<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Models\ParliamentMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ScrapeCommittees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'committees:scrape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape parliament committees and their members';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting committee scraping...');

        // Clear existing committee data
        Committee::truncate();
        $this->info('Cleared existing committee data');

        // Fetch the committee list
        $committees = $this->fetchCommitteeList();
        
        if (!$committees) {
            $this->error('Failed to fetch committee list');
            return 1;
        }

        $this->info('Found ' . count($committees) . ' committees');

        // Process each committee
        $bar = $this->output->createProgressBar(count($committees));
        $bar->start();

        $savedCommittees = 0;
        $savedMemberships = 0;

        foreach ($committees as $committeeData) {
            $committeeDetails = $this->fetchCommitteeDetails($committeeData['A_ns_C_id']);
            
            if ($committeeDetails) {
                $committee = $this->saveCommitteeToDatabase($committeeDetails);
                $savedCommittees++;
                
                // Save committee members
                if (isset($committeeDetails['colListMP']) && is_array($committeeDetails['colListMP'])) {
                    $membershipCount = $this->saveCommitteeMembers($committee, $committeeDetails['colListMP']);
                    $savedMemberships += $membershipCount;
                }
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Committee scraping completed!");
        $this->info("Saved {$savedCommittees} committees and {$savedMemberships} committee memberships.");

        return 0;
    }

    /**
     * Fetch the list of committees
     */
    private function fetchCommitteeList()
    {
        try {
            $response = Http::get('https://www.parliament.bg/api/v1/coll-list/bg/3');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            $this->error('Failed to fetch committee list: ' . $response->status());
            return null;
        } catch (\Exception $e) {
            $this->error('Error fetching committee list: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch detailed information for a specific committee including members
     */
    private function fetchCommitteeDetails($committeeId)
    {
        try {
            $response = Http::get("https://www.parliament.bg/api/v1/coll-list-mp/bg/{$committeeId}/3?date=");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save committee to database
     */
    private function saveCommitteeToDatabase($committeeData)
    {
        // Use CDend or default to far future if not available
        $dateTo = $committeeData['A_ns_CDend'] ?? '9999-12-31';
        
        $committee = Committee::create([
            'committee_id' => $committeeData['A_ns_C_id'],
            'committee_type_id' => $committeeData['A_ns_CT_id'],
            'name' => $committeeData['A_ns_CL_value'],
            'active_count' => $committeeData['A_ns_C_active_count'] ?? null,
            'date_from' => Carbon::parse($committeeData['A_ns_C_date_F']),
            'date_to' => $dateTo === '9999-12-31' 
                ? Carbon::parse('9999-12-31') 
                : Carbon::parse($dateTo),
            'email' => $committeeData['A_ns_CDemail'] ?? null,
            'room' => $committeeData['A_ns_CDroom'] ?? null,
            'phone' => $committeeData['A_ns_CDphone'] ?? null,
            'rules' => $committeeData['A_ns_CDrules'] ?? null,
        ]);

        return $committee;
    }

    /**
     * Save committee members to database
     */
    private function saveCommitteeMembers($committee, $members)
    {
        $savedCount = 0;

        foreach ($members as $memberData) {
            // Find the parliament member by their ID
            $parliamentMember = ParliamentMember::where('member_id', $memberData['A_ns_MP_id'])->first();
            
            if ($parliamentMember) {
                // Attach the member to the committee with position and dates
                $committee->parliamentMembers()->attach($parliamentMember->id, [
                    'position' => $memberData['A_ns_MP_PosL_value'] ?? null,
                    'date_from' => Carbon::parse($memberData['A_ns_MSP_date_F']),
                    'date_to' => $memberData['A_ns_MSP_date_T'] === '9999-12-31' 
                        ? Carbon::parse('9999-12-31') 
                        : Carbon::parse($memberData['A_ns_MSP_date_T']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $savedCount++;
            }
        }

        return $savedCount;
    }
}
