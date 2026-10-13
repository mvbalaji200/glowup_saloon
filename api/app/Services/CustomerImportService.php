<?php

namespace App\Services;

use App\Models\Customer;

class CustomerImportService
{
    protected string $reportPath;
    protected $reportHandle;

    public function __construct()
    {
        // Path to the log file for skipped rows
        $this->reportPath = storage_path('app/import_report.csv');
        $this->reportHandle = fopen($this->reportPath, 'w');

        // Write header row to the report
        fputcsv($this->reportHandle, ['name', 'phone', 'reason']);
    }

    public function __destruct()
    {
        // Close the file when done
        if ($this->reportHandle) {
            fclose($this->reportHandle);
        }
    }

    // Clean phone number to plain 10 digits
    public function cleanPhone($phone)
    {
        // 1. Remove common symbols: +, -, spaces
        $cleaned = str_replace(['+', '-', ' '], '', (string)$phone);

        // 2. If it starts with 91 and has 12 digits, cut off the 91
        if (strlen($cleaned) == 12 && substr($cleaned, 0, 2) == '91') {
            $cleaned = substr($cleaned, 2);
        }

        // 3. If it starts with 0 and has 11 digits, cut off the 0
        if (strlen($cleaned) == 11 && substr($cleaned, 0, 1) == '0') {
            $cleaned = substr($cleaned, 1);
        }

        // Return the 10-digit number, or null if it's invalid
        if (strlen($cleaned) == 10 && ctype_digit($cleaned)) {
            return $cleaned;
        }

        return null;
    }

    // Save a batch/chunk of rows to the database
    public function processRows(array $rows)
    {
        $savedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $row) {
            $name = trim($row['name'] ?? '');
            $rawPhone = $row['phone'] ?? '';

            // Rule 1: Skip if name is empty
            if ($name == '') {
                fputcsv($this->reportHandle, [$name, $rawPhone, 'Missing name']);
                $skippedCount++;
                continue;
            }

            // Rule 2: Skip if phone is invalid
            $cleanPhone = $this->cleanPhone($rawPhone);
            if ($cleanPhone == null) {
                fputcsv($this->reportHandle, [$name, $rawPhone, 'Invalid phone number']);
                $skippedCount++;
                continue;
            }

            // Rule 3: Insert or update if phone already exists
            Customer::updateOrCreate(
                ['phone' => $cleanPhone],
                ['name' => $name, 'is_active' => 1]
            );

            $savedCount++;
        }

        return [
            'saved' => $savedCount,
            'skipped' => $skippedCount,
        ];
    }
}

