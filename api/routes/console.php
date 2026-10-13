<?php

use App\Services\CustomerImportService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('customers:import {file}', function (CustomerImportService $service, $file) {
    if (!file_exists($file)) {
        $this->error("File not found: {$file}");
        return 1;
    }

    $handle = fopen($file, 'r');
    if (! $handle) {
        $this->error("Cannot open file: {$file}");
        return 1;
    }

    $headerLine = fgetcsv($handle);
    $headers = [];
    foreach ($headerLine as $h) {
        $headers[] = strtolower(trim((string) $h));
    }

    $nameCol = array_search('name', $headers);
    $phoneCol = array_search('contact', $headers);
    if ($phoneCol === false) {
        $phoneCol = array_search('phone', $headers);
    }

    $chunk = [];
    $totalSaved = 0;
    $totalSkipped = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $chunk[] = [
            'name' => $row[$nameCol] ?? '',
            'phone' => $row[$phoneCol] ?? '',
        ];

        if (count($chunk) === 100) {
            $result = $service->processRows($chunk);
            $totalSaved += $result['saved'];
            $totalSkipped += $result['skipped'];
            $chunk = [];
        }
    }

    if (count($chunk) > 0) {
        $result = $service->processRows($chunk);
        $totalSaved += $result['saved'];
        $totalSkipped += $result['skipped'];
    }

    fclose($handle);

    $this->info("Import complete!");
    $this->info("Saved: {$totalSaved}");
    $this->info("Skipped: {$totalSkipped}");
    $this->info("Skipped report saved to: storage/app/import_report.csv");

    return 0;
});
