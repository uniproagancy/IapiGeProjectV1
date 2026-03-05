<?php

namespace App\Console\Commands;

use App\Jobs\ProductExcelImportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportProductExcel extends Command
{
    protected $signature   = 'product:import-excel {file}';
    protected $description = 'Excel ფაილიდან პროდუქტების იმპორტი';

    public function handle(): void
    {
        $filePath = $this->argument('file');

        if (!Storage::disk('public')->exists($filePath)) {
            $this->error("ფაილი ვერ მოიძებნა: {$filePath}");
            return;
        }

        $this->info("დამუშავება დაიწყო: {$filePath}");

        ProductExcelImportJob::dispatchSync($filePath);

        $this->info('✅ დასრულდა!');
    }
}