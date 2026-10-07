<?php
namespace App\Services;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CustomerImportService {
    public function import(string $filePath): array {
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn($h) => strtolower(trim(preg_replace('/[\x{FEFF}]/u', '', (string)$h))), $header);

        $imported = 0;
        $skipped = 0;
        Storage::disk('local')->put('import_report.csv', "name,phone,date,reason\n");

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;
            $data = array_combine($header, array_pad($row, count($header), null));

            $name = trim($data['name'] ?? $data['customer_name'] ?? '');
            $rawPhone = trim($data['contact'] ?? $data['phone'] ?? $data['mobile'] ?? '');
            $digits = preg_replace('/\D/', '', $rawPhone);

            if (str_starts_with($digits, '91') && strlen($digits) === 12) $digits = substr($digits, 2);
            if (str_starts_with($digits, '0') && strlen($digits) === 11) $digits = substr($digits, 1);

            if (empty($name) || strlen($digits) !== 10) {
                Storage::disk('local')->append('import_report.csv', implode(',', $row) . ",Invalid name or phone\n");
                $skipped++;
                continue;
            }

            Customer::updateOrCreate(
                ['phone' => $digits],
                ['name' => $name, 'created_at' => Carbon::now(), 'is_active' => true]
            );
            $imported++;
        }
        fclose($handle);
        return ['imported' => $imported, 'skipped' => $skipped];
    }
}?>
