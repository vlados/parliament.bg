<?php

namespace App\Console\Commands;

use App\Models\Committee;
use Illuminate\Console\Command;

class ExportCommitteesCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'committees:export {--file=committees.csv} {--members-file=committee_members.csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export committees and their members to CSV files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting committees CSV export...');

        $committees = Committee::orderBy('name')->get();

        if ($committees->isEmpty()) {
            $this->warn('No committees found in database. Run committees:scrape first.');
            return 1;
        }

        $committeeFile = $this->option('file');
        $membersFile = $this->option('members-file');
        
        $committeeFilepath = storage_path('app/' . $committeeFile);
        $membersFilepath = storage_path('app/' . $membersFile);

        $this->info("Found {$committees->count()} committees to export");

        // Export committees
        $this->exportCommittees($committees, $committeeFilepath);
        
        // Export committee members
        $this->exportCommitteeMembers($committees, $membersFilepath);

        $this->newLine();
        $this->info("Committees CSV export completed successfully!");
        $this->info("Committees file: {$committeeFilepath}");
        $this->info("Members file: {$membersFilepath}");

        return 0;
    }

    /**
     * Export committees to CSV
     */
    private function exportCommittees($committees, $filepath)
    {
        $handle = fopen($filepath, 'w');

        // Add UTF-8 BOM for proper encoding in Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Write CSV header
        fputcsv($handle, [
            'ID',
            'Име на комисията',
            'Тип комисия ID',
            'Брой активни членове',
            'Дата от',
            'Дата до',
            'Е-мейл',
            'Стая',
            'Телефон',
            'Създадена на',
            'Обновена на'
        ]);

        // Write committee data
        $bar = $this->output->createProgressBar($committees->count());
        $bar->setMessage('Exporting committees...');
        $bar->start();

        foreach ($committees as $committee) {
            fputcsv($handle, [
                $committee->committee_id,
                $committee->name,
                $committee->committee_type_id,
                $committee->active_count,
                $committee->date_from?->format('Y-m-d'),
                $committee->date_to?->format('Y-m-d'),
                $committee->email,
                $committee->room,
                $committee->phone,
                $committee->created_at?->format('Y-m-d H:i:s'),
                $committee->updated_at?->format('Y-m-d H:i:s'),
            ]);
            
            $bar->advance();
        }

        $bar->finish();
        fclose($handle);
        $this->newLine();
    }

    /**
     * Export committee members to CSV
     */
    private function exportCommitteeMembers($committees, $filepath)
    {
        $handle = fopen($filepath, 'w');

        // Add UTF-8 BOM for proper encoding in Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Write CSV header
        fputcsv($handle, [
            'Комисия ID',
            'Име на комисията',
            'Член ID',
            'Име на члена',
            'Позиция',
            'Дата от',
            'Дата до',
            'Политическа сила',
            'Изборен район',
            'Е-мейл члена'
        ]);

        // Count total members for progress bar
        $totalMembers = 0;
        foreach ($committees as $committee) {
            $totalMembers += $committee->parliamentMembers()->count();
        }

        $bar = $this->output->createProgressBar($totalMembers);
        $bar->setMessage('Exporting committee members...');
        $bar->start();

        // Write committee member data
        foreach ($committees as $committee) {
            $members = $committee->parliamentMembers()->orderBy('full_name')->get();
            
            foreach ($members as $member) {
                fputcsv($handle, [
                    $committee->committee_id,
                    $committee->name,
                    $member->member_id,
                    $member->full_name,
                    $member->pivot->position,
                    $member->pivot->date_from,
                    $member->pivot->date_to,
                    $member->political_party,
                    $member->electoral_district,
                    $member->email,
                ]);
                
                $bar->advance();
            }
        }

        $bar->finish();
        fclose($handle);
        $this->newLine();
    }
}
