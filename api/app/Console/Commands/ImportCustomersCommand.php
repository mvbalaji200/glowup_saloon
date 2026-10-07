<?php
namespace App\Console\Commands;

use App\Services\CustomerImportService;
use Illuminate\Console\Command;

class ImportCustomersCommand extends Command {
    protected $signature = 'customers:import {file}';
    protected $description = 'Import customers from CSV';

    public function handle(CustomerImportService $service): int {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }
        $res = $service->import($file);
        $this->info("Imported: {$res['imported']} | Skipped: {$res['skipped']}");
        return Command::SUCCESS;
    }
}
