<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlneoScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function handle(): void
    {
        @ini_set('memory_limit', '512M');

        $url = 'https://alneo.ge/shop/?ppp=-1';

        try {
            $response = Http::timeout(180)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::error("⛔ Alneo Scan: მთავარი გვერდი ვერ წამოვიდა", ['status' => $response->status()]);
                return;
            }

            $html = $response->body();
            $links = $this->extractProductLinks($html);
            unset($html);

            $unique = array_values(array_unique($links));
            Log::info("🔎 Alneo Scan: ნაპოვნია " . count($unique) . " უნიკალური პროდუქტი");

            $dispatched = 0;
            foreach ($unique as $productUrl) {
                AlneoImportJob::dispatch($productUrl)->onQueue('alneo');
                $dispatched++;
            }

            Log::info("✅ Alneo Scan: დაიგზავნა {$dispatched} ჯობი queue-ში");

        } catch (\Throwable $e) {
            Log::error("❌ Alneo Scan შეცდომა", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function extractProductLinks(string $html): array
    {
        $links = [];

        preg_match_all(
            '/<a\s+href="([^"]+)"[^>]*class="[^"]*woocommerce-LoopProduct-link[^"]*"/i',
            $html,
            $matches
        );
        if (!empty($matches[1])) {
            $links = array_merge($links, $matches[1]);
        }

        preg_match_all(
            '/<a\s+class="[^"]*woocommerce-LoopProduct-link[^"]*"\s+href="([^"]+)"/i',
            $html,
            $matches
        );
        if (!empty($matches[1])) {
            $links = array_merge($links, $matches[1]);
        }

        return array_values(array_filter($links, fn($u) => str_contains($u, '/product/')));
    }
}