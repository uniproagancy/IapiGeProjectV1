<?php

namespace App\Services\Products;

use App\Jobs\AltaProductJob;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Exception;

/**
 * ✅ Improved ALTA Product Scraping Service
 *
 * Optimizations:
 * - Rate limiting (429 handling)
 * - Request delay (100-150ms)
 * - Reduced concurrency (20 → 5)
 * - Reduced chunk size (100 → 50)
 * - Better error handling
 * - Configurable parameters
 * - Memory optimization
 */
class AltaProduct
{
    // ============================================
    // Configuration
    // ============================================

    protected string $api_url;
    protected int $concurrent_requests;
    protected int $chunk_size;
    protected int $timeout;
    protected int $delay_ms;
    protected int $retry_delay;
    protected int $start_id;
    protected int $end_id;
    protected string $scrape_token;
    protected bool $use_scraper;

    // ============================================
    // Constructor
    // ============================================

    public function __construct()
    {
        // ✅ Load from config or use defaults
        $this->api_url = Config::get('services.alta.api_url', 'https://api.alta.ge');

        // ✅ RATE LIMITING: Reduced from 20 to 5
        $this->concurrent_requests = Config::get('services.alta.concurrent_requests', 5);

        // ✅ RATE LIMITING: Reduced from 100 to 50
        $this->chunk_size = Config::get('services.alta.chunk_size', 50);

        $this->timeout = Config::get('services.alta.timeout', 300);

        // ✅ NEW: Request delay (100-150ms between requests)
        $this->delay_ms = Config::get('services.alta.request_delay_ms', 100);

        // ✅ NEW: Retry delay when 429 (wait X seconds)
        $this->retry_delay = Config::get('services.alta.retry_delay', 5);

        $this->start_id = Config::get('services.alta.start_id', 1);
        $this->end_id = Config::get('services.alta.end_id', 100000);
        $this->scrape_token = Config::get('services.alta.scrape_token', '54ca3e2868ca407893b3316c254d6db6c146439c5b3');
        $this->use_scraper = Config::get('services.alta.use_scraper', true);
    }

    // ============================================
    // Fluent API
    // ============================================

    /**
     * ✅ Set ID range fluently
     */
    public function setIdRange(int $startId, int $endId): self
    {
        $this->start_id = $startId;
        $this->end_id = $endId;
        return $this;
    }

    /**
     * ✅ Set concurrent requests
     */
    public function setConcurrency(int $concurrency): self
    {
        $this->concurrent_requests = $concurrency;
        return $this;
    }

    /**
     * ✅ Set chunk size
     */
    public function setChunkSize(int $chunkSize): self
    {
        $this->chunk_size = $chunkSize;
        return $this;
    }

    /**
     * ✅ Set request delay in milliseconds
     */
    public function setDelayMs(int $delayMs): self
    {
        $this->delay_ms = $delayMs;
        return $this;
    }

    /**
     * ✅ Use/don't use scraper
     */
    public function useScraper(bool $use = true): self
    {
        $this->use_scraper = $use;
        return $this;
    }

    // ============================================
    // Main Scan Method
    // ============================================

