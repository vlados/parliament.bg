<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Models\Transcript;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use function Laravel\Prompts\table;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;

class ListTranscripts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transcripts:list 
                            {--committee= : Filter by committee ID}
                            {--year= : Filter by year}
                            {--month= : Filter by month}
                            {--downloaded : Show only downloaded transcripts}
                            {--not-downloaded : Show only not downloaded transcripts}
                            {--export : Export the list to CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List transcripts from parliament.bg with their download status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Interactive committee selection if not provided
        $committeeId = $this->option('committee');
        
        if (!$committeeId) {
            $committees = Committee::orderBy('name')->get();
            
            if ($committees->isEmpty()) {
                error('No committees found. Please run committees:scrape first.');
                return 1;
            }
            
            $options = ['all' => 'All Committees'];
            foreach ($committees as $committee) {
                $options[$committee->committee_id] = $committee->name;
            }
            
            $selected = select(
                label: 'Select a committee to view transcripts:',
                options: $options,
                default: 'all'
            );
            
            $committeeId = $selected === 'all' ? null : $selected;
        }
        
        // Get year and month
        $year = $this->option('year') ?? Carbon::now()->year;
        $month = $this->option('month');
        
        // If no month specified, we'll fetch all months for the year
        if ($month) {
            info("Fetching transcripts for {$year}/{$month}...");
        } else {
            info("Fetching transcripts for entire year {$year}...");
        }
        
        // Fetch available transcripts from API
        $availableTranscripts = spin(
            fn() => $this->fetchAvailableTranscripts($committeeId, $year, $month),
            $month ? "Loading transcripts for {$year}/{$month}..." : "Loading transcripts for year {$year}..."
        );
        
        if (empty($availableTranscripts)) {
            warning('No transcripts found for the selected criteria.');
            return 0;
        }
        
        // Get downloaded transcripts from database
        $downloadedTranscripts = $this->getDownloadedTranscripts($committeeId);
        
        // Prepare table data
        $tableData = $this->prepareTableData($availableTranscripts, $downloadedTranscripts);
        
        // Apply filters
        if ($this->option('downloaded')) {
            $tableData = array_filter($tableData, fn($row) => $row['Downloaded'] === '✅ Yes');
        } elseif ($this->option('not-downloaded')) {
            $tableData = array_filter($tableData, fn($row) => $row['Downloaded'] === '❌ No');
        }
        
        // Display table
        $this->displayTable($tableData);
        
        // Export if requested
        if ($this->option('export')) {
            $this->exportToCSV($tableData);
        }
        
        // Show summary
        $this->showSummary($tableData);
        
        // Ask if user wants to download missing transcripts
        $notDownloaded = array_filter($tableData, fn($row) => $row['Downloaded'] === '❌ No');
        if (count($notDownloaded) > 0) {
            if (confirm("Found " . count($notDownloaded) . " transcripts not downloaded. Would you like to download them now?")) {
                $this->downloadMissingTranscripts($notDownloaded, $committeeId);
            }
        }
        
        return 0;
    }
    
    /**
     * Fetch available transcripts from parliament.bg API
     */
    private function fetchAvailableTranscripts($committeeId, $year, $month = null): array
    {
        $transcripts = [];
        
        // If no month specified, fetch all months for the year
        $months = $month ? [$month] : range(1, 12);
        
        foreach ($months as $currentMonth) {
            if ($committeeId) {
                // Fetch for specific committee
                $monthTranscripts = $this->fetchCommitteeTranscripts($committeeId, $year, $currentMonth);
                $transcripts = array_merge($transcripts, $monthTranscripts);
            } else {
                // Fetch for all committees
                $committees = Committee::get();
                foreach ($committees as $committee) {
                    $committeeTranscripts = $this->fetchCommitteeTranscripts($committee->committee_id, $year, $currentMonth);
                    foreach ($committeeTranscripts as &$transcript) {
                        $transcript['committee_name'] = $committee->name;
                        $transcript['committee_id'] = $committee->committee_id;
                    }
                    $transcripts = array_merge($transcripts, $committeeTranscripts);
                }
            }
        }
        
        return $transcripts;
    }
    
    /**
     * Fetch transcripts for a specific committee
     */
    private function fetchCommitteeTranscripts($committeeId, $year, $month): array
    {
        $url = "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Steno/{$year}/{$month}/{$committeeId}/0";
        
        try {
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                return [];
            }
            
            $data = $response->json();
            
            if (!is_array($data)) {
                return [];
            }
            
            return $data;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get downloaded transcripts from database
     */
    private function getDownloadedTranscripts($committeeId): array
    {
        $query = Transcript::query();
        
        if ($committeeId) {
            $query->where('committee_id', $committeeId);
        }
        
        return $query->pluck('transcript_id')->toArray();
    }
    
    /**
     * Prepare table data for display
     */
    private function prepareTableData(array $availableTranscripts, array $downloadedIds): array
    {
        $tableData = [];
        
        foreach ($availableTranscripts as $transcript) {
            $transcriptId = $transcript['t_id'] ?? 'N/A';
            $isDownloaded = in_array($transcriptId, $downloadedIds);
            
            // Get additional info from database if downloaded
            $dbTranscript = null;
            if ($isDownloaded) {
                $dbTranscript = Transcript::where('transcript_id', $transcriptId)->first();
            }
            
            // Format date to show month when viewing full year
            $dateStr = $transcript['t_date'] ?? 'N/A';
            if ($dateStr !== 'N/A') {
                try {
                    $date = Carbon::parse($dateStr);
                    $dateStr = $date->format('Y-m-d');
                } catch (\Exception $e) {
                    // Keep original if parsing fails
                }
            }
            
            $tableData[] = [
                'ID' => $transcriptId,
                'Date' => $dateStr,
                'Type' => $transcript['t_label'] ?? 'N/A',
                'Committee' => $transcript['committee_name'] ?? 'N/A',
                'Downloaded' => $isDownloaded ? '✅ Yes' : '❌ No',
                'Has Content' => $dbTranscript && $dbTranscript->content_html ? '✅' : '⚪',
                'Word Count' => $dbTranscript ? number_format($dbTranscript->word_count ?? 0) : '-',
                'Analyzed' => $dbTranscript && $dbTranscript->billAnalyses()->exists() ? '✅' : '⚪',
            ];
        }
        
        // Sort by date descending
        usort($tableData, function($a, $b) {
            return strcmp($b['Date'], $a['Date']);
        });
        
        return $tableData;
    }
    
    /**
     * Display the table
     */
    private function displayTable(array $tableData): void
    {
        if (empty($tableData)) {
            warning('No data to display.');
            return;
        }
        
        // Convert to format expected by Laravel Prompts table
        $headers = array_keys($tableData[0]);
        $rows = array_map(fn($row) => array_values($row), $tableData);
        
        table($headers, $rows);
    }
    
    /**
     * Export data to CSV
     */
    private function exportToCSV(array $tableData): void
    {
        $filename = 'transcripts_list_' . Carbon::now()->format('Y-m-d_His') . '.csv';
        $filepath = storage_path('app/' . $filename);
        
        $handle = fopen($filepath, 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        if (!empty($tableData)) {
            fputcsv($handle, array_keys($tableData[0]));
        }
        
        // Write data
        foreach ($tableData as $row) {
            // Clean up symbols for CSV
            $row['Downloaded'] = str_replace(['✅ Yes', '❌ No'], ['Yes', 'No'], $row['Downloaded']);
            $row['Has Content'] = str_replace(['✅', '⚪'], ['Yes', 'No'], $row['Has Content']);
            $row['Analyzed'] = str_replace(['✅', '⚪'], ['Yes', 'No'], $row['Analyzed']);
            
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        info("Data exported to: {$filepath}");
    }
    
    /**
     * Show summary statistics
     */
    private function showSummary(array $tableData): void
    {
        $total = count($tableData);
        $downloaded = count(array_filter($tableData, fn($row) => $row['Downloaded'] === '✅ Yes'));
        $hasContent = count(array_filter($tableData, fn($row) => $row['Has Content'] === '✅'));
        $analyzed = count(array_filter($tableData, fn($row) => $row['Analyzed'] === '✅'));
        
        info("\n📊 Summary:");
        info("• Total transcripts: {$total}");
        info("• Downloaded: {$downloaded} (" . ($total > 0 ? round($downloaded / $total * 100, 1) : 0) . "%)");
        info("• With content: {$hasContent}");
        info("• Analyzed: {$analyzed}");
        info("• Not downloaded: " . ($total - $downloaded));
    }
    
    /**
     * Download missing transcripts
     */
    private function downloadMissingTranscripts(array $notDownloaded, $committeeId): void
    {
        info("\n📥 Downloading missing transcripts...");
        
        // Group transcripts by committee and date for efficient downloading
        $grouped = [];
        foreach ($notDownloaded as $transcript) {
            $date = $transcript['Date'] ?? 'N/A';
            if ($date !== 'N/A') {
                $parsedDate = Carbon::parse($date);
                $year = $parsedDate->year;
                $month = $parsedDate->month;
                $committee = $transcript['Committee'] ?? 'Unknown';
                
                // Find committee ID if needed
                if (!$committeeId) {
                    $committeeModel = Committee::where('name', $committee)->first();
                    $currentCommitteeId = $committeeModel ? $committeeModel->committee_id : null;
                } else {
                    $currentCommitteeId = $committeeId;
                }
                
                if ($currentCommitteeId) {
                    $key = "{$currentCommitteeId}_{$year}_{$month}";
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'committee_id' => $currentCommitteeId,
                            'year' => $year,
                            'month' => $month,
                            'count' => 0
                        ];
                    }
                    $grouped[$key]['count']++;
                }
            }
        }
        
        if (empty($grouped)) {
            error('Could not determine transcript dates for downloading.');
            return;
        }
        
        // Download transcripts for each group
        foreach ($grouped as $group) {
            info("Downloading {$group['count']} transcripts for {$group['year']}/{$group['month']}...");
            
            $this->call('transcripts:scrape', [
                '--committee' => $group['committee_id'],
                '--year' => $group['year'],
                '--month' => $group['month'],
            ]);
        }
        
        info("✅ Download process completed!");
    }
}