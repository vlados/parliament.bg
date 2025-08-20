<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Models\Transcript;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ScrapeTranscripts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transcripts:scrape {--committee=} {--year=} {--month=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape transcripts from parliament.bg API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting transcripts scraping...');

        // Get target parameters
        $params = $this->getTargetParameters();
        
        if (!$params) {
            return 1;
        }

        $totalTranscripts = 0;
        $newTranscripts = 0;
        $updatedTranscripts = 0;

        foreach ($params as $param) {
            $this->info("Processing committee {$param['committee_id']} for {$param['year']}/{$param['month']}");
            
            $result = $this->scrapeTranscriptsForPeriod(
                $param['committee_id'],
                $param['year'],
                $param['month']
            );

            $totalTranscripts += $result['total'];
            $newTranscripts += $result['new'];
            $updatedTranscripts += $result['updated'];
        }

        $this->info("Transcripts scraping completed!");
        $this->info("Total transcripts processed: {$totalTranscripts}");
        $this->info("New transcripts added: {$newTranscripts}");
        $this->info("Transcripts updated: {$updatedTranscripts}");

        return 0;
    }

    /**
     * Get target parameters based on command options
     */
    private function getTargetParameters(): ?array
    {
        $params = [];

        if ($this->option('all')) {
            // Get all committees and current year/month
            $committees = Committee::get();
            $currentDate = Carbon::now();
            
            foreach ($committees as $committee) {
                $params[] = [
                    'committee_id' => $committee->committee_id,
                    'year' => $currentDate->year,
                    'month' => $currentDate->month,
                ];
            }
        } else {
            // Get specific committee, year, month
            $committeeId = $this->option('committee');
            $year = $this->option('year') ?? Carbon::now()->year;
            $month = $this->option('month') ?? Carbon::now()->month;

            if (!$committeeId) {
                $this->error('Please specify --committee or use --all');
                return null;
            }

            $committee = Committee::where('committee_id', $committeeId)->first();
            if (!$committee) {
                $this->error("Committee with ID {$committeeId} not found");
                return null;
            }

            $params[] = [
                'committee_id' => $committeeId,
                'year' => $year,
                'month' => $month,
            ];
        }

        return $params;
    }

    /**
     * Scrape transcripts for a specific committee and period
     */
    private function scrapeTranscriptsForPeriod($committeeId, $year, $month): array
    {
        $url = "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Steno/{$year}/{$month}/{$committeeId}/0";
        
        $this->info("Fetching transcripts from: {$url}");

        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                $this->error("Failed to fetch transcripts for committee {$committeeId}");
                return ['total' => 0, 'new' => 0, 'updated' => 0];
            }

            $data = $response->json();

            if (!is_array($data)) {
                $this->warn("Invalid response format for committee {$committeeId}");
                return ['total' => 0, 'new' => 0, 'updated' => 0];
            }

            return $this->processTranscriptsList($data, $committeeId);

        } catch (\Exception $e) {
            $this->error("Error fetching transcripts for committee {$committeeId}: " . $e->getMessage());
            return ['total' => 0, 'new' => 0, 'updated' => 0];
        }
    }

    /**
     * Process the list of transcripts from API
     */
    private function processTranscriptsList(array $transcripts, $committeeId): array
    {
        $total = count($transcripts);
        $new = 0;
        $updated = 0;

        foreach ($transcripts as $transcriptData) {
            if (!isset($transcriptData['t_id'])) {
                $this->warn("Transcript missing t_id, skipping");
                continue;
            }

            $transcriptId = $transcriptData['t_id'];
            
            // Check if transcript already exists
            $existingTranscript = Transcript::where('transcript_id', $transcriptId)->first();

            if ($existingTranscript && $existingTranscript->content_html) {
                // Skip if we already have content
                continue;
            }

            // Fetch transcript content
            $contentData = $this->fetchTranscriptContent($transcriptId);
            
            if (!$contentData || !$contentData['content']) {
                $this->warn("Failed to fetch content for transcript {$transcriptId}");
                continue;
            }

            if ($existingTranscript) {
                // Update existing transcript
                $existingTranscript->update([
                    'content_html' => $contentData['content'],
                    'metadata' => array_merge(
                        $existingTranscript->metadata ?? [],
                        $this->extractMetadata($transcriptData),
                        $contentData['metadata'] ?? []
                    ),
                ]);
                $updated++;
                $this->info("✓ Updated transcript {$transcriptId}");
            } else {
                // Create new transcript
                // Use date from content API if available, fallback to list API
                $transcriptDate = $this->parseTranscriptDate(
                    $contentData['date'] ?? $transcriptData['t_date'] ?? null
                );
                
                Transcript::create([
                    'transcript_id' => $transcriptId,
                    'committee_id' => $committeeId,
                    'type' => $contentData['type'] ?? $transcriptData['t_label'] ?? 'Unknown',
                    'transcript_date' => $transcriptDate,
                    'content_html' => $contentData['content'],
                    'metadata' => array_merge(
                        $this->extractMetadata($transcriptData),
                        $contentData['metadata'] ?? []
                    ),
                ]);
                
                $new++;
                $this->info("✓ Created transcript {$transcriptId}");
            }
        }

        return ['total' => $total, 'new' => $new, 'updated' => $updated];
    }

    /**
     * Fetch transcript content from the com-steno API
     */
    private function fetchTranscriptContent($transcriptId): ?array
    {
        $url = "https://www.parliament.bg/api/v1/com-steno/bg/{$transcriptId}";
        
        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            
            // The API returns JSON with the HTML content in A_Cm_St_text field
            if (is_array($data) && isset($data['A_Cm_St_text'])) {
                return [
                    'content' => $data['A_Cm_St_text'],
                    'date' => $data['A_Cm_St_date'] ?? null,
                    'type' => $data['A_Cm_St_sub'] ?? null,
                    'metadata' => [
                        'steno_id' => $data['A_Cm_Stid'] ?? null,
                        'acts' => $data['acts'] ?? [],
                        'raw_response' => $data,
                    ],
                ];
            }
            
            // Fallback for different response formats
            if (is_string($data)) {
                return ['content' => $data, 'metadata' => []];
            }

            return null;

        } catch (\Exception $e) {
            $this->error("Error fetching content for transcript {$transcriptId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse transcript date from various formats
     */
    private function parseTranscriptDate($dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            $this->warn("Failed to parse date: {$dateString}");
            return null;
        }
    }

    /**
     * Extract metadata from transcript data
     */
    private function extractMetadata(array $transcriptData): array
    {
        $metadata = [];
        
        // Store any additional fields that might be useful
        $fieldsToStore = ['t_label', 't_date', 't_time', 't_status'];
        
        foreach ($fieldsToStore as $field) {
            if (isset($transcriptData[$field])) {
                $metadata[$field] = $transcriptData[$field];
            }
        }

        return $metadata;
    }
}