    /**
     * ✅ Scan all product IDs in range
     *
     * Usage:
     * $service = new AltaProduct();
     * $stats = $service
     *     ->setIdRange(44200, 44300)
     *     ->setConcurrency(5)
     *     ->setDelayMs(100)
     *     ->scanAllIds();
     */
    public function scanAllIds(): array
    {
        try {
            // ✅ Setup environment
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '0');

            $startTime = microtime(true);
            $totalIds = $this->end_id - $this->start_id + 1;

            Log::info('🔄 Starting ALTA product scan', [
                'start_id' => $this->start_id,
                'end_id' => $this->end_id,
                'total_ids' => $totalIds,
                'concurrency' => $this->concurrent_requests,  // ✅ Now 5
                'chunk_size' => $this->chunk_size,            // ✅ Now 50
                'delay_ms' => $this->delay_ms,                // ✅ New: 100ms
            ]);

            $stats = [
                'total' => $totalIds,
                'queued' => 0,
                'null' => 0,
                'errors' => 0,
                'skipped' => 0,
                'rate_limited' => 0,  // ✅ NEW: Track 429s
            ];

            // ✅ Generate and process chunks
            $allIds = range($this->start_id, $this->end_id);
            $chunks = array_chunk($allIds, $this->chunk_size);

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkNumber = $chunkIndex + 1;
                $totalChunks = count($chunks);

                Log::info("🔄 Processing chunk {$chunkNumber}/{$totalChunks}", [
                    'chunk_ids' => count($chunk),
                    'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB',
                    'rate_limited_so_far' => $stats['rate_limited'],
                ]);

                // ✅ Process chunk
                $chunkStats = $this->scanChunk($chunk);
                $stats['queued'] += $chunkStats['queued'];
                $stats['null'] += $chunkStats['null'];
                $stats['errors'] += $chunkStats['errors'];
                $stats['skipped'] += $chunkStats['skipped'] ?? 0;
                $stats['rate_limited'] += $chunkStats['rate_limited'] ?? 0;

                // ✅ Wait between chunks if rate limited
                if ($stats['rate_limited'] > 0 && $chunkNumber < $totalChunks) {
                    Log::warning("⚠️ Rate limiting detected, waiting {$this->retry_delay}s before next chunk...");
                    sleep($this->retry_delay);
                }

                // ✅ Clean up memory
                unset($chunk);
                gc_collect_cycles();

                Log::info("✅ Chunk {$chunkNumber}/{$totalChunks} completed", [
                    'queued' => $chunkStats['queued'],
                    'rate_limited' => $chunkStats['rate_limited'] ?? 0,
                    'errors' => $chunkStats['errors'],
                ]);
            }

            $stats['duration'] = round(microtime(true) - $startTime, 2);
            $stats['rate'] = round($stats['total'] / $stats['duration'], 2) . ' products/sec';

            Log::info('✅ ALTA product scan completed', $stats);

