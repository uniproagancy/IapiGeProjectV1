<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AllmarketScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function handle(): void
    {
        Log::info("🚀 Allmarket Scan: დაიწყო");

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'AppSecret'    => env('ALLMARKET_APP_SECRET', '4r7B0Y4qAIq02vb251'),
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post('https://b2b.allmarket.ge/_mvcapi/productsapi/get');

            if (!$response->successful()) {
                Log::error("❌ Allmarket Scan: API error | status=" . $response->status() . " | body=" . substr($response->body(), 0, 200));
                return;
            }

            $data = $response->json();

            if (!($data['succeeded'] ?? false)) {
                Log::error("❌ Allmarket Scan: API succeeded=false | message=" . ($data['message'] ?? 'unknown'));
                return;
            }

            $products = $data['data']['products'] ?? [];

            if (empty($products)) {
                Log::warning("⚠️ Allmarket Scan: პროდუქტები ცარიელია");
                return;
            }

            Log::info("📦 Allmarket Scan: ნაპოვნია " . count($products) . " პროდუქტი");

            $dispatched = 0;
            foreach ($products as $product) {
                AllmarketProductJob::dispatch($product)->onQueue('allmarket');
                $dispatched++;
            }

            Log::info("✅ Allmarket Scan: დაიგზავნა {$dispatched} job queue-ში");

        } catch (\Throwable $e) {
            Log::error("❌ Allmarket Scan შეცდომა: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }
}