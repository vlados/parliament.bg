<?php

namespace App\Console\Commands;

use App\Models\Transcript;
use App\Models\ProtocolExtraction;
use App\Services\LangExtractService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExtractProtocolChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transcripts:extract 
                            {--transcript=* : Specific transcript IDs to process}
                            {--committee= : Process transcripts from specific committee}
                            {--from= : Process transcripts from this date (YYYY-MM-DD)}
                            {--to= : Process transcripts until this date (YYYY-MM-DD)}
                            {--type= : Extraction type (bill_discussions, committee_decisions, amendments, speaker_statements, all)}
                            {--limit= : Limit number of transcripts to process}
                            {--force : Re-process already extracted transcripts}
                            {--check-deps : Check Python dependencies}
                            {--install-deps : Install Python dependencies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract structured protocol changes from transcripts using LangExtract';

    protected LangExtractService $extractService;

    /**
     * Create a new command instance.
     */
    public function __construct(LangExtractService $extractService)
    {
        parent::__construct();
        $this->extractService = $extractService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check dependencies if requested
        if ($this->option('check-deps')) {
            return $this->checkDependencies();
        }
        
        // Install dependencies if requested
        if ($this->option('install-deps')) {
            return $this->installDependencies();
        }
        
        $this->info('Starting protocol changes extraction...');
        
        // Get transcripts to process
        $transcripts = $this->getTranscriptsToProcess();
        
        if ($transcripts->isEmpty()) {
            $this->warn('No transcripts found to process.');
            return 0;
        }
        
        $this->info("Found {$transcripts->count()} transcripts to process.");
        
        $extractionType = $this->option('type') ?? 'all';
        $successCount = 0;
        $errorCount = 0;
        
        $bar = $this->output->createProgressBar($transcripts->count());
        $bar->start();
        
        foreach ($transcripts as $transcript) {
            try {
                $this->processTranscript($transcript, $extractionType);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to extract from transcript', [
                    'transcript_id' => $transcript->id,
                    'error' => $e->getMessage()
                ]);
                
                if ($this->output->isVerbose()) {
                    $this->error("\nError processing transcript {$transcript->transcript_id}: {$e->getMessage()}");
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Extraction completed!");
        $this->info("Successfully processed: {$successCount} transcripts");
        
        if ($errorCount > 0) {
            $this->warn("Failed to process: {$errorCount} transcripts");
        }
        
        return $errorCount > 0 ? 1 : 0;
    }
    
    /**
     * Get transcripts to process based on command options
     */
    protected function getTranscriptsToProcess()
    {
        $query = Transcript::query();
        
        // Filter by specific transcript IDs
        if ($transcriptIds = $this->option('transcript')) {
            $query->whereIn('transcript_id', $transcriptIds);
        }
        
        // Filter by committee
        if ($committeeId = $this->option('committee')) {
            $query->where('committee_id', $committeeId);
        }
        
        // Filter by date range
        if ($from = $this->option('from')) {
            $query->where('transcript_date', '>=', Carbon::parse($from));
        }
        
        if ($to = $this->option('to')) {
            $query->where('transcript_date', '<=', Carbon::parse($to));
        }
        
        // Skip already processed unless forced
        if (!$this->option('force')) {
            $query->whereDoesntHave('protocolExtractions');
        }
        
        // Only process transcripts with content
        $query->whereNotNull('content_text')
              ->where('content_text', '!=', '');
        
        // Apply limit if specified
        if ($limit = $this->option('limit')) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
    
    /**
     * Process a single transcript
     */
    protected function processTranscript(Transcript $transcript, string $extractionType)
    {
        $content = $transcript->content_text;
        
        if (empty($content)) {
            throw new \Exception('Transcript has no content');
        }
        
        // Add delay between API calls to respect rate limits
        static $lastCallTime = 0;
        $minInterval = 2; // 2 seconds between calls
        $timeSinceLastCall = time() - $lastCallTime;
        
        if ($timeSinceLastCall < $minInterval) {
            sleep($minInterval - $timeSinceLastCall);
        }
        
        $lastCallTime = time();
        
        // Perform extraction with retry logic
        $maxRetries = 3;
        $retryDelay = 60; // Start with 1 minute delay
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $results = $this->extractService->extractProtocolChanges($content, [
                    'extraction_type' => $extractionType,
                    'transcript_id' => $transcript->transcript_id,
                    'committee_id' => $transcript->committee_id
                ]);
                
                if (isset($results['error'])) {
                    $errorMessage = $results['error'];
                    
                    // Check if it's a quota/rate limit error
                    if (str_contains($errorMessage, 'RESOURCE_EXHAUSTED') || 
                        str_contains($errorMessage, '429')) {
                        
                        if ($attempt < $maxRetries) {
                            $this->warn("Rate limit hit, waiting {$retryDelay} seconds before retry {$attempt}/{$maxRetries}...");
                            sleep($retryDelay);
                            $retryDelay *= 2; // Exponential backoff
                            continue;
                        }
                    }
                    
                    throw new \Exception($errorMessage);
                }
                
                // Store extraction results
                $this->storeExtractionResults($transcript, $results, $extractionType);
                return; // Success, exit retry loop
                
            } catch (\Exception $e) {
                if ($attempt < $maxRetries && 
                    (str_contains($e->getMessage(), 'RESOURCE_EXHAUSTED') || 
                     str_contains($e->getMessage(), '429'))) {
                    
                    $this->warn("Rate limit hit, waiting {$retryDelay} seconds before retry {$attempt}/{$maxRetries}...");
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
                
                throw $e; // Re-throw if not retryable or max retries reached
            }
        }
    }
    
    /**
     * Store extraction results in the database
     */
    protected function storeExtractionResults(Transcript $transcript, array $results, string $extractionType)
    {
        // Delete existing extractions if re-processing
        if ($this->option('force')) {
            ProtocolExtraction::where('transcript_id', $transcript->id)
                ->where('extraction_type', $extractionType)
                ->delete();
        }
        
        // Store each type of extraction
        if ($extractionType === 'all') {
            foreach ($results as $type => $data) {
                $this->createExtraction($transcript, $type, $data);
            }
        } else {
            $this->createExtraction($transcript, $extractionType, $results);
        }
    }
    
    /**
     * Create a single extraction record
     */
    protected function createExtraction(Transcript $transcript, string $type, array $data)
    {
        ProtocolExtraction::create([
            'transcript_id' => $transcript->id,
            'extraction_type' => $type,
            'extracted_data' => $data,
            'extraction_date' => now(),
            'metadata' => [
                'extractor_version' => '1.0',
                'model_used' => 'gemini-1.5-pro',
                'extraction_options' => $this->options()
            ]
        ]);
    }
    
    /**
     * Check Python dependencies
     */
    protected function checkDependencies()
    {
        $this->info('Checking dependencies...');
        
        $checks = $this->extractService->checkDependencies();
        
        $table = [];
        foreach ($checks as $dep => $status) {
            $installed = $status['installed'] ?? $status['configured'] ?? false;
            $version = $status['version'] ?? 'N/A';
            
            $table[] = [
                $dep,
                $installed ? '✓' : '✗',
                $version
            ];
        }
        
        $this->table(['Dependency', 'Status', 'Version'], $table);
        
        $allGood = collect($checks)->every(function ($check) {
            return $check['installed'] ?? $check['configured'] ?? false;
        });
        
        if (!$allGood) {
            $this->warn('Some dependencies are missing. Run with --install-deps to install them.');
            return 1;
        }
        
        $this->info('All dependencies are installed!');
        return 0;
    }
    
    /**
     * Install Python dependencies
     */
    protected function installDependencies()
    {
        $this->info('Installing Python dependencies...');
        
        if ($this->extractService->installDependencies()) {
            $this->info('Dependencies installed successfully!');
            return 0;
        } else {
            $this->error('Failed to install dependencies. Please check the logs.');
            return 1;
        }
    }
}