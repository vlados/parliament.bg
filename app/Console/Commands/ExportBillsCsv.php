<?php

namespace App\Console\Commands;

use App\Models\Bill;
use Illuminate\Console\Command;

class ExportBillsCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:export-csv {--filename=bills.csv} {--committee-id=} {--days=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export bills to CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting bills CSV export...');

        $query = Bill::with('committee');

        // Filter by committee if specified
        if ($this->option('committee-id')) {
            $query->where('committee_id', $this->option('committee-id'));
        }

        // Filter by days if specified
        if ($this->option('days')) {
            $days = $this->option('days');
            $cutoffDate = now()->subDays($days);
            $query->where('bill_date', '>=', $cutoffDate);
        }

        $bills = $query->orderBy('bill_date', 'desc')->get();

        if ($bills->isEmpty()) {
            $this->warn('No bills found matching the criteria.');
            return 1;
        }

        $filename = $this->option('filename');
        $filepath = storage_path('app/' . $filename);

        $handle = fopen($filepath, 'w');

        // Add UTF-8 BOM for proper encoding in Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Write header
        fputcsv($handle, [
            'ID на законопроект',
            'Заглавие',
            'Номер',
            'Дата',
            'Комисия',
            'Път',
            'Създаден на',
            'Обновен на'
        ]);

        // Write bill data
        foreach ($bills as $bill) {
            fputcsv($handle, [
                $bill->bill_id,
                $bill->title,
                $bill->sign,
                $bill->bill_date->format('Y-m-d H:i:s'),
                $bill->committee?->name ?? 'Няма',
                $bill->path,
                $bill->created_at->format('Y-m-d H:i:s'),
                $bill->updated_at->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($handle);

        $this->info("Bills CSV export completed successfully!");
        $this->info("Exported {$bills->count()} bills to: {$filepath}");

        return 0;
    }
}