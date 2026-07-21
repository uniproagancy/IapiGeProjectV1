<?php

namespace App\Jobs;

use App\Models\Product\Product;
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

    private const SUPPLIER_ID = 13;
    private const SKU_PREFIX  = 'ALLMARKET-';

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
            $scannedSkus = [];

            foreach ($products as $product) {
                $productCode = $product['productCode'] ?? null;
                if ($productCode) {
                    $scannedSkus[] = self::SKU_PREFIX . $productCode;
                }

                AllmarketProductJob::dispatch($product)->onQueue('allmarket');
                $dispatched++;
            }

            Log::info("✅ Allmarket Scan: დაიგზავნა {$dispatched} job queue-ში");

            // feed-იდან გამქრალი პროდუქტების გათიშვა
            $this->deactivateMissingProducts($scannedSkus);

        } catch (\Throwable $e) {
            Log::error("❌ Allmarket Scan შეცდომა: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    private function deactivateMissingProducts(array $scannedSkus): void
    {
        if (empty($scannedSkus)) {
            Log::warning("⚠️ Allmarket Scan: scannedSkus ცარიელია, გამორთვა გამოტოვებულია უსაფრთხოებისთვის");
            return;
        }

        $missingProducts = Product::where('supplier_id', self::SUPPLIER_ID)
            ->where('active', 1)
            ->where('update_lock', 0)
            ->whereNotIn('sku', $scannedSkus)
            ->get();

        if ($missingProducts->isEmpty()) {
            return;
        }

        Product::whereIn('id', $missingProducts->pluck('id'))
            ->update([
                'active'   => 0,
                'show'     => 0,
                'in_stock' => 0,
                'quantity' => 0,
            ]);

        Log::info("🚫 Allmarket Scan: გაითიშა " . $missingProducts->count() . " პროდუქტი (feed-ში აღარ მოიძებნა): "
            . $missingProducts->pluck('sku')->implode(', '));
    }
}