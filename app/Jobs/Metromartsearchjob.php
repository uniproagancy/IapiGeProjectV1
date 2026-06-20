<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MetromartSearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    private const SEARCH_URL  = 'https://metromart.ge/find-products-suggestions';
    private const PRODUCT_URL = 'https://metromart.ge/ka_GE/shop/product/';

    public function __construct(public string $model) {}

    public function handle(): void
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
                    'Referer'      => 'https://metromart.ge/',
                    'Origin'       => 'https://metromart.ge',
                ])
                ->post(self::SEARCH_URL, [
                    'jsonrpc' => '2.0',
                    'method'  => 'call',
                    'params'  => ['search' => $this->model],
                    'id'      => rand(100000000, 999999999),
                ]);

            if (!$response->successful()) {
                Log::warning("⚠️ Metromart: API error [{$response->status()}] model='{$this->model}'");
                return;
            }

            $index = $response->json('result.index') ?? [];

            if (empty($index)) {
                Log::info("🔍 Metromart: შედეგი არ მოიძებნა model='{$this->model}'");
                return;
            }

            $metromartId = $index[0]['id'] ?? null;

            if (!$metromartId) {
                Log::warning("⚠️ Metromart: id ცარიელია model='{$this->model}'");
                return;
            }

            $productUrl = self::PRODUCT_URL . $metromartId;

            Log::info("✅ Metromart: URL მოიძებნა", [
                'model'       => $this->model,
                'product_url' => $productUrl,
            ]);

            // შემდეგი ეტაპი — პროდუქტის import
            // MetromartImportJob::dispatch($this->model, $productUrl)->onQueue('metromart');

        } catch (Exception $e) {
            Log::error("❌ Metromart Search შეცდომა", [
                'model' => $this->model,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("🚨 MetromartSearchJob failed", [
            'model' => $this->model,
            'error' => $exception->getMessage(),
        ]);
    }
}