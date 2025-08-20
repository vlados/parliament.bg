<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Models\Transcript;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;
use function Laravel\Prompts\select;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;

class ExportCommitteeTranscripts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transcripts:export-committee 
                            {--committee=* : Committee IDs to export (multiple allowed)}
                            {--all : Export all committees}
                            {--from= : Export transcripts from this date (YYYY-MM-DD)}
                            {--to= : Export transcripts until this date (YYYY-MM-DD)}
                            {--format=txt : Export format (txt, json, csv, html)}
                            {--separate-files : Create separate file for each transcript}
                            {--include-metadata : Include transcript metadata}
                            {--include-analysis : Include AI analysis if available}
                            {--output= : Custom output directory name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export committee transcripts to files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get committees to export
        $committees = $this->getCommitteesToExport();
        
        if ($committees->isEmpty()) {
            error('No committees selected for export.');
            return 1;
        }
        
        info("Selected " . $committees->count() . " committee(s) for export.");
        
        // Get date range
        $fromDate = $this->option('from') ? Carbon::parse($this->option('from')) : null;
        $toDate = $this->option('to') ? Carbon::parse($this->option('to')) : null;
        
        // Prepare output directory
        $outputDir = $this->prepareOutputDirectory();
        
        $totalExported = 0;
        $format = $this->option('format');
        
        foreach ($committees as $committee) {
            info("\n📁 Exporting transcripts for: {$committee->name}");
            
            // Get transcripts for this committee
            $transcripts = spin(
                fn() => $this->getCommitteeTranscripts($committee, $fromDate, $toDate),
                "Loading transcripts for {$committee->name}..."
            );
            
            if ($transcripts->isEmpty()) {
                warning("No transcripts found for {$committee->name}");
                continue;
            }
            
            info("Found {$transcripts->count()} transcripts to export.");
            
            // Export based on selected options
            if ($this->option('separate-files')) {
                $exported = $this->exportSeparateFiles($transcripts, $committee, $outputDir, $format);
            } else {
                $exported = $this->exportCombinedFile($transcripts, $committee, $outputDir, $format);
            }
            
            $totalExported += $exported;
            info("✅ Exported {$exported} transcripts for {$committee->name}");
        }
        
        info("\n🎉 Export completed! Total transcripts exported: {$totalExported}");
        info("📂 Files saved to: {$outputDir}");
        
        // Ask if user wants to open the directory
        if (confirm("Would you like to open the export directory?")) {
            $this->openDirectory($outputDir);
        }
        
        return 0;
    }
    
    /**
     * Get committees to export based on options
     */
    private function getCommitteesToExport()
    {
        if ($this->option('all')) {
            return Committee::orderBy('name')->get();
        }
        
        $committeeIds = $this->option('committee');
        
        if (!empty($committeeIds)) {
            return Committee::whereIn('committee_id', $committeeIds)->get();
        }
        
        // Interactive selection
        $committees = Committee::orderBy('name')->get();
        
        if ($committees->isEmpty()) {
            error('No committees found. Please run committees:scrape first.');
            return collect();
        }
        
        // Ask for single or multiple selection
        if (confirm('Do you want to select multiple committees?', true)) {
            $options = [];
            foreach ($committees as $committee) {
                $options[$committee->committee_id] = $committee->name;
            }
            
            $selected = multiselect(
                label: 'Select committees to export:',
                options: $options,
                required: true
            );
            
            return Committee::whereIn('committee_id', $selected)->get();
        } else {
            $options = [];
            foreach ($committees as $committee) {
                $options[$committee->committee_id] = $committee->name;
            }
            
            $selected = select(
                label: 'Select a committee to export:',
                options: $options
            );
            
            return Committee::where('committee_id', $selected)->get();
        }
    }
    
    /**
     * Get transcripts for a committee
     */
    private function getCommitteeTranscripts(Committee $committee, $fromDate = null, $toDate = null)
    {
        $query = Transcript::where('committee_id', $committee->committee_id)
            ->whereNotNull('content_html')
            ->orderBy('transcript_date', 'desc');
        
        if ($fromDate) {
            $query->where('transcript_date', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->where('transcript_date', '<=', $toDate);
        }
        
        if ($this->option('include-analysis')) {
            $query->with('billAnalyses');
        }
        
        return $query->get();
    }
    
    /**
     * Prepare output directory
     */
    private function prepareOutputDirectory(): string
    {
        $dirName = $this->option('output') ?? 'transcript_exports_' . Carbon::now()->format('Y-m-d_His');
        $outputDir = storage_path('app/' . $dirName);
        
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }
        
        return $outputDir;
    }
    
    /**
     * Export transcripts as separate files
     */
    private function exportSeparateFiles($transcripts, Committee $committee, string $outputDir, string $format): int
    {
        $committeeDir = $outputDir . '/' . $this->sanitizeFilename($committee->name);
        
        if (!File::exists($committeeDir)) {
            File::makeDirectory($committeeDir, 0755, true);
        }
        
        $exported = 0;
        
        foreach ($transcripts as $transcript) {
            $filename = $this->generateTranscriptFilename($transcript, $format);
            $filepath = $committeeDir . '/' . $filename;
            
            $content = $this->formatTranscriptContent($transcript, $format);
            
            File::put($filepath, $content);
            $exported++;
        }
        
        return $exported;
    }
    
    /**
     * Export transcripts as combined file
     */
    private function exportCombinedFile($transcripts, Committee $committee, string $outputDir, string $format): int
    {
        $filename = $this->sanitizeFilename($committee->name) . '_transcripts.' . $format;
        $filepath = $outputDir . '/' . $filename;
        
        $content = $this->formatCombinedContent($transcripts, $committee, $format);
        
        File::put($filepath, $content);
        
        return $transcripts->count();
    }
    
    /**
     * Format transcript content based on format
     */
    private function formatTranscriptContent(Transcript $transcript, string $format): string
    {
        switch ($format) {
            case 'json':
                return $this->formatAsJson($transcript);
            
            case 'csv':
                return $this->formatAsCsv(collect([$transcript]));
            
            case 'html':
                return $this->formatAsHtml($transcript);
            
            case 'txt':
            default:
                return $this->formatAsText($transcript);
        }
    }
    
    /**
     * Format combined content based on format
     */
    private function formatCombinedContent($transcripts, Committee $committee, string $format): string
    {
        switch ($format) {
            case 'json':
                return json_encode([
                    'committee' => $committee->name,
                    'export_date' => Carbon::now()->toIso8601String(),
                    'total_transcripts' => $transcripts->count(),
                    'transcripts' => $transcripts->map(fn($t) => $this->transcriptToArray($t))
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            case 'csv':
                return $this->formatAsCsv($transcripts);
            
            case 'html':
                return $this->formatAsHtmlDocument($transcripts, $committee);
            
            case 'txt':
            default:
                return $this->formatAsTextDocument($transcripts, $committee);
        }
    }
    
    /**
     * Format transcript as JSON
     */
    private function formatAsJson(Transcript $transcript): string
    {
        return json_encode($this->transcriptToArray($transcript), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Convert transcript to array
     */
    private function transcriptToArray(Transcript $transcript): array
    {
        $data = [
            'id' => $transcript->transcript_id,
            'date' => $transcript->transcript_date?->format('Y-m-d'),
            'type' => $transcript->type,
            'word_count' => $transcript->word_count,
        ];
        
        if ($this->option('include-metadata')) {
            $data['metadata'] = $transcript->metadata;
        }
        
        // Clean HTML content
        $data['content'] = $this->cleanHtmlContent($transcript->content_html);
        
        if ($this->option('include-analysis') && $transcript->billAnalyses) {
            $data['analyses'] = $transcript->billAnalyses->map(fn($a) => [
                'bill' => $a->bill_identifier,
                'proposer' => $a->proposer_name,
                'type' => $a->amendment_type,
                'status' => $a->status,
                'confidence' => $a->confidence_score,
                'summary' => $a->summary,
            ])->toArray();
        }
        
        return $data;
    }
    
    /**
     * Format transcripts as CSV
     */
    private function formatAsCsv($transcripts): string
    {
        $output = chr(0xEF).chr(0xBB).chr(0xBF); // UTF-8 BOM
        
        // Headers
        $headers = ['ID', 'Date', 'Type', 'Word Count', 'Content Preview'];
        if ($this->option('include-analysis')) {
            $headers[] = 'Analyses Count';
        }
        
        $output .= implode(',', array_map(fn($h) => '"' . $h . '"', $headers)) . "\n";
        
        // Data
        foreach ($transcripts as $transcript) {
            $row = [
                $transcript->transcript_id,
                $transcript->transcript_date?->format('Y-m-d') ?? '',
                $transcript->type ?? '',
                $transcript->word_count ?? 0,
                '"' . str_replace('"', '""', Str::limit($this->cleanHtmlContent($transcript->content_html), 500)) . '"',
            ];
            
            if ($this->option('include-analysis')) {
                $row[] = $transcript->billAnalyses ? $transcript->billAnalyses->count() : 0;
            }
            
            $output .= implode(',', $row) . "\n";
        }
        
        return $output;
    }
    
    /**
     * Format transcript as HTML
     */
    private function formatAsHtml(Transcript $transcript): string
    {
        $html = "<!DOCTYPE html>\n<html lang=\"bg\">\n<head>\n";
        $html .= "<meta charset=\"UTF-8\">\n";
        $html .= "<title>Transcript - " . $transcript->transcript_date?->format('Y-m-d') . "</title>\n";
        $html .= "<style>body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }</style>\n";
        $html .= "</head>\n<body>\n";
        
        $html .= "<h1>Transcript from " . $transcript->transcript_date?->format('Y-m-d') . "</h1>\n";
        $html .= "<p><strong>Type:</strong> {$transcript->type}</p>\n";
        $html .= "<p><strong>Word Count:</strong> " . number_format($transcript->word_count ?? 0) . "</p>\n";
        
        if ($this->option('include-analysis') && $transcript->billAnalyses && $transcript->billAnalyses->isNotEmpty()) {
            $html .= "<h2>Bill Analyses</h2>\n<ul>\n";
            foreach ($transcript->billAnalyses as $analysis) {
                $html .= "<li>{$analysis->bill_identifier} - {$analysis->proposer_name} ({$analysis->status})</li>\n";
            }
            $html .= "</ul>\n";
        }
        
        $html .= "<hr>\n";
        $html .= $transcript->content_html ?? '';
        $html .= "\n</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Format transcript as text
     */
    private function formatAsText(Transcript $transcript): string
    {
        $text = "=" . str_repeat("=", 70) . "\n";
        $text .= "TRANSCRIPT FROM " . ($transcript->transcript_date?->format('Y-m-d') ?? 'N/A') . "\n";
        $text .= "=" . str_repeat("=", 70) . "\n\n";
        
        $text .= "Type: {$transcript->type}\n";
        $text .= "Word Count: " . number_format($transcript->word_count ?? 0) . "\n";
        
        if ($this->option('include-metadata') && $transcript->metadata) {
            $text .= "\nMetadata:\n";
            $text .= json_encode($transcript->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        if ($this->option('include-analysis') && $transcript->billAnalyses && $transcript->billAnalyses->isNotEmpty()) {
            $text .= "\nBill Analyses:\n";
            foreach ($transcript->billAnalyses as $analysis) {
                $text .= "- {$analysis->bill_identifier} by {$analysis->proposer_name} ({$analysis->status})\n";
                if ($analysis->summary) {
                    $text .= "  Summary: {$analysis->summary}\n";
                }
            }
        }
        
        $text .= "\n" . str_repeat("-", 70) . "\n";
        $text .= "CONTENT:\n";
        $text .= str_repeat("-", 70) . "\n\n";
        
        $text .= $this->cleanHtmlContent($transcript->content_html);
        
        return $text;
    }
    
    /**
     * Format as HTML document
     */
    private function formatAsHtmlDocument($transcripts, Committee $committee): string
    {
        $html = "<!DOCTYPE html>\n<html lang=\"bg\">\n<head>\n";
        $html .= "<meta charset=\"UTF-8\">\n";
        $html .= "<title>Transcripts - {$committee->name}</title>\n";
        $html .= "<style>\n";
        $html .= "body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }\n";
        $html .= ".transcript { border: 1px solid #ddd; padding: 20px; margin: 20px 0; }\n";
        $html .= ".metadata { background: #f5f5f5; padding: 10px; margin: 10px 0; }\n";
        $html .= "</style>\n";
        $html .= "</head>\n<body>\n";
        
        $html .= "<h1>Transcripts - {$committee->name}</h1>\n";
        $html .= "<p>Total: {$transcripts->count()} transcripts</p>\n";
        $html .= "<p>Export Date: " . Carbon::now()->format('Y-m-d H:i:s') . "</p>\n";
        
        foreach ($transcripts as $transcript) {
            $html .= "<div class=\"transcript\">\n";
            $html .= "<h2>" . $transcript->transcript_date?->format('Y-m-d') . "</h2>\n";
            $html .= "<div class=\"metadata\">\n";
            $html .= "<strong>Type:</strong> {$transcript->type}<br>\n";
            $html .= "<strong>Words:</strong> " . number_format($transcript->word_count ?? 0) . "\n";
            $html .= "</div>\n";
            $html .= $transcript->content_html ?? '';
            $html .= "</div>\n";
        }
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Format as text document
     */
    private function formatAsTextDocument($transcripts, Committee $committee): string
    {
        $text = "TRANSCRIPTS EXPORT - {$committee->name}\n";
        $text .= "=" . str_repeat("=", 70) . "\n";
        $text .= "Export Date: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        $text .= "Total Transcripts: {$transcripts->count()}\n";
        $text .= "=" . str_repeat("=", 70) . "\n\n";
        
        foreach ($transcripts as $index => $transcript) {
            $text .= "\n" . str_repeat("-", 70) . "\n";
            $text .= "TRANSCRIPT #" . ($index + 1) . " - " . $transcript->transcript_date?->format('Y-m-d') . "\n";
            $text .= str_repeat("-", 70) . "\n";
            $text .= "Type: {$transcript->type}\n";
            $text .= "Words: " . number_format($transcript->word_count ?? 0) . "\n\n";
            $text .= $this->cleanHtmlContent($transcript->content_html) . "\n";
        }
        
        return $text;
    }
    
    /**
     * Clean HTML content
     */
    private function cleanHtmlContent($html): string
    {
        if (!$html) {
            return '';
        }
        
        // Remove script and style tags
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        
        // Convert to text
        $text = strip_tags($html);
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Generate transcript filename
     */
    private function generateTranscriptFilename(Transcript $transcript, string $format): string
    {
        $date = $transcript->transcript_date?->format('Y-m-d') ?? 'unknown';
        $id = $transcript->transcript_id;
        $type = $this->sanitizeFilename($transcript->type ?? 'transcript');
        
        return "transcript_{$date}_{$id}_{$type}.{$format}";
    }
    
    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $filename): string
    {
        // Use Laravel's Str::slug for better handling of special characters
        // This will transliterate Cyrillic to Latin and handle special characters
        $filename = Str::slug($filename, '_');
        
        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'export_' . time();
        }
        
        return $filename;
    }
    
    /**
     * Open directory in file manager
     */
    private function openDirectory(string $path): void
    {
        $os = PHP_OS_FAMILY;
        
        if ($os === 'Darwin') {
            exec("open '{$path}'");
        } elseif ($os === 'Windows') {
            exec("explorer '{$path}'");
        } elseif ($os === 'Linux') {
            exec("xdg-open '{$path}'");
        }
    }
}