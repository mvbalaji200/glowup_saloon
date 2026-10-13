<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CustomerImportService;

class ImportCustomers extends Command
{
    protected $signature = 'customers:import {file}';
    protected $description = 'Import customers from CSV in chunks of 100';

    public function handle(CustomerImportService $service): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: " . $file);
            return Command::FAILURE;
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Cannot open file: " . $file);
            return Command::FAILURE;
        }

        // Read the first line (headers)
        $headerLine = fgetcsv($handle);
        $headers = [];
        foreach ($headerLine as $h) {
            $headers[] = strtolower(trim((string)$h));
        }

        // Find which column is "name" and which is "contact" / "phone"
        $nameCol = array_search('name', $headers);
        $phoneCol = array_search('contact', $headers);
        if ($phoneCol === false) {
            $phoneCol = array_search('phone', $headers);
        }

        $chunk = [];
        $totalSaved = 0;
        $totalSkipped = 0;

        // Read row by row
        while (($row = fgetcsv($handle)) !== false) {
            $chunk[] = [
                'name' => $row[$nameCol] ?? '',
                'phone' => $row[$phoneCol] ?? '',
            ];

            // When chunk reaches 100, process and reset
            if (count($chunk) == 100) {
                $result = $service->processRows($chunk);
                $totalSaved += $result['saved'];
                $totalSkipped += $result['skipped'];
                $chunk = []; // empty the bucket
            }
        }

        // Process leftover rows at the end
        if (count($chunk) > 0) {
            $result = $service->processRows($chunk);
            $totalSaved += $result['saved'];
            $totalSkipped += $result['skipped'];
        }

        fclose($handle);

        $this->info("Import complete!");
        $this->info("Saved: " . $totalSaved);
        $this->info("Skipped: " . $totalSkipped);
        $this->info("Skipped report saved to: storage/app/import_report.csv");

        return Command::SUCCESS;
    }
}
