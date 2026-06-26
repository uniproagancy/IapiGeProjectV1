<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ThermocenterScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;

    private const CATALOG_URL = 'https://thermocenter.ge/თერმო-ცენტრი-კატეგორიები?items_per_page=10000';

    public function handle(): void
    {
        Log::info('🌡️ ThermocenterScan: კატალოგი იტვირთება...');

        $html = $this->fetchUrl(self::CATALOG_URL);
        if (!$html) {
            Log::error('❌ ThermocenterScan: კატალოგი ვერ ჩაიტვირთა');
            return;
        }

        $urls = $this->extractProductUrls($html);
        Log::info("📦 ThermocenterScan: " . count($urls) . " URL ნაპოვნი");

        if (empty($urls)) {
            Log::warning('⚠️ ThermocenterScan: პროდუქტის URL ვერ მოიძებნა');
            return;
        }

        $urls  = array_slice($urls, 0, 20); // TODO: ლიმიტი — წაშალე production-ზე
        $count = 0;
        foreach ($urls as $url) {
            ThermocenterProductJob::dispatch($url)->onQueue('thermocenter');
            $count++;
        }

        Log::info("✅ ThermocenterScan: {$count} job dispatched");
    }

    private function extractProductUrls(string $html): array
    {
        $urls = [];

        // a.product-title href-ების ამოღება
        if (preg_match_all('/<a[^>]+class="[^"]*product-title[^"]*"[^>]+href="([^"]+)"/i', $html, $matches)) {
            $urls = array_unique($matches[1]);
        }

        // fallback: title attribute-ით
        if (empty($urls)) {
            if (preg_match_all('/<a[^>]+href="(https:\/\/thermocenter\.ge\/[^"]+)"[^>]+class="[^"]*product-title[^"]*"/i', $html, $matches)) {
                $urls = array_unique($matches[1]);
            }
        }

        return array_values($urls);
    }

    private function fetchUrl(string $url): ?string
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ka,en;q=0.9',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error || $httpCode !== 200) {
            Log::warning("⚠️ ThermocenterScan: fetch failed HTTP={$httpCode} error={$error} url={$url}");
            return null;
        }

        return $response;
    }
}