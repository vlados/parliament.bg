<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\Committee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ScheduledBillsCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:check-new {--days=7} {--committee-id=} {--notify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for new bills and report changes (designed for scheduled execution)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for new bills...');

        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $committees = $this->getCommitteesToCheck();
        
        if ($committees->isEmpty()) {
            $this->warn('No committees found. Run committees:scrape first.');
            return 1;
        }

        $newBillsFound = [];
        $totalChecked = 0;

        foreach ($committees as $committee) {
            $bills = $this->checkCommitteeForNewBills($committee, $cutoffDate);
            $totalChecked += count($bills['all']);
            
            if (!empty($bills['new'])) {
                $newBillsFound[$committee->name] = $bills['new'];
                $this->info("✓ Committee '{$committee->name}': " . count($bills['new']) . " new bills");
            } else {
                $this->line("- Committee '{$committee->name}': No new bills");
            }
        }

        $this->reportResults($newBillsFound, $totalChecked, $days);

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
     * Check a committee for new bills
     */
    private function checkCommitteeForNewBills($committee, $cutoffDate)
    {
        $url = "https://www.parliament.bg/api/v1/com-acts/bg/{$committee->committee_id}/1";
        
        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                $this->warn("Failed to fetch bills for committee {$committee->name}");
                return ['all' => [], 'new' => []];
            }

            $bills = $response->json();

            if (!is_array($bills)) {
                return ['all' => [], 'new' => []];
            }

            $newBills = [];

            foreach ($bills as $billData) {
                $billDate = Carbon::parse($billData['L_Act_date']);
                
                // Only check bills from the specified period
                if ($billDate->lt($cutoffDate)) {
                    continue;
                }

                // Check if bill exists in database
                $existingBill = Bill::where('bill_id', $billData['L_Act_id'])->first();
                
                if (!$existingBill) {
                    // Save new bill
                    $bill = Bill::create([
                        'bill_id' => $billData['L_Act_id'],
                        'title' => $billData['L_ActL_title'],
                        'sign' => $billData['L_Act_sign'] ?? null,
                        'bill_date' => $billDate,
                        'path' => $billData['path'] ?? null,
                        'committee_id' => $committee->committee_id,
                    ]);

                    $newBills[] = [
                        'id' => $bill->bill_id,
                        'title' => $bill->title,
                        'sign' => $bill->sign,
                        'date' => $bill->bill_date->format('Y-m-d'),
                    ];
                }
            }

            return ['all' => $bills, 'new' => $newBills];

        } catch (\Exception $e) {
            $this->error("Error checking committee {$committee->name}: " . $e->getMessage());
            return ['all' => [], 'new' => []];
        }
    }

    /**
     * Report the results
     */
    private function reportResults($newBillsFound, $totalChecked, $days)
    {
        $totalNewBills = array_sum(array_map('count', $newBillsFound));

        $this->newLine();
        $this->info("=== BILLS CHECK SUMMARY ===");
        $this->info("Period: Last {$days} days");
        $this->info("Total bills checked: {$totalChecked}");
        $this->info("New bills found: {$totalNewBills}");

        if (!empty($newBillsFound)) {
            $this->newLine();
            $this->info("NEW BILLS DETAILS:");
            
            foreach ($newBillsFound as $committeeName => $bills) {
                $this->newLine();
                $this->line("<fg=cyan>Committee: {$committeeName}</>");
                
                foreach ($bills as $bill) {
                    $this->line("  • [{$bill['sign']}] {$bill['title']}");
                    $this->line("    Date: {$bill['date']} | ID: {$bill['id']}");
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