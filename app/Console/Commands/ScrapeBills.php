<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\Committee;
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
    protected $signature = 'bills:scrape {--committee-id=} {--all-committees}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape bills from parliament.bg for specific committee or all committees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting bills scraping...');

        $committees = $this->getCommitteesToScrape();
        
        if ($committees->isEmpty()) {
            $this->warn('No committees found. Run committees:scrape first or specify a valid committee ID.');
            return 1;
        }

        $this->info("Found {$committees->count()} committees to scrape bills for");

        $totalBills = 0;
        $newBills = 0;

        foreach ($committees as $committee) {
            $this->info("Processing committee: {$committee->name} (ID: {$committee->committee_id})");
            
            $bills = $this->scrapeBillsForCommittee($committee->committee_id);
            
            if (empty($bills)) {
                $this->warn("No bills found for committee {$committee->name}");
                continue;
            }

            $committeeNewBills = 0;
            foreach ($bills as $billData) {
                if ($this->saveBillToDatabase($billData, $committee->committee_id)) {
                    $committeeNewBills++;
                }
                $totalBills++;
            }

            $this->info("Committee {$committee->name}: {$committeeNewBills} new bills out of " . count($bills) . " total");
            $newBills += $committeeNewBills;
        }

        $this->info("Bills scraping completed!");
        $this->info("Total bills processed: {$totalBills}");
        $this->info("New bills added: {$newBills}");

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
                return false; // Not a new bill
            }

            // Create new bill
            Bill::create([
                'bill_id' => $billData['L_Act_id'],
                'title' => $billData['L_ActL_title'],
                'sign' => $billData['L_Act_sign'] ?? null,
                'bill_date' => Carbon::parse($billData['L_Act_date']),
                'path' => $billData['path'] ?? null,
                'committee_id' => $committeeId,
            ]);

            return true; // New bill created

        } catch (\Exception $e) {
            $this->error("Error saving bill {$billData['L_Act_id']}: " . $e->getMessage());
            return false;
        }
    }
}