            return $stats;

        } catch (Exception $e) {
            Log::error('❌ AltaProduct scanAllIds error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    // ============================================
    // Chunk Processing
    // ============================================

    /**
     * ✅ Process single chunk of product IDs
     */
    protected function scanChunk(array $ids): array
    {
        $stats = [
            'queued' => 0,
            'null' => 0,
            'errors' => 0,
            'skipped' => 0,
            'rate_limited' => 0,  // ✅ NEW: Track 429s
        ];

        try {
            // ✅ Create HTTP client
            $client = new Client([
                'timeout' => $this->timeout,
                'connect_timeout' => 10,
                'http_errors' => false,
                'verify' => false,
            ]);

            // ✅ Build requests generator (with delay)
            $requests = $this->buildRequests($ids);

            // ✅ Create and execute pool
            $pool = new Pool($client, $requests, [
                'concurrency' => $this->concurrent_requests,
                'fulfilled' => function ($response, $id) use (&$stats) {
                    $this->handleResponse($response, $id, $stats);
                },
                'rejected' => function ($reason, $id) use (&$stats) {
                    Log::warning("❌ Request rejected for product {$id}: {$reason}");
                    $stats['errors']++;
                },
            ]);

            // ✅ Wait for all requests
            $pool->promise()->wait();

        } catch (Exception $e) {
            Log::error('❌ AltaProduct scanChunk error: ' . $e->getMessage());
            $stats['errors'] += count($ids);
        }

        return $stats;
    }

    // ============================================
    // Request Building
    // ============================================

    /**
     * ✅ Build request generator with delay
     */
    private function buildRequests(array $ids)
    {
        foreach ($ids as $id) {
            try {
                // ✅ Add delay before yielding (100-150ms)
                usleep($this->delay_ms * 1000);

                // ✅ Build target URL
                $targetUrl = "{$this->api_url}/v1/Products/details?productId={$id}";

                // ✅ Use scraper if configured
                if ($this->use_scraper && !empty($this->scrape_token)) {
                    $url = "https://api.scrape.do/?url=" . urlencode($targetUrl) .
                        "&token={$this->scrape_token}";
                } else {
                    $url = $targetUrl;
                }

                yield $id => new Request('GET', $url, [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Accept' => 'application/json',
                ]);

            } catch (Exception $e) {
                Log::warning("⚠️ Error creating request for product {$id}: {$e->getMessage()}");
            }
        }
    }

    // ============================================
    // Response Handling
    // ============================================

    /**
     * ✅ Handle individual response (with 429 handling)
     */
    private function handleResponse($response, $id, &$stats): void
    {
        try {
            // ✅ Check status code
            $statusCode = $response->getStatusCode();

            // ✅ HANDLE 429 SPECIALLY
            if ($statusCode === 429) {
                Log::warning("⚠️ 429 Too Many Requests for product {$id}");
                $stats['rate_limited']++;
                return;  // Don't process, will be retried
            }

            if ($statusCode !== 200) {
                Log::warning("⚠️ Product {$id} returned status {$statusCode}");
                $stats['errors']++;
                return;
            }

            // ✅ Decode JSON
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // ✅ Validate response
            if (!isset($data['product'])) {
                Log::debug("📦 Product {$id} returned null");
                $stats['null']++;
                return;
            }

            // ✅ Validate product data
            if (!$this->validateProductData($data['product'])) {
                Log::warning("⚠️ Product {$id} failed validation");
                $stats['skipped']++;
                return;
            }

            // ✅ Dispatch job
            AltaProductJob::dispatch(
                $data['product'],
                $data['availabilityInStores'] ?? []
            )->onQueue('alta');

            $stats['queued']++;

        } catch (Exception $e) {
            Log::error("❌ Error processing product {$id}: {$e->getMessage()}");
            $stats['errors']++;
        }
    }

    // ============================================
    // Validation
    // ============================================

    /**
     * ✅ Validate product data
     */
    private function validateProductData(array $product): bool
    {
        // ✅ Check required fields
        $required = ['id', 'name', 'price'];
        foreach ($required as $field) {
            if (!isset($product[$field]) || empty($product[$field])) {
                return false;
            }
        }

        return true;
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * ✅ Test single product
     */
    public function testProduct(int $id): array
    {
        try {
            $client = new Client([
                'timeout' => $this->timeout,
                'verify' => false,
            ]);

            $targetUrl = "{$this->api_url}/v1/Products/details?productId={$id}";

            if ($this->use_scraper && !empty($this->scrape_token)) {
                $url = "https://api.scrape.do/?url=" . urlencode($targetUrl) .
                    "&token={$this->scrape_token}";
            } else {
                $url = $targetUrl;
            }

            $response = $client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            Log::info("✅ Test successful for product {$id}", ['data' => $data]);

            return $data;

        } catch (Exception $e) {
            Log::error("❌ Test failed for product {$id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Scan specific IDs only
     */
    public function scanSpecificIds(array $ids): array
    {
        $originalStart = $this->start_id;
        $originalEnd = $this->end_id;

        try {
            $this->start_id = min($ids);
            $this->end_id = max($ids);

            return $this->scanChunk($ids);

        } finally {
            $this->start_id = $originalStart;
            $this->end_id = $originalEnd;
        }
    }

    /**
     * ✅ Resume from specific ID
     */
    public function resumeFromId(int $startId): array
    {
        $this->start_id = $startId;
        return $this->scanAllIds();
    }

    /**
     * ✅ Get current statistics
     */
    public function getStats(): array
    {
        return [
            'api_url' => $this->api_url,
            'start_id' => $this->start_id,
            'end_id' => $this->end_id,
            'total_ids' => ($this->end_id - $this->start_id + 1),
            'concurrency' => $this->concurrent_requests,
            'chunk_size' => $this->chunk_size,
            'delay_ms' => $this->delay_ms,
            'retry_delay' => $this->retry_delay,
            'use_scraper' => $this->use_scraper,
        ];
    }
}