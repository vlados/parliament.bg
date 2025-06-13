<?php

namespace App\Console\Commands;

use App\Models\ParliamentMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportParliamentCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parliament:export {--file=parliament_members.csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export parliament members to CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting CSV export...');

        $members = ParliamentMember::orderBy('full_name')->get();

        if ($members->isEmpty()) {
            $this->warn('No parliament members found in database. Run parliament:scrape first.');
            return 1;
        }

        $filename = $this->option('file');
        $filepath = storage_path('app/' . $filename);

        $this->info("Found {$members->count()} members to export");

        // Create CSV content
        $handle = fopen($filepath, 'w');

        // Add UTF-8 BOM for proper encoding in Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Write CSV header
        fputcsv($handle, [
            'ID',
            'Име',
            'Презиме', 
            'Фамилия',
            'Пълно име',
            'Изборен район',
            'Политическа сила',
            'Професия',
            'Е-мейл',
            'Създаден на',
            'Обновен на'
        ]);

        // Write member data
        $bar = $this->output->createProgressBar($members->count());
        $bar->start();

        foreach ($members as $member) {
            fputcsv($handle, [
                $member->member_id,
                $member->first_name,
                $member->middle_name,
                $member->last_name,
                $member->full_name,
                $member->electoral_district,
                $member->political_party,
                $member->profession,
                $member->email,
                $member->created_at?->format('Y-m-d H:i:s'),
                $member->updated_at?->format('Y-m-d H:i:s'),
            ]);
            
            $bar->advance();
        }

        $bar->finish();
        fclose($handle);

        $this->newLine();
        $this->info("CSV export completed successfully!");
        $this->info("File saved to: {$filepath}");
        $this->info("Exported {$members->count()} members");

        return 0;
    }
}
