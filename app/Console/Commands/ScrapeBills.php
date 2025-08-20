<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\Committee;
use App\Services\PdfTextExtractorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ScrapeBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:scrape {--committee-id=} {--all-committees} {--detailed : Fetch detailed information and PDF text} {--pdf-only : Only download PDFs for existing bills}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape bills from parliament.bg with optional detailed information and PDF text extraction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting bills scraping...');

        // Handle PDF-only mode
        if ($this->option('pdf-only')) {
            return $this->handlePdfOnlyMode();
        }

        $committees = $this->getCommitteesToScrape();
        
        if ($committees->isEmpty()) {
            $this->warn('No committees found. Run committees:scrape first or specify a valid committee ID.');
            return 1;
        }

        $this->info("Found {$committees->count()} committees to scrape bills for");

        $totalBills = 0;
        $newBills = 0;
        $detailedBills = 0;

        foreach ($committees as $committee) {
            $this->info("Processing committee: {$committee->name} (ID: {$committee->committee_id})");
            
            $bills = $this->scrapeBillsForCommittee($committee->committee_id);
            
            if (empty($bills)) {
                $this->warn("No bills found for committee {$committee->name}");
                continue;
            }

            $committeeNewBills = 0;
            $committeeDetailedBills = 0;
            
            foreach ($bills as $billData) {
                $bill = $this->saveBillToDatabase($billData, $committee->committee_id);
                if ($bill) {
                    if ($bill->wasRecentlyCreated) {
                        $committeeNewBills++;
                    }
                    
                    // Fetch detailed information if requested
                    if ($this->option('detailed') && !$bill->is_detailed) {
                        if ($this->fetchDetailedBillInfo($bill)) {
                            $committeeDetailedBills++;
                        }
                    }
                }
                $totalBills++;
            }

            $this->info("Committee {$committee->name}: {$committeeNewBills} new bills, {$committeeDetailedBills} detailed");
            $newBills += $committeeNewBills;
            $detailedBills += $committeeDetailedBills;
        }

        $this->info("Bills scraping completed!");
        $this->info("Total bills processed: {$totalBills}");
        $this->info("New bills added: {$newBills}");
        if ($this->option('detailed')) {
            $this->info("Bills with detailed info: {$detailedBills}");
        }

        return 0;
    }

    /**
     * Get committees to scrape based on options
     */
    private function getCommitteesToScrape()
    {
        if ($this->option('committee-id')) {
            return Committee::where('committee_id', $this->option('committee-id'))->get();
        }

        if ($this->option('all-committees')) {
            return Committee::orderBy('name')->get();
        }

        // Default to transport committee (3613) from the URL provided
        return Committee::where('committee_id', 3613)->get();
    }

    /**
     * Scrape bills for a specific committee
     */
    private function scrapeBillsForCommittee($committeeId)
    {
        $url = "https://www.parliament.bg/api/v1/com-acts/bg/{$committeeId}/1";
        
        $this->info("Fetching bills from: {$url}");

        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                $this->error("Failed to fetch bills for committee {$committeeId}");
                return [];
            }

            $data = $response->json();

            if (!is_array($data)) {
                $this->warn("Invalid response format for committee {$committeeId}");
                return [];
            }

            return $data;

        } catch (\Exception $e) {
            $this->error("Error fetching bills for committee {$committeeId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Save bill to database
     */
    private function saveBillToDatabase($billData, $committeeId)
    {
        try {
            // Check if bill already exists
            $existingBill = Bill::where('bill_id', $billData['L_Act_id'])->first();
            
            if ($existingBill) {
                // Update existing bill with committee if not set
                if (!$existingBill->committee_id && $committeeId) {
                    $existingBill->update(['committee_id' => $committeeId]);
                }
                return $existingBill; // Return existing bill
            }

            // Create new bill
            $bill = Bill::create([
                'bill_id' => $billData['L_Act_id'],
                'title' => $billData['L_ActL_title'],
                'sign' => $billData['L_Act_sign'] ?? null,
                'bill_date' => Carbon::parse($billData['L_Act_date']),
                'path' => $billData['path'] ?? null,
                'committee_id' => $committeeId,
            ]);

            return $bill; // Return new bill

        } catch (\Exception $e) {
            $this->error("Error saving bill {$billData['L_Act_id']}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle PDF-only mode - download PDFs for existing bills
     */
    private function handlePdfOnlyMode()
    {
        $this->info('PDF-only mode: Processing existing bills...');
        
        $bills = Bill::whereNotNull('sign')
            ->where('is_detailed', false)
            ->orWhereNull('extracted_text')
            ->get();
            
        if ($bills->isEmpty()) {
            $this->info('No bills found that need PDF processing.');
            return 0;
        }
        
        $processed = 0;
        $pdfExtractor = new PdfTextExtractorService();
        
        foreach ($bills as $bill) {
            if ($bill->sign && $this->processBillPdf($bill, $pdfExtractor)) {
                $processed++;
            }
        }
        
        $this->info("PDF processing completed! Processed {$processed} bills.");
        return 0;
    }

    /**
     * Fetch detailed bill information from individual bill API
     */
    private function fetchDetailedBillInfo(Bill $bill): bool
    {
        try {
            $url = "https://www.parliament.bg/api/v1/bill/{$bill->bill_id}";
            $this->info("Fetching detailed info for bill {$bill->bill_id} from {$url}");
            
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                $this->warn("Failed to fetch detailed info for bill {$bill->bill_id}");
                return false;
            }
            
            $data = $response->json();
            
            // Parse the detailed information
            $detailedData = $this->parseDetailedBillData($data);
            
            // Update bill with detailed information
            $bill->update($detailedData);
            
            // Download and process PDF if signature is available
            if ($bill->fresh()->signature) {
                $pdfExtractor = new PdfTextExtractorService();
                $this->processBillPdf($bill->fresh(), $pdfExtractor);
            }
            
            $this->info("✓ Detailed info updated for bill {$bill->bill_id}");
            return true;
            
        } catch (\Exception $e) {
            $this->error("Error fetching detailed info for bill {$bill->bill_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Parse detailed bill data from API response
     */
    private function parseDetailedBillData(array $data): array
    {
        $parsed = [
            'is_detailed' => true,
        ];
        
        // Extract signature (L_Act_sign)
        if (isset($data['L_Act_sign'])) {
            $parsed['signature'] = $data['L_Act_sign'];
        }
        
        // Extract session information (L_SesL_value)
        if (isset($data['L_SesL_value'])) {
            $parsed['session'] = $data['L_SesL_value'];
        }
        
        // Extract submitters from imp_list
        if (isset($data['imp_list']) && is_array($data['imp_list'])) {
            $submitters = [];
            foreach ($data['imp_list'] as $submitter) {
                $name = trim(
                    ($submitter['A_ns_MPL_Name1'] ?? '') . ' ' . 
                    ($submitter['A_ns_MPL_Name2'] ?? '') . ' ' . 
                    ($submitter['A_ns_MPL_Name3'] ?? '')
                );
                if ($name && $name !== '  ') {
                    $submitters[] = $name;
                }
            }
            if (!empty($submitters)) {
                $parsed['submitters'] = $submitters;
            }
        }
        
        // Extract committee assignments from dist_list
        if (isset($data['dist_list']) && is_array($data['dist_list'])) {
            $committees = [];
            foreach ($data['dist_list'] as $committee) {
                $committeeName = $committee['A_ns_CL_value'] ?? '';
                $role = $committee['L_Act_DTL_value'] ?? '';
                
                if ($committeeName) {
                    $committeeEntry = $committeeName;
                    if ($role) {
                        $committeeEntry .= ' (' . $role . ')';
                    }
                    $committees[] = $committeeEntry;
                }
            }
            if (!empty($committees)) {
                $parsed['committees'] = $committees;
            }
        }
        
        // Extract file information
        if (isset($data['file_list']) && is_array($data['file_list']) && !empty($data['file_list'])) {
            $firstFile = $data['file_list'][0];
            if (isset($firstFile['FILENAME'])) {
                $filename = $firstFile['FILENAME'];
                $parsed['pdf_filename'] = $filename;
                
                // Generate PDF URL based on actual API structure
                if (isset($data['A_ns_folder'])) {
                    $folder = $data['A_ns_folder'];
                    $parsed['pdf_url'] = "https://www.parliament.bg/bills/{$folder}/{$filename}";
                } elseif (isset($parsed['signature'])) {
                    // Fallback to original method
                    $parsed['pdf_url'] = PdfTextExtractorService::generatePdfUrl($parsed['signature']);
                }
            }
        }
        
        // Extract additional metadata
        if (isset($data['withdrawn']) && $data['withdrawn']) {
            $parsed['is_withdrawn'] = true;
        }
        
        return $parsed;
    }

    /**
     * Process bill PDF - download and extract text
     */
    private function processBillPdf(Bill $bill, PdfTextExtractorService $pdfExtractor): bool
    {
        if (!$bill->pdf_url) {
            return false;
        }
        
        $this->info("Processing PDF for bill {$bill->bill_id}...");
        
        $result = $pdfExtractor->downloadAndExtractText(
            $bill->pdf_url,
            $bill->pdf_filename
        );
        
        if ($result['success']) {
            $bill->update([
                'extracted_text' => $result['extracted_text'],
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count'],
                'text_language' => $result['text_language'],
                'pdf_downloaded_at' => $result['downloaded_at'],
            ]);
            
            $this->info("✓ PDF processed for bill {$bill->bill_id}");
            return true;
        } else {
            $this->warn("✗ PDF processing failed for bill {$bill->bill_id}: " . $result['error']);
            return false;
        }
    }
}
