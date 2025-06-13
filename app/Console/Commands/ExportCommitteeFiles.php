<?php

namespace App\Console\Commands;

use App\Models\Committee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExportCommitteeFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'committees:export-files {--format=csv} {--folder=committees}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export each committee to a separate file with its members';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting committee files export...');

        $committees = Committee::orderBy('name')->get();

        if ($committees->isEmpty()) {
            $this->warn('No committees found in database. Run committees:scrape first.');
            return 1;
        }

        $format = $this->option('format');
        $folderName = $this->option('folder');
        
        // Create committees folder in storage
        $folderPath = storage_path('app/' . $folderName);
        
        // Clean and recreate the folder
        if (File::exists($folderPath)) {
            File::deleteDirectory($folderPath);
        }
        File::makeDirectory($folderPath, 0755, true);

        $this->info("Found {$committees->count()} committees to export");
        $this->info("Creating files in: {$folderPath}");

        // Process each committee
        $bar = $this->output->createProgressBar($committees->count());
        $bar->start();

        $totalMembers = 0;

        foreach ($committees as $committee) {
            $members = $committee->parliamentMembers()->orderBy('full_name')->get();
            $memberCount = $this->exportCommitteeFile($committee, $members, $folderPath, $format);
            $totalMembers += $memberCount;
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Committee files export completed successfully!");
        $this->info("Created {$committees->count()} files with {$totalMembers} total members");
        $this->info("Files location: {$folderPath}");

        return 0;
    }

    /**
     * Export a single committee to its own file
     */
    private function exportCommitteeFile($committee, $members, $folderPath, $format)
    {
        // Create a safe filename from committee name
        $safeFilename = $this->createSafeFilename($committee->name);
        $filename = $safeFilename . '.' . $format;
        $filepath = $folderPath . '/' . $filename;

        if ($format === 'csv') {
            return $this->exportCommitteeCsv($committee, $members, $filepath);
        } else {
            return $this->exportCommitteeTxt($committee, $members, $filepath);
        }
    }

    /**
     * Export committee as CSV file
     */
    private function exportCommitteeCsv($committee, $members, $filepath)
    {
        $handle = fopen($filepath, 'w');

        // Add UTF-8 BOM for proper encoding in Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Write committee header info
        fputcsv($handle, ['КОМИСИЯ:', $committee->name]);
        fputcsv($handle, ['ID:', $committee->committee_id]);
        fputcsv($handle, ['БРОЙ ЧЛЕНОВЕ:', $members->count()]);
        fputcsv($handle, ['Е-МЕЙЛ:', $committee->email ?? 'Няма']);
        fputcsv($handle, ['ТЕЛЕФОН:', $committee->phone ?? 'Няма']);
        fputcsv($handle, ['']); // Empty row

        // Write members header
        fputcsv($handle, [
            'Член ID',
            'Пълно име',
            'Позиция в комисията',
            'Политическа сила',
            'Изборен район',
            'Професия',
            'Е-мейл члена',
            'Дата от',
            'Дата до'
        ]);

        // Write member data
        foreach ($members as $member) {
            fputcsv($handle, [
                $member->member_id,
                $member->full_name,
                $member->pivot->position ?? 'член',
                $member->political_party,
                $member->electoral_district,
                $member->profession,
                $member->email,
                $member->pivot->date_from,
                $member->pivot->date_to,
            ]);
        }

        fclose($handle);
        return $members->count();
    }

    /**
     * Export committee as text file
     */
    private function exportCommitteeTxt($committee, $members, $filepath)
    {
        $content = [];
        
        $content[] = "=".str_repeat("=", 80);
        $content[] = "КОМИСИЯ: " . $committee->name;
        $content[] = "=".str_repeat("=", 80);
        $content[] = "";
        $content[] = "ID: " . $committee->committee_id;
        $content[] = "БРОЙ ЧЛЕНОВЕ: " . $members->count();
        $content[] = "Е-МЕЙЛ: " . ($committee->email ?? 'Няма');
        $content[] = "ТЕЛЕФОН: " . ($committee->phone ?? 'Няма');
        $content[] = "ДАТА ОТ: " . $committee->date_from?->format('Y-m-d');
        $content[] = "ДАТА ДО: " . $committee->date_to?->format('Y-m-d');
        $content[] = "";
        $content[] = "ЧЛЕНОВЕ:";
        $content[] = str_repeat("-", 80);

        foreach ($members as $index => $member) {
            $content[] = ($index + 1) . ". " . $member->full_name;
            $content[] = "   Позиция: " . ($member->pivot->position ?? 'член');
            $content[] = "   Политическа сила: " . ($member->political_party ?? 'Няма');
            $content[] = "   Изборен район: " . ($member->electoral_district ?? 'Няма');
            $content[] = "   Професия: " . ($member->profession ?? 'Няма');
            $content[] = "   Е-мейл: " . ($member->email ?? 'Няма');
            $content[] = "   Период: " . $member->pivot->date_from . " до " . $member->pivot->date_to;
            $content[] = "";
        }

        $content[] = str_repeat("=", 80);
        $content[] = "Общо членове: " . $members->count();
        $content[] = "Файл създаден на: " . now()->format('Y-m-d H:i:s');

        // Write to file with UTF-8 encoding
        File::put($filepath, implode("\n", $content));
        
        return $members->count();
    }

    /**
     * Create a safe filename from committee name
     */
    private function createSafeFilename($name)
    {
        // Remove HTML tags if any
        $name = strip_tags($name);
        
        // Replace Bulgarian characters with Latin equivalents
        $bgToLatin = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
            'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sht', 'ъ' => 'a', 'ь' => 'y',
            'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F',
            'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sht', 'Ъ' => 'A', 'Ь' => 'Y',
            'Ю' => 'Yu', 'Я' => 'Ya'
        ];
        
        $name = strtr($name, $bgToLatin);
        
        // Remove invalid filename characters and limit length
        $name = preg_replace('/[^A-Za-z0-9\s\-_]/', '', $name);
        $name = preg_replace('/\s+/', '_', trim($name));
        $name = substr($name, 0, 100); // Limit filename length
        
        return $name ?: 'committee_' . time();
    }
}
