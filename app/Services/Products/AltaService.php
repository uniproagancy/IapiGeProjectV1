<?php

namespace App\Services\Products;

use App\Jobs\AltaProductJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AltaService
{
    protected string $worker_url = '';
    protected string $base_url   = 'https://alta.ge/';
    protected int    $timeout    = 30;
    protected int    $chunk_size = 50;

    public function __construct()
    {
        $this->worker_url = env('ALTA_WORKER_URL', 'https://dry-king-29d3.royal-sunset-e1c6.workers.dev');
    }

    public function scanAllIds(): array
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '0');

        Log::info("🚀 Alta Scan: დაიწყო | worker={$this->worker_url}");

        $token = env('ALTA_ACCESS_TOKEN');
        if (empty($token)) {
            Log::error("❌ Alta: ALTA_ACCESS_TOKEN არ არის .env-ში");
            return ['error' => 'Token missing'];
        }

        $stats = ['total' => 0, 'queued' => 0, 'not_found' => 0, 'errors' => 0];

        $items = DB::table('db_alta')
            ->where('quantity', '>', 2)
            ->select('product_id', 'quantity')
            ->get();

        $stats['total'] = $items->count();
        Log::info("📦 Alta: {$stats['total']} პროდუქტი quantity > 2");

        if ($stats['total'] === 0) {
            Log::warning("⚠️ Alta: db_alta ცარიელია");
            return $stats;
        }

        $startTime = microtime(true);
        $chunks    = $items->chunk($this->chunk_size);

        foreach ($chunks as $chunkIndex => $chunk) {
            if ($chunkIndex % 5 === 0) {
                Log::info("⏳ Alta chunk {$chunkIndex} | queued={$stats['queued']} not_found={$stats['not_found']} errors={$stats['errors']}");
            }

            foreach ($chunk as $item) {
                try {
                    $result = $this->processProduct((int) $item->product_id, (int) $item->quantity, $token);

                    if ($result === 'queued')        $stats['queued']++;
                    elseif ($result === 'not_found') $stats['not_found']++;
                    else                             $stats['errors']++;

                    usleep(1500000); // 1.5 წამი

                } catch (Exception $e) {
                    $stats['errors']++;
                    Log::error("❌ Alta error product_id={$item->product_id}: " . $e->getMessage());
                }
            }

            gc_collect_cycles();
            sleep(2);
        }

        $stats['duration'] = round(microtime(true) - $startTime, 2);
        Log::info("✅ Alta Scan დასრულდა | " . json_encode($stats, JSON_UNESCAPED_UNICODE));

        return $stats;
    }

    protected function processProduct(int $productId, int $quantity, string $token): string
    {
        // ნაბიჯი 1: Worker → suggestions API
        $suggestionsUrl = $this->worker_url . '?' . http_build_query([
                'type'  => 'suggestions',
                'query' => $productId,
                'token' => $token,
            ]);

        $response = $this->makeRequest($suggestionsUrl, [
            'Accept'     => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        if ($response === null) {
            Log::warning("⚠️ Alta: suggestions ვერ მიიღო product_id={$productId}");
            return 'error';
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['products'])) {
            return 'not_found';
        }

        $product = $data['products'][0] ?? null;
        if (!$product || empty($product['route'])) {
            return 'not_found';
        }

        $route = $product['route'];
        Log::info("🔗 Alta: route={$route} product_id={$productId}");

        // ნაბიჯი 2: Worker → პროდუქტის გვერდის HTML
        $pageUrl = $this->worker_url . '?' . http_build_query([
                'type'  => 'page',
                'route' => $route,
                'token' => $token,
            ]);

        $html = $this->makeRequest($pageUrl, [
            'Accept'     => 'text/html',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        if ($html === null) {
            Log::warning("⚠️ Alta: HTML ვერ მიიღო route={$route}");
            return 'error';
        }

        // ნაბიჯი 3: __NEXT_DATA__ parse
        $productData = $this->parseNextData($html);
        if ($productData === null) {
            Log::warning("⚠️ Alta: __NEXT_DATA__ parse ვერ მოხდა route={$route}");
            return 'error';
        }

        // ✅ Fix: product და availability ცალ-ცალკე გადაეცემა Job-ს
        AltaProductJob::dispatch(
            $productData['product'],
            $productData['availability']
        )->onQueue('alta');

        $name = $productData['product']['name'] ?? '?';
        Log::info("✅ Alta queued: product_id={$productId} | name={$name}");

        return 'queued';
    }

    protected function parseNextData(string $html): ?array
    {
        if (!preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            return null;
        }

        $json = json_decode($matches[1], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $product      = $json['props']['pageProps']['initialProductData']['product'] ?? null;
        $availability = $json['props']['pageProps']['initialProductData']['availabilityInStores'] ?? [];

        if (!$product) return null;

        return [
            'product'      => $product,
            'availability' => $availability,
        ];
    }

    protected function makeRequest(string $url, array $headers): ?string
    {
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "{$key}: {$value}";
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error) {
            Log::warning("⚠️ Alta curl error: {$error}");
            return null;
        }

        if ($httpCode === 429) {
            Log::warning("⏳ Alta: Rate limit (429), 5 წამი...");
            sleep(5);
            return null;
        }

        if ($httpCode === 403) {
            Log::error("🔐 Alta: 403 Blocked | url={$url}");
            return null;
        }

        if ($httpCode !== 200) {
            Log::warning("⚠️ Alta: HTTP {$httpCode} | url={$url}");
            return null;
        }

        return $response ?: null;
    }
}