<?php

namespace App\Console\Commands;

use App\Services\Products\AltaProduct;
use Illuminate\Console\Command;

class AltaScan extends Command
{
    protected $signature   = 'alta:scan {--start=1} {--end=100000}';
    protected $description = 'Alta პროდუქტების სკანირება';

    public function handle(): void
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '0');

        $this->info('🚀 Alta სკანირება დაიწყო...');

        $service = new AltaProduct(
            startId: (int) $this->option('start'),
            endId:   (int) $this->option('end'),
        );

        $stats = $service->scanAllIds();

        $this->info("✅ დასრულდა:");
        $this->table(
            ['სულ', 'Queue-ში', 'ცარიელი', 'შეცდომა', 'დრო'],
            [[
                $stats['total'],
                $stats['queued'],
                $stats['null'],
                $stats['errors'],
                $stats['duration'] . 's',
            ]]
        );
    }
}