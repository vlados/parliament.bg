<?php

namespace App\Console\Commands;

use App\Models\Transcript;
use App\Models\BillAnalysis;
use App\Services\TranscriptAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeTranscripts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:transcripts 
                            {--all : Analyze all transcripts}
                            {--ids=* : Specific transcript IDs to analyze}
                            {--since= : Analyze transcripts since this date (Y-m-d format)}
                            {--committee= : Filter by committee ID}
                            {--reanalyze : Re-analyze transcripts that have already been analyzed}
                            {--dry-run : Show what would be analyzed without actually processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze transcripts for bill discussions using AI';

    private TranscriptAnalysisService $analysisService;

    public function __construct(TranscriptAnalysisService $analysisService)
    {
        parent::__construct();
        $this->analysisService = $analysisService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Starting transcript analysis...');

        // Get transcripts to analyze
        $transcripts = $this->getTranscriptsToAnalyze();

        if ($transcripts->isEmpty()) {
            $this->info('No transcripts found to analyze.');
            return Command::SUCCESS;
        }

        $this->info("Found {$transcripts->count()} transcript(s) to analyze.");

        if ($this->option('dry-run')) {
            $this->showDryRunResults($transcripts);
            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (!$this->option('all') && !$this->confirm('Do you want to proceed with the analysis?')) {
            $this->info('Analysis cancelled.');
            return Command::SUCCESS;
        }

        // Process transcripts
        $progressBar = $this->output->createProgressBar($transcripts->count());
        $progressBar->start();

        $totalAnalyses = 0;
        $errors = 0;
        $startTime = microtime(true);

        foreach ($transcripts as $transcript) {
            try {
                $progressBar->setMessage("Analyzing transcript ID: {$transcript->id}");

                // Delete existing analyses if reanalyzing
                if ($this->option('reanalyze')) {
                    BillAnalysis::where('transcript_id', $transcript->id)->delete();
                }

                $analyses = $this->analysisService->analyzeTranscript($transcript);
                $totalAnalyses += $analyses->count();

                $progressBar->advance();

            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError analyzing transcript ID {$transcript->id}: " . $e->getMessage());
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        $this->showResults($totalAnalyses, $errors, microtime(true) - $startTime);

        return Command::SUCCESS;
    }

    /**
     * Get transcripts to analyze based on options
     */
    private function getTranscriptsToAnalyze()
    {
        $query = Transcript::query();

        // Filter by specific IDs
        if ($this->option('ids')) {
            $ids = array_filter($this->option('ids'));
            return $query->whereIn('id', $ids)->get();
        }

        // Filter by date
        if ($this->option('since')) {
            $query->where('transcript_date', '>=', $this->option('since'));
        }

        // Filter by committee
        if ($this->option('committee')) {
            $query->where('committee_id', $this->option('committee'));
        }

        // Exclude already analyzed unless reanalyzing
        if (!$this->option('reanalyze')) {
            $query->whereDoesntHave('billAnalyses');
        }

        // Get all or with limits
        if ($this->option('all')) {
            return $query->orderBy('transcript_date', 'desc')->get();
        }

        // Default: get recent unanalyzed transcripts
        return $query->orderBy('transcript_date', 'desc')->limit(10)->get();
    }

    /**
     * Show dry run results
     */
    private function showDryRunResults($transcripts)
    {
        $this->info('🔍 Dry run results:');
        $this->newLine();

        $table = [];
        foreach ($transcripts as $transcript) {
            $alreadyAnalyzed = BillAnalysis::where('transcript_id', $transcript->id)->exists();
            
            $table[] = [
                $transcript->id,
                $transcript->transcript_date?->format('Y-m-d'),
                $transcript->committee?->name ?? 'N/A',
                number_format($transcript->word_count ?? 0),
                $alreadyAnalyzed ? '✓' : '✗',
            ];
        }

        $this->table([
            'ID',
            'Date',
            'Committee',
            'Words',
            'Analyzed'
        ], $table);

        $alreadyAnalyzedCount = $transcripts->filter(function ($transcript) {
            return BillAnalysis::where('transcript_id', $transcript->id)->exists();
        })->count();

        $this->info("Total transcripts: {$transcripts->count()}");
        $this->info("Already analyzed: {$alreadyAnalyzedCount}");
        $this->info("Would analyze: " . ($transcripts->count() - ($this->option('reanalyze') ? 0 : $alreadyAnalyzedCount)));
    }

    /**
     * Show analysis results
     */
    private function showResults(int $totalAnalyses, int $errors, float $executionTime)
    {
        $this->info('✅ Analysis completed!');
        $this->newLine();

        $this->info("📊 Results:");
        $this->info("• Total bill discussions found: {$totalAnalyses}");
        $this->info("• Errors encountered: {$errors}");
        $this->info("• Execution time: " . number_format($executionTime, 2) . " seconds");

        if ($totalAnalyses > 0) {
            $this->newLine();
            
            // Show statistics
            $stats = $this->analysisService->getAnalysisStatistics();
            
            $this->info("📈 Overall Statistics:");
            $this->info("• Total analyses in database: {$stats['total_analyses']}");
            $this->info("• High confidence (≥80%): {$stats['high_confidence']}");
            $this->info("• Low confidence (<50%): {$stats['low_confidence']}");

            if (!empty($stats['by_status'])) {
                $this->newLine();
                $this->info("📋 By Status:");
                foreach ($stats['by_status'] as $status => $count) {
                    $this->info("• {$status}: {$count}");
                }
            }

            if (!empty($stats['by_amendment_type'])) {
                $this->newLine();
                $this->info("🔧 By Amendment Type:");
                foreach ($stats['by_amendment_type'] as $type => $count) {
                    $this->info("• {$type}: {$count}");
                }
            }

            // Show recent high-confidence analyses
            $this->showRecentAnalyses();
        }

        if ($errors > 0) {
            $this->newLine();
            $this->error("⚠️  Some transcripts failed to analyze. Check the logs for details.");
        }
    }

    /**
     * Show recent high-confidence analyses
     */
    private function showRecentAnalyses()
    {
        $recentAnalyses = BillAnalysis::with(['transcript', 'bill'])
            ->highConfidence(0.7)
            ->latest()
            ->limit(5)
            ->get();

        if ($recentAnalyses->isNotEmpty()) {
            $this->newLine();
            $this->info("🎯 Recent High-Confidence Analyses:");

            $table = [];
            foreach ($recentAnalyses as $analysis) {
                $table[] = [
                    $analysis->bill_identifier ?? 'N/A',
                    $analysis->proposer_name ?? 'N/A',
                    $analysis->amendment_type_label ?? 'N/A',
                    $analysis->status_label,
                    $analysis->confidence_percentage ?? 'N/A',
                    $analysis->transcript->transcript_date?->format('Y-m-d') ?? 'N/A',
                ];
            }

            $this->table([
                'Bill',
                'Proposer',
                'Type',
                'Status',
                'Confidence',
                'Date'
            ], $table);
        }
    }
}
