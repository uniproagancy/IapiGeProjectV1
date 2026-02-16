<?php

namespace App\Console\Commands;

use App\Services\Products\AltaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapeAltaProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alta:scrape 
                            {--start=1 : Start product ID}
                            {--end=60000 : End product ID}
                            {--concurrency=5 : Concurrent requests (1-10)}
                            {--delay=100 : Delay in milliseconds (50-1000)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape ALTA products by ID range';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // ✅ Get arguments
            $start = (int)$this->option('start');
            $end = (int)$this->option('end');
            $concurrency = (int)$this->option('concurrency');
            $delay = (int)$this->option('delay');

            // ✅ Validate
            if ($start >= $end) {
                $this->error('❌ Start ID must be less than End ID');
                return 1;
            }

            if ($concurrency < 1 || $concurrency > 10) {
                $this->error('❌ Concurrency must be between 1-10');
                return 1;
            }

            if ($delay < 50 || $delay > 1000) {
                $this->error('❌ Delay must be between 50-1000 ms');
                return 1;
            }

            // ✅ Show info
            $this->info('');
            $this->info('🔄 ALTA Products Scraper');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("Start ID:      {$start}");
            $this->info("End ID:        {$end}");
            $this->info("Total IDs:     " . ($end - $start + 1));
            $this->info("Concurrency:   {$concurrency}");
            $this->info("Delay:         {$delay}ms");
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('');

            if (!$this->confirm('Start scraping?')) {
                $this->info('✅ Cancelled');
                return 0;
            }

            // ✅ Create service
            $service = new AltaService();

            // ✅ Run scraping
            $this->info('🚀 Starting scrape...');
            $this->newLine();

            $stats = $service
                ->setIdRange($start, $end)
                ->setConcurrency($concurrency)
                ->setDelayMs($delay)
                ->scanAllIds();

            // ✅ Show results
            $this->newLine();
            $this->info('✅ Scrape completed!');
            $this->info('');
            $this->info('📊 Statistics:');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line("Total IDs:        {$stats['total']}");
            $this->line("Queued:           {$stats['queued']}");
            $this->line("Null:             {$stats['null']}");
            $this->line("Skipped:          {$stats['skipped']}");
            $this->line("Errors:           {$stats['errors']}");
            $this->line("Rate Limited:     {$stats['rate_limited']}");
            $this->line("Duration:         {$stats['duration']}s");
            $this->line("Rate:             {$stats['rate']}");
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();

            // ✅ Log
            Log::info('✅ ALTA scraping completed via command', $stats);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            Log::error('❌ ALTA scrape command error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}