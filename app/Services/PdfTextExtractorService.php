<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PdfTextExtractorService
{
    /**
     * Download PDF from URL and extract text content
     */
    public function downloadAndExtractText(string $pdfUrl, string $filename): array
    {
        try {
            // Download PDF
            $response = Http::timeout(60)->get($pdfUrl);
            
            if (!$response->successful()) {
                throw new \Exception("Failed to download PDF: HTTP {$response->status()}");
            }

            // Store PDF file
            $pdfPath = "bills/pdfs/{$filename}";
            Storage::disk('local')->put($pdfPath, $response->body());
            
            // Extract text using available method
            $extractedText = $this->extractTextFromPdf(Storage::disk('local')->path($pdfPath));
            
            // Calculate text metrics
            $wordCount = str_word_count($extractedText);
            $charCount = strlen($extractedText);
            $language = $this->detectLanguage($extractedText);
            
            return [
                'success' => true,
                'pdf_path' => $pdfPath,
                'extracted_text' => $extractedText,
                'word_count' => $wordCount,
                'character_count' => $charCount,
                'text_language' => $language,
                'downloaded_at' => now(),
            ];
            
        } catch (\Exception $e) {
            Log::error("PDF download/extraction failed for {$pdfUrl}: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'extracted_text' => null,
                'word_count' => 0,
                'character_count' => 0,
            ];
        }
    }

    /**
     * Extract text from PDF file
     */
    private function extractTextFromPdf(string $pdfPath): string
    {
        // Try multiple extraction methods
        
        // Method 1: Try pdftotext if available (most reliable)
        if ($this->commandExists('pdftotext')) {
            return $this->extractWithPdftotext($pdfPath);
        }
        
        // Method 2: Try pdfinfo + basic extraction
        if ($this->commandExists('pdfinfo')) {
            return $this->extractWithBasicMethod($pdfPath);
        }
        
        // Method 3: Fallback - try to read simple PDF structure
        return $this->extractWithFallback($pdfPath);
    }

    /**
     * Extract text using pdftotext command
     */
    private function extractWithPdftotext(string $pdfPath): string
    {
        $outputPath = $pdfPath . '.txt';
        $command = "pdftotext -enc UTF-8 -layout " . escapeshellarg($pdfPath) . " " . escapeshellarg($outputPath);
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($outputPath)) {
            $text = file_get_contents($outputPath);
            unlink($outputPath); // Clean up temp file
            return $this->cleanExtractedText($text);
        }
        
        throw new \Exception("pdftotext extraction failed");
    }

    /**
     * Basic extraction method using pdfinfo
     */
    private function extractWithBasicMethod(string $pdfPath): string
    {
        // This is a simplified approach - in real implementation you might use
        // a PHP PDF library like tcpdf, fpdf, or smalot/pdfparser
        $command = "pdfinfo " . escapeshellarg($pdfPath);
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            // Return basic info if we can't extract full text
            return "PDF документ със " . count($output) . " страници. Пълен текст не може да бъде извлечен автоматично.";
        }
        
        throw new \Exception("PDF info extraction failed");
    }

    /**
     * Fallback extraction method
     */
    private function extractWithFallback(string $pdfPath): string
    {
        // Very basic fallback - just note that we have the PDF
        $fileSize = filesize($pdfPath);
        $fileSizeKB = round($fileSize / 1024, 2);
        
        return "PDF документ (размер: {$fileSizeKB} KB). Автоматично извличане на текст не е достъпно в момента. " .
               "Моля, отворете PDF файла директно за преглед на съдържанието.";
    }

    /**
     * Clean and normalize extracted text
     */
    private function cleanExtractedText(string $text): string
    {
        // Remove excessive whitespace and normalize line breaks
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        // Remove common PDF artifacts
        $text = str_replace(['', '', ''], '', $text);
        
        // Trim and ensure proper encoding
        $text = trim($text);
        
        // Ensure we have valid UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        return $text;
    }

    /**
     * Simple language detection for Bulgarian text
     */
    private function detectLanguage(string $text): string
    {
        // Simple Bulgarian language detection
        $bulgarianChars = ['а', 'б', 'в', 'г', 'д', 'е', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ю', 'я'];
        $bulgarianCount = 0;
        $totalChars = 0;
        
        $chars = mb_str_split(mb_strtolower($text));
        foreach ($chars as $char) {
            if (preg_match('/[а-я]/u', $char)) {
                $bulgarianCount++;
            }
            if (preg_match('/[a-zа-я]/u', $char)) {
                $totalChars++;
            }
        }
        
        if ($totalChars > 0 && ($bulgarianCount / $totalChars) > 0.6) {
            return 'bg';
        }
        
        return 'unknown';
    }

    /**
     * Check if a command exists on the system
     */
    private function commandExists(string $command): bool
    {
        $which = shell_exec("which $command");
        return !empty($which);
    }

    /**
     * Generate PDF URL from signature
     */
    public static function generatePdfUrl(string $signature): string
    {
        // Extract the session number from signature (e.g., "51-554-01-114" -> "51")
        $parts = explode('-', $signature);
        $session = $parts[0] ?? '51';
        
        return "https://www.parliament.bg/bills/{$session}/{$signature}.pdf";
    }
}