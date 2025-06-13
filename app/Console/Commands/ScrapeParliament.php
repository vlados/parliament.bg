<?php

namespace App\Console\Commands;

use App\Models\ParliamentMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScrapeParliament extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parliament:scrape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape parliament.bg for member information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting parliament scraping...');

        // Clear existing data
        ParliamentMember::truncate();
        $this->info('Cleared existing data');

        // Fetch the member list
        $memberList = $this->fetchMemberList();
        
        if (!$memberList) {
            $this->error('Failed to fetch member list');
            return 1;
        }

        $this->info('Found ' . count($memberList) . ' members');

        // Fetch detailed information for each member
        $bar = $this->output->createProgressBar(count($memberList));
        $bar->start();

        $savedCount = 0;

        foreach ($memberList as $member) {
            $memberDetails = $this->fetchMemberDetails($member['A_ns_MP_id']);
            
            if ($memberDetails) {
                $this->saveMemberToDatabase($member, $memberDetails);
                $savedCount++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Parliament scraping completed! Saved {$savedCount} members to database.");

        return 0;
    }

    /**
     * Fetch the list of parliament members
     */
    private function fetchMemberList()
    {
        try {
            $response = Http::get('https://www.parliament.bg/api/v1/coll-list-ns/bg');
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['colListMP'] ?? [];
            }
            
            $this->error('Failed to fetch member list: ' . $response->status());
            return null;
        } catch (\Exception $e) {
            $this->error('Error fetching member list: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch detailed information for a specific member
     */
    private function fetchMemberDetails($memberId)
    {
        try {
            $response = Http::get("https://www.parliament.bg/api/v1/mp-profile/bg/{$memberId}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save member information to database
     */
    private function saveMemberToDatabase($member, $details)
    {
        $firstName = $member['A_ns_MPL_Name1'] ?? '';
        $middleName = $member['A_ns_MPL_Name2'] ?? '';
        $lastName = $member['A_ns_MPL_Name3'] ?? '';
        $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
        
        // Get profession from the details prsList array
        $profession = null;
        if (isset($details['prsList']) && is_array($details['prsList']) && !empty($details['prsList'])) {
            $profession = $details['prsList'][0]['A_ns_MP_Pr_TL_value'] ?? null;
        }
        
        ParliamentMember::create([
            'member_id' => $member['A_ns_MP_id'],
            'first_name' => $firstName ?: null,
            'middle_name' => $middleName ?: null,
            'last_name' => $lastName ?: null,
            'full_name' => $fullName ?: 'Unknown',
            'electoral_district' => $member['A_ns_Va_name'] ?? null,
            'political_party' => $member['A_ns_CL_value_short'] ?? null,
            'profession' => $profession,
            'email' => $details['A_ns_MP_Email'] ?? null,
        ]);
    }
}
