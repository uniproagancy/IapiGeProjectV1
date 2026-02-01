<?php

namespace App\Services\Products;

use App\Jobs\AltaProductJob;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AltaProduct
{
    // ✅ API Configuration
    protected string $api_url = 'https://api.alta.ge';
    protected string $scrape_api_key;

    // ✅ Performance Settings
    protected int $concurrent_requests = 20;
    protected int $chunk_size = 100;
    protected int $timeout = 30;

    // ✅ Product ID Range
    protected int $start_id = 45760;
    protected int $end_id = 45780;

    public function __construct()
    {
        $this->scrape_api_key = '54ca3e2868ca407893b3316c254d6db6c146439c5b3';
    }

    public function setIdRange(int $startId, int $endId): self
    {
        if ($startId < 1 || $endId < $startId) {
            throw new Exception('Invalid ID range: start must be >= 1 and <= end');
        }

        $this->start_id = $startId;
        $this->end_id = $endId;
        return $this;
    }

    public function setConcurrency(int $concurrent): self
    {
        if ($concurrent < 1 || $concurrent > 100) {
            throw new Exception('Concurrency must be between 1 and 100');
        }

        $this->concurrent_requests = $concurrent;
        return $this;
    }

    /**
     * ✅ Set chunk size
     */
    public function setChunkSize(int $size): self
    {
        if ($size < 1 || $size > 1000) {
            throw new Exception('Chunk size must be between 1 and 1000');
        }

        $this->chunk_size = $size;
        return $this;
    }

    /**
     * ✅ Scan all product IDs and queue jobs
     */
    public function scanAllIds(): array
    {
        $startTime = microtime(true);

        Log::info('🔄 Starting Alta product scan', [
            'start_id' => $this->start_id,
            'end_id' => $this->end_id,
            'chunk_size' => $this->chunk_size,
            'concurrency' => $this->concurrent_requests,
        ]);

        $stats = [
            'total' => 0,
            'queued' => 0,
            'null' => 0,
            'errors' => 0,
        ];

        // ✅ Generate all IDs
        $allIds = range($this->start_id, $this->end_id);
        $stats['total'] = count($allIds);

        Log::info("📊 Total products to scan: {$stats['total']}");

        // ✅ Process in chunks
        $chunks = array_chunk($allIds, $this->chunk_size);
        $chunkCount = count($chunks);

        foreach ($chunks as $chunkIndex => $chunk) {
            try {
                $currentChunk = $chunkIndex + 1;
                Log::info("⏳ Processing chunk {$currentChunk}/{$chunkCount}", [
                    'ids' => count($chunk),
                ]);

                $chunkStats = $this->scanChunk($chunk);

                $stats['queued'] += $chunkStats['queued'];
                $stats['null'] += $chunkStats['null'];
                $stats['errors'] += $chunkStats['errors'];

                Log::info("✅ Chunk {$currentChunk} completed", [
                    'queued' => $chunkStats['queued'],
                    'null' => $chunkStats['null'],
                    'errors' => $chunkStats['errors'],
                ]);

            } catch (Exception $e) {
                Log::error("❌ Error processing chunk {$chunkIndex}: {$e->getMessage()}");
                $stats['errors'] += count($chunk);
            }
        }

        $stats['duration'] = round(microtime(true) - $startTime, 2);

        // ✅ Final summary
        Log::info('✨ Alta product scan completed', [
            'total' => $stats['total'],
            'queued' => $stats['queued'],
            'null' => $stats['null'],
            'errors' => $stats['errors'],
            'duration_seconds' => $stats['duration'],
            'rate' => round($stats['total'] / max($stats['duration'], 1), 2) . ' products/sec',
        ]);

        return $stats;
    }

    /**
     * ✅ Scan single chunk with concurrent requests
     */
    public function scanChunk(array $ids): array
    {
        $stats = ['queued' => 0, 'null' => 0, 'errors' => 0];

        try {
            // ✅ Create HTTP client with timeout
            $client = new Client([
                'timeout' => $this->timeout,
                'http_errors' => false,
                'connect_timeout' => 5,
            ]);

            // ✅ Generate requests
            $requests = function ($ids) {
                foreach ($ids as $id) {
                    try {
                        $targetUrl = $this->api_url . "/v1/Products/details?productId={$id}";
                        $crapeUrl = "https://api.scrape.do/?url=".$targetUrl."&token=54ca3e2868ca407893b3316c254d6db6c146439c5b3";
                        yield $id => new Request('GET', $crapeUrl);

                    } catch (Exception $e) {
                        Log::warning("Error creating request for product {$id}: {$e->getMessage()}");
                    }
                }
            };

            // ✅ Execute requests in pool
            $pool = new Pool($client, $requests($ids), [
                'concurrency' => $this->concurrent_requests,

                // ✅ Handle successful response
                'fulfilled' => function ($response, $id) use (&$stats) {
                    try {
                        // ✅ Check response status
                        if ($response->getStatusCode() !== 200) {
                            Log::warning("Product {$id} returned status {$response->getStatusCode()}");
                            $stats['errors']++;
                            return;
                        }

                        // ✅ Parse JSON response
                        $body = $response->getBody()->getContents();
                        $data = json_decode($body, true);

                        if (!is_array($data)) {
                            Log::warning("Invalid JSON response for product {$id}");
                            $stats['errors']++;
                            return;
                        }

                        // ✅ Check if product exists
                        if (!isset($data['product']) || empty($data['product'])) {
                            Log::debug("No product data for ID {$id}");
                            $stats['null']++;
                            return;
                        }

                        // ✅ Validate product data
                        $product = $data['product'];
                        if (empty($product['id'])) {
                            Log::warning("Product {$id} has no ID in response");
                            $stats['errors']++;
                            return;
                        }

                        // ✅ Queue job
                        AltaProductJob::dispatch($product)->onQueue('alta');
                        $stats['queued']++;

                    } catch (Exception $e) {
                        Log::error("Error processing product {$id}: {$e->getMessage()}");
                        $stats['errors']++;
                    }
                },

                // ✅ Handle failed request
                'rejected' => function ($reason, $id) use (&$stats) {
                    Log::warning("Request failed for product {$id}: {$reason}");
                    $stats['errors']++;
                },
            ]);

            // ✅ Wait for all requests to complete
            $pool->promise()->wait();

        } catch (Exception $e) {
            Log::error("Error in scanChunk: {$e->getMessage()}");
            $stats['errors'] += count($ids);
        }

        return $stats;
    }

    /**
     * ✅ Scan specific product IDs
     */
    public function scanSpecificIds(array $ids): array
    {
        if (empty($ids)) {
            throw new Exception('No product IDs provided');
        }

        Log::info("🔍 Scanning specific products", ['count' => count($ids)]);

        return $this->scanChunk($ids);
    }

    /**
     * ✅ Resume from specific ID
     */
    public function resumeFromId(int $fromId): array
    {
        Log::info("⏸️  Resuming from product ID {$fromId}");

        return $this->setIdRange($fromId, $this->end_id)->scanAllIds();
    }

    /**
     * ✅ Test single product
     */
    public function testProduct(int $productId): ?array
    {
        try {
            Log::info("🧪 Testing product {$productId}");

            $client = new Client(['timeout' => $this->timeout, 'http_errors' => false]);

            $targetUrl = $this->api_url . "/v1/Products/details?productId={$productId}";
            $zenrowsUrl = "https://api.zenrows.com/v1/?apikey={$this->zenrows_api_key}&url=" . urlencode($targetUrl);

            $response = $client->get($zenrowsUrl);

            if ($response->getStatusCode() !== 200) {
                Log::error("❌ Product {$productId} returned status {$response->getStatusCode()}");
                return null;
            }

            $data = json_decode($response->getBody(), true);

            if (!isset($data['product'])) {
                Log::warning("⚠️  No product data for ID {$productId}");
                return null;
            }

            Log::info("✅ Product {$productId} data retrieved successfully", [
                'name' => $data['product']['name'] ?? 'N/A',
            ]);

            return $data['product'];

        } catch (Exception $e) {
            Log::error("Error testing product {$productId}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * ✅ Get statistics
     */
    public function getStats(): array
    {
        return [
            'api_url' => $this->api_url,
            'start_id' => $this->start_id,
            'end_id' => $this->end_id,
            'total_products' => $this->end_id - $this->start_id + 1,
            'chunk_size' => $this->chunk_size,
            'concurrent_requests' => $this->concurrent_requests,
            'timeout' => $this->timeout,
            'api_key_configured' => !empty($this->zenrows_api_key),
        ];
    }
}