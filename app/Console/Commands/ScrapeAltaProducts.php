<?php

namespace App\Console\Commands;

use App\Services\Products\AltaService;
use Illuminate\Console\Command;

class ScrapeAltaProducts extends Command
{
    protected $signature = 'alta:scan
        {--start= : საწყისი ID}
        {--end= : საბოლოო ID}
        {--concurrency=5 : პარალელური მოთხოვნები}
        {--chunk=50 : chunk-ის ზომა}
        {--delay=100 : დაყოვნება ms-ში}
        {--no-scraper : scraper-ის გარეშე}
        {--resume= : ID-დან გაგრძელება}
        {--ids= : კონკრეტული ID-ები, მძიმით გამოყოფილი}';

    protected $description = 'Alta პროდუქტების სკანირება';

    public function handle(AltaService $service): void
    {
        // ✅ კონკრეტული ID-ების სკანირება
        if ($this->option('ids')) {
            $ids = array_map('intval', explode(',', $this->option('ids')));
            $this->info('🔍 სკანირება კონკრეტული ID-ებისთვის: ' . implode(', ', $ids));
            $stats = $service->scanSpecificIds($ids);
            $this->printStats($stats);
            return;
        }

        // ✅ start და end სავალდებულოა
        $start = (int) $this->option('start');
        $end   = (int) $this->option('end');

        if (!$start || !$end) {
            $this->error('❌ --start და --end სავალდებულოა');
            return;
        }

        $service
            ->setIdRange($start, $end)
            ->setConcurrency((int) $this->option('concurrency'))
            ->setChunkSize((int) $this->option('chunk'))
            ->setDelayMs((int) $this->option('delay'))
            ->useScraper(!$this->option('no-scraper'));

        // ✅ სტატისტიკის გამოტანა
        $this->info('📊 სკანირების პარამეტრები:');
        $this->table(
            ['პარამეტრი', 'მნიშვნელობა'],
            collect($service->getStats())->map(fn ($v, $k) => [$k, $v])->values()->toArray()
        );

        if (!$this->confirm('დაიწყოს სკანირება?', true)) {
            return;
        }

        // ✅ გაგრძელება კონკრეტული ID-დან
        if ($resumeId = $this->option('resume')) {
            $this->info("⏩ გაგრძელება ID {$resumeId}-დან");
            $stats = $service->resumeFromId((int) $resumeId);
        } else {
            $stats = $service->scanAllIds();
        }

        $this->printStats($stats);
    }

    private function printStats(array $stats): void
    {
        $this->info('');
        $this->info('✅ სკანირება დასრულდა:');
        $this->table(
            ['სტატუსი', 'რაოდენობა'],
            [
                ['სულ',           $stats['total']        ?? 0],
                ['დამატებული',    $stats['queued']       ?? 0],
                ['ცარიელი',       $stats['null']         ?? 0],
                ['გამოტოვებული',  $stats['skipped']      ?? 0],
                ['შეცდომა',       $stats['errors']       ?? 0],
                ['Rate Limited',  $stats['rate_limited'] ?? 0],
                ['დრო',           ($stats['duration']    ?? 0) . 's'],
                ['სიჩქარე',       $stats['rate']         ?? 'N/A'],
            ]
        );
    }
}