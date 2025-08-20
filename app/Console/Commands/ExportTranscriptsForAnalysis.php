<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Models\Transcript;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExportTranscriptsForAnalysis extends Command
{
    protected $signature = 'transcripts:export-for-analysis 
                            {--committee= : Committee ID to export transcripts for}
                            {--output= : Output directory (default: storage/app/exports/transcripts)}
                            {--format=txt : Format (txt or md)}
                            {--since= : Export transcripts since date (YYYY-MM-DD)}
                            {--all : Export all transcripts from all committees}';

    protected $description = 'Export transcripts as separate text files for AI analysis';

    public function handle()
    {
        $this->info('🚀 Starting transcript export for analysis...');

        // Get parameters
        $committeeId = $this->option('committee');
        $outputDir = $this->option('output') ?: storage_path('app/exports/transcripts');
        $format = $this->option('format');
        $since = $this->option('since');
        $exportAll = $this->option('all');

        // Validate format
        if (!in_array($format, ['txt', 'md'])) {
            $this->error('Format must be either "txt" or "md"');
            return 1;
        }

        // Build query
        $query = Transcript::with('committee')->orderBy('transcript_date');

        if ($exportAll) {
            $this->info('Exporting transcripts from all committees...');
        } elseif ($committeeId) {
            $committee = Committee::where('committee_id', $committeeId)->first();
            if (!$committee) {
                $this->error("Committee with ID {$committeeId} not found");
                return 1;
            }
            $query->where('committee_id', $committeeId);
            $this->info("Exporting transcripts for committee: {$committee->name}");
        } else {
            $this->error('Please specify --committee=ID or use --all');
            return 1;
        }

        if ($since) {
            try {
                $sinceDate = Carbon::parse($since);
                $query->where('transcript_date', '>=', $sinceDate);
                $this->info("Filtering transcripts since: {$sinceDate->format('Y-m-d')}");
            } catch (\Exception $e) {
                $this->error("Invalid date format: {$since}");
                return 1;
            }
        }

        $transcripts = $query->get();

        if ($transcripts->isEmpty()) {
            $this->warn('No transcripts found to export.');
            return 0;
        }

        // Create output directory
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Create committee-specific subdirectory if needed
        if ($committeeId && !$exportAll) {
            $committee = Committee::where('committee_id', $committeeId)->first();
            $committeeName = $this->sanitizeFilename($committee->name);
            $outputDir = $outputDir . '/' . $committeeName;
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
        }

        $exported = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($transcripts->count());
        $progressBar->start();

        foreach ($transcripts as $transcript) {
            try {
                $filename = $this->generateFilename($transcript, $format);
                $filepath = $outputDir . '/' . $filename;
                
                $content = $this->generateContent($transcript, $format);
                
                file_put_contents($filepath, $content);
                $exported++;
                
            } catch (\Exception $e) {
                $this->error("\nError exporting transcript {$transcript->transcript_id}: " . $e->getMessage());
                $errors++;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();

        // Generate summary file
        $this->generateSummaryFile($transcripts, $outputDir, $format);

        $this->info("\n✅ Export completed!");
        $this->info("📁 Output directory: {$outputDir}");
        $this->info("📄 Files exported: {$exported}");
        if ($errors > 0) {
            $this->warn("⚠️  Errors: {$errors}");
        }

        return 0;
    }

    private function generateFilename(Transcript $transcript, string $format): string
    {
        $date = $transcript->transcript_date ? $transcript->transcript_date->format('Y-m-d') : 'unknown-date';
        $committee = $this->sanitizeFilename($transcript->committee->name ?? 'unknown');
        $type = $transcript->type === 'Пълен протокол' ? 'full' : 'abbreviated';
        
        return "{$date}_{$committee}_{$type}_transcript_{$transcript->transcript_id}.{$format}";
    }

    private function generateContent(Transcript $transcript, string $format): string
    {
        if ($format === 'md') {
            return $this->generateMarkdownContent($transcript);
        } else {
            return $this->generateTextContent($transcript);
        }
    }

    private function generateMarkdownContent(Transcript $transcript): string
    {
        $content = "# Parliamentary Committee Transcript\n\n";
        
        // Metadata section
        $content .= "## Metadata\n\n";
        $content .= "- **Transcript ID**: {$transcript->transcript_id}\n";
        $content .= "- **Committee**: " . ($transcript->committee->name ?? 'Unknown') . "\n";
        $content .= "- **Date**: " . ($transcript->transcript_date ? $transcript->transcript_date->format('Y-m-d') : 'Unknown') . "\n";
        $content .= "- **Type**: {$transcript->type}\n";
        
        if ($transcript->word_count) {
            $content .= "- **Word Count**: " . number_format($transcript->word_count) . "\n";
        }
        
        if ($transcript->character_count) {
            $content .= "- **Character Count**: " . number_format($transcript->character_count) . "\n";
        }
        
        $content .= "\n---\n\n";
        
        // Content section
        $content .= "## Transcript Content\n\n";
        
        if ($transcript->content_text) {
            $content .= $transcript->content_text;
        } elseif ($transcript->content_html) {
            // Convert HTML to plain text
            $plainText = strip_tags(html_entity_decode($transcript->content_html));
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $content .= trim($plainText);
        } else {
            $content .= "*No content available*";
        }
        
        return $content;
    }

    private function generateTextContent(Transcript $transcript): string
    {
        $content = "PARLIAMENTARY COMMITTEE TRANSCRIPT\n";
        $content .= str_repeat("=", 50) . "\n\n";
        
        // Metadata
        $content .= "Transcript ID: {$transcript->transcript_id}\n";
        $content .= "Committee: " . ($transcript->committee->name ?? 'Unknown') . "\n";
        $content .= "Date: " . ($transcript->transcript_date ? $transcript->transcript_date->format('Y-m-d') : 'Unknown') . "\n";
        $content .= "Type: {$transcript->type}\n";
        
        if ($transcript->word_count) {
            $content .= "Word Count: " . number_format($transcript->word_count) . "\n";
        }
        
        $content .= "\n" . str_repeat("-", 50) . "\n\n";
        
        // Content
        if ($transcript->content_text) {
            $content .= $transcript->content_text;
        } elseif ($transcript->content_html) {
            // Convert HTML to plain text
            $plainText = strip_tags(html_entity_decode($transcript->content_html));
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $content .= trim($plainText);
        } else {
            $content .= "No content available";
        }
        
        return $content;
    }

    private function generateSummaryFile(object $transcripts, string $outputDir, string $format): void
    {
        $summaryFile = $outputDir . "/00_SUMMARY.{$format}";
        
        if ($format === 'md') {
            $content = "# Transcript Export Summary\n\n";
            $content .= "Export generated on: " . now()->format('Y-m-d H:i:s') . "\n\n";
            $content .= "## Statistics\n\n";
            $content .= "- **Total transcripts**: " . $transcripts->count() . "\n";
            $content .= "- **Date range**: " . 
                       $transcripts->min('transcript_date') . " to " . 
                       $transcripts->max('transcript_date') . "\n";
            $content .= "- **Committees**: " . $transcripts->pluck('committee.name')->unique()->count() . "\n\n";
            
            $content .= "## Files Included\n\n";
            foreach ($transcripts as $transcript) {
                $filename = $this->generateFilename($transcript, $format);
                $content .= "- `{$filename}` - " . ($transcript->committee->name ?? 'Unknown Committee') . "\n";
            }
        } else {
            $content = "TRANSCRIPT EXPORT SUMMARY\n";
            $content .= str_repeat("=", 50) . "\n\n";
            $content .= "Export generated on: " . now()->format('Y-m-d H:i:s') . "\n\n";
            $content .= "Total transcripts: " . $transcripts->count() . "\n";
            $content .= "Date range: " . 
                       $transcripts->min('transcript_date') . " to " . 
                       $transcripts->max('transcript_date') . "\n";
            $content .= "Committees: " . $transcripts->pluck('committee.name')->unique()->count() . "\n\n";
            
            $content .= "FILES INCLUDED:\n";
            $content .= str_repeat("-", 30) . "\n";
            foreach ($transcripts as $transcript) {
                $filename = $this->generateFilename($transcript, $format);
                $content .= $filename . " - " . ($transcript->committee->name ?? 'Unknown Committee') . "\n";
            }
        }
        
        file_put_contents($summaryFile, $content);
    }

    private function sanitizeFilename(string $name): string
    {
        // Convert Bulgarian Cyrillic to Latin for safe filenames
        $name = Str::ascii($name);
        
        // Replace spaces and special characters
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name);
        
        // Remove multiple underscores
        $name = preg_replace('/_+/', '_', $name);
        
        // Trim underscores from ends
        $name = trim($name, '_');
        
        // Limit length
        return Str::limit($name, 50, '');
    }
}