<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Models\Transcript;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ScheduledTranscriptsCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transcripts:check-new {--months=1} {--committee-id=} {--notify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for new transcripts and report changes (designed for scheduled execution)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for new transcripts...');

        $months = $this->option('months');
        $targetDate = Carbon::now()->subMonths($months);

        $committees = $this->getCommitteesToCheck();
        
        if ($committees->isEmpty()) {
            $this->warn('No committees found. Run committees:scrape first.');
            return 1;
        }

        $newTranscriptsFound = [];
        $totalChecked = 0;

        foreach ($committees as $committee) {
            $result = $this->checkCommitteeForNewTranscripts($committee, $targetDate);
            $totalChecked += $result['total'];
            
            if (!empty($result['new'])) {
                $newTranscriptsFound[$committee->name] = $result['new'];
                $this->info("✓ Committee '{$committee->name}': " . count($result['new']) . " new transcripts");
            } else {
                $this->line("- Committee '{$committee->name}': No new transcripts");
            }
        }

        $this->reportResults($newTranscriptsFound, $totalChecked, $months);

        return 0;
    }

    /**
     * Get committees to check
     */
    private function getCommitteesToCheck()
    {
        if ($this->option('committee-id')) {
            return Committee::where('committee_id', $this->option('committee-id'))->get();
        }

        // Check all committees by default
        return Committee::orderBy('name')->get();
    }

    /**
     * Check a committee for new transcripts
     */
    private function checkCommitteeForNewTranscripts($committee, $targetDate)
    {
        $newTranscripts = [];
        $totalCount = 0;

        // Check current month and previous months based on option
        $currentDate = Carbon::now();
        $checkDate = $targetDate->copy();

        while ($checkDate->lte($currentDate)) {
            $monthResult = $this->checkMonthForTranscripts($committee, $checkDate->year, $checkDate->month);
            $totalCount += $monthResult['count'];
            $newTranscripts = array_merge($newTranscripts, $monthResult['new']);
            
            $checkDate->addMonth();
        }

        return ['total' => $totalCount, 'new' => $newTranscripts];
    }

    /**
     * Check a specific month for transcripts
     */
    private function checkMonthForTranscripts($committee, $year, $month)
    {
        $url = "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Steno/{$year}/{$month}/{$committee->committee_id}/0";
        
        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                $this->warn("Failed to fetch transcripts for committee {$committee->name} ({$year}/{$month})");
                return ['count' => 0, 'new' => []];
            }

            $transcripts = $response->json();

            if (!is_array($transcripts)) {
                return ['count' => 0, 'new' => []];
            }

            $newTranscripts = [];

            foreach ($transcripts as $transcriptData) {
                if (!isset($transcriptData['t_id'])) {
                    continue;
                }

                $transcriptId = $transcriptData['t_id'];
                
                // Check if transcript exists in database
                $existingTranscript = Transcript::where('transcript_id', $transcriptId)->first();
                
                if (!$existingTranscript) {
                    // Fetch transcript content
                    $content = $this->fetchTranscriptContent($transcriptId);
                    
                    if ($content) {
                        $transcriptDate = $this->parseTranscriptDate($transcriptData['t_date'] ?? null);
                        
                        $transcript = Transcript::create([
                            'transcript_id' => $transcriptId,
                            'committee_id' => $committee->committee_id,
                            'type' => $transcriptData['t_label'] ?? 'Unknown',
                            'transcript_date' => $transcriptDate,
                            'content_html' => $content,
                            'metadata' => $this->extractMetadata($transcriptData),
                        ]);

                        $newTranscripts[] = [
                            'id' => $transcript->transcript_id,
                            'type' => $transcript->type,
                            'date' => $transcript->transcript_date ? $transcript->transcript_date->format('Y-m-d') : 'Unknown',
                            'word_count' => $transcript->word_count,
                        ];
                    }
                }
            }

            return ['count' => count($transcripts), 'new' => $newTranscripts];

        } catch (\Exception $e) {
            $this->error("Error checking committee {$committee->name} ({$year}/{$month}): " . $e->getMessage());
            return ['count' => 0, 'new' => []];
        }
    }

    /**
     * Fetch transcript content from the com-steno API
     */
    private function fetchTranscriptContent($transcriptId): ?string
    {
        $url = "https://www.parliament.bg/api/v1/com-steno/bg/{$transcriptId}";
        
        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            
            if (is_string($data)) {
                return $data;
            }
            
            if (is_array($data)) {
                return $data['content'] ?? $data['html'] ?? $data['text'] ?? null;
            }

            return null;

        } catch (\Exception $e) {
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
            return null;
        }
    }

    /**
     * Extract metadata from transcript data
     */
    private function extractMetadata(array $transcriptData): array
    {
        $metadata = [];
        
        $fieldsToStore = ['t_label', 't_date', 't_time', 't_status'];
        
        foreach ($fieldsToStore as $field) {
            if (isset($transcriptData[$field])) {
                $metadata[$field] = $transcriptData[$field];
            }
        }

        return $metadata;
    }

    /**
     * Report the results
     */
    private function reportResults($newTranscriptsFound, $totalChecked, $months)
    {
        $totalNewTranscripts = array_sum(array_map('count', $newTranscriptsFound));

        $this->newLine();
        $this->info("=== TRANSCRIPTS CHECK SUMMARY ===");
        $this->info("Period: Last {$months} month(s)");
        $this->info("Total transcripts checked: {$totalChecked}");
        $this->info("New transcripts found: {$totalNewTranscripts}");

        if (!empty($newTranscriptsFound)) {
            $this->newLine();
            $this->info("NEW TRANSCRIPTS DETAILS:");
            
            foreach ($newTranscriptsFound as $committeeName => $transcripts) {
                $this->newLine();
                $this->line("<fg=cyan>Committee: {$committeeName}</>");
                
                foreach ($transcripts as $transcript) {
                    $wordCount = $transcript['word_count'] ? number_format($transcript['word_count']) . ' words' : 'N/A';
                    $this->line("  • [{$transcript['type']}] {$transcript['date']}");
                    $this->line("    ID: {$transcript['id']} | Words: {$wordCount}");
                }
            }

            // If notify option is set, you could add email/slack notifications here
            if ($this->option('notify')) {
                $this->info("\n📧 Notification would be sent (implement notification logic)");
            }
        }

        $this->newLine();
        $this->info("Check completed at " . Carbon::now()->format('Y-m-d H:i:s'));
    }
}
