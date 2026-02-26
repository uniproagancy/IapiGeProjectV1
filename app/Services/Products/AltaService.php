<?php

namespace App\Services\Products;

use App\Jobs\AltaProductJob;
use App\Models\AltaID;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Exception;

class AltaService
{
    protected string $api_url;
    protected int $concurrent_requests;
    protected int $chunk_size;
    protected int $timeout;
    protected int $delay_ms;
    protected int $retry_delay;
    protected int $start_id = 0; // ✅ initialized
    protected int $end_id = 0;   // ✅ initialized
    protected string $scrape_token;
    protected bool $use_scraper;

    public function __construct()
    {
        $this->api_url             = Config::get('services.alta.api_url', 'https://api.alta.ge');
        $this->concurrent_requests = Config::get('services.alta.concurrent_requests', 5);
        $this->chunk_size          = Config::get('services.alta.chunk_size', 50);
        $this->timeout             = Config::get('services.alta.timeout', 300);
        $this->delay_ms            = Config::get('services.alta.request_delay_ms', 100);
        $this->retry_delay         = Config::get('services.alta.retry_delay', 5);
        $this->scrape_token        = Config::get('services.alta.scrape_token', '54ca3e2868ca407893b3316c254d6db6c146439c5b3');
        $this->use_scraper         = Config::get('services.alta.use_scraper', true);
    }

    // ============================================
    // Fluent API
    // ============================================

    public function setIdRange(int $startId, int $endId): self
    {
        $this->start_id = $startId;
        $this->end_id   = $endId;
        return $this;
    }

    public function setConcurrency(int $concurrency): self
    {
        $this->concurrent_requests = $concurrency;
        return $this;
    }

    public function setChunkSize(int $chunkSize): self
    {
        $this->chunk_size = $chunkSize;
        return $this;
    }

    public function setDelayMs(int $delayMs): self
    {
        $this->delay_ms = $delayMs;
        return $this;
    }

    public function useScraper(bool $use = true): self
    {
        $this->use_scraper = $use;
        return $this;
    }

    // ============================================
    // Main Scan Method
    // ============================================

    public function scanAllIds(): array
    {
        try {
            // ✅ Validation
            if ($this->start_id === 0 || $this->end_id === 0) {
                throw new Exception('ID range not set. Call setIdRange() first.');
            }

            if ($this->start_id > $this->end_id) {
                throw new Exception('start_id must be less than or equal to end_id.');
            }

            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '0');

            $startTime = microtime(true);
            $totalIds  = $this->end_id - $this->start_id + 1;

            Log::info('🔄 Starting ALTA product scan', [
                'start_id'    => $this->start_id,
                'end_id'      => $this->end_id,
                'total_ids'   => $totalIds,
                'concurrency' => $this->concurrent_requests,
                'chunk_size'  => $this->chunk_size,
            ]);

            $stats = [
                'total'        => $totalIds,
                'queued'       => 0,
                'null'         => 0,
                'errors'       => 0,
                'skipped'      => 0,
                'rate_limited' => 0,
            ];

            $allIds  = range($this->start_id, $this->end_id);
            $chunks  = array_chunk($allIds, $this->chunk_size);
            $total   = count($chunks);

            foreach ($chunks as $index => $chunk) {
                $current = $index + 1;

                Log::info("🔄 Processing chunk {$current}/{$total}", [
                    'chunk_ids'    => count($chunk),
                    'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB',
                ]);

                $chunkStats = $this->scanChunk($chunk);

                $stats['queued']       += $chunkStats['queued'];
                $stats['null']         += $chunkStats['null'];
                $stats['errors']       += $chunkStats['errors'];
                $stats['skipped']      += $chunkStats['skipped'] ?? 0;
                $stats['rate_limited'] += $chunkStats['rate_limited'] ?? 0;

                if (($chunkStats['rate_limited'] ?? 0) > 0 && $current < $total) {
                    Log::warning("⚠️ Rate limiting detected, waiting {$this->retry_delay}s before next chunk...");
                    sleep($this->retry_delay);
                }

                Log::info("✅ Chunk {$current}/{$total} completed", $chunkStats);

                unset($chunk);
                gc_collect_cycles();
            }

            $stats['duration'] = round(microtime(true) - $startTime, 2);
            $stats['rate']     = $stats['duration'] > 0
                ? round($stats['total'] / $stats['duration'], 2) . ' products/sec'
                : 'N/A';

            Log::info('✅ ALTA product scan completed', $stats);

            return $stats;

        } catch (Exception $e) {
            Log::error('❌ AltaService scanAllIds error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // ============================================
    // Chunk Processing
    // ============================================

    protected function scanChunk(array $ids): array
    {
        $stats = [
            'queued'       => 0,
            'null'         => 0,
            'errors'       => 0,
            'skipped'      => 0,
            'rate_limited' => 0,
        ];

        try {
            $client = new Client([
                'timeout'         => $this->timeout,
                'connect_timeout' => 10,
                'http_errors'     => false,
                'verify'          => false,
            ]);

            $requests = $this->buildRequests($ids);

            $pool = new Pool($client, $requests, [
                'concurrency' => $this->concurrent_requests,
                'fulfilled'   => function ($response, $id) use (&$stats) {
                    $this->handleResponse($response, $id, $stats);
                },
                'rejected'    => function ($reason, $id) use (&$stats) {
                    $this->handleRejection($reason, $id, $stats);
                },
            ]);

            $pool->promise()->wait();

        } catch (Exception $e) {
            Log::error('❌ AltaService scanChunk error: ' . $e->getMessage());
            $stats['errors'] += count($ids);
        } finally {
            unset($pool, $client, $requests);
            gc_collect_cycles();
        }

        return $stats;
    }

    // ============================================
    // Request Building
    // ============================================

    private function buildRequests(array $ids): \Generator
    {
        foreach ($ids as $id) {
            try {
                $this->sleep($this->delay_ms);
                yield $id => $this->buildRequest($id);
            } catch (Exception $e) {
                Log::warning("⚠️ Error building request for {$id}: {$e->getMessage()}");
                continue;
            }
        }
    }

    private function buildRequest(int $id): Request
    {
        $targetUrl = "{$this->api_url}/v1/Products/details?productId={$id}";

        if ($this->use_scraper && !empty($this->scrape_token)) {
            $url = "https://api.scrape.do/?url=" . urlencode($targetUrl) .
                "&token={$this->scrape_token}";
        } else {
            $url = $targetUrl;
        }

        return new Request('GET', $url, [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Accept'     => 'application/json',
        ]);
    }

    private function sleep(int $milliseconds): void
    {
        $seconds      = intdiv($milliseconds, 1000);
        $microseconds = ($milliseconds % 1000) * 1000;

        if ($seconds > 0) {
            sleep($seconds);
        }
        if ($microseconds > 0) {
            usleep($microseconds);
        }
    }

    // ============================================
    // Response Handling
    // ============================================

    private function handleResponse($response, $id, &$stats): void
    {
        try {
            $statusCode = $response->getStatusCode();
            if ($statusCode === 429) {
                Log::warning("⚠️ 429 Too Many Requests for product {$id}");
                $stats['rate_limited']++;
                return;
            }
            if ($statusCode !== 200) {
                Log::warning("⚠️ Product {$id} returned status {$statusCode}");
                $stats['errors']++;
                return;
            }

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (!isset($data['product'])) {
                Log::debug("📦 Product {$id} returned null");
                $stats['null']++;
                return;
            }

            if (!$this->validateProductData($data['product'])) {
                Log::debug("⚠️ Product {$id} failed validation");
                $stats['skipped']++;
                return;
            }

            $barCode = $data['product']['barCode'] ?? null;

            if (!$barCode) {
                Log::debug("⚠️ Product {$id} missing barCode");
                $stats['skipped']++;
                return;
            }

            // ✅ გასწორდა: skipped ითვლება whitelist-ში არარსებულისთვის
            if (AltaID::where('product_id', $barCode)->exists()) {
                AltaProductJob::dispatch(
                    $data['product'],
                    $data['availabilityInStores'] ?? []
                )->onQueue('alta');
                $stats['queued']++;
            } else {
                Log::debug("⏭️ Product {$id} (barCode: {$barCode}) not in AltaID whitelist");
                $stats['skipped']++;
            }

        } catch (Exception $e) {
            Log::error("❌ Error processing product {$id}: {$e->getMessage()}");
            $stats['errors']++;
        }
    }

    private function handleRejection($reason, $id, &$stats): void
    {
        $reasonStr = (string) $reason;

        // ✅ გასწორდა: rate_limited პირველი შემოწმება
        if (stripos($reasonStr, '429') !== false) {
            Log::warning("⚠️ Rate limited for product {$id}");
            $stats['rate_limited']++;
            return;
        }

        if (stripos($reasonStr, 'timeout') !== false) {
            Log::warning("⏱️ Timeout for product {$id}");
        } else {
            Log::error("❌ Request rejected for product {$id}: {$reasonStr}");
        }

        $stats['errors']++;
    }

    // ============================================
    // Validation
    // ============================================

    private function validateProductData(array $product): bool
    {
        if (!isset($product['id']) || !$product['id']) {
            return false;
        }

        if (!isset($product['name']) || !trim($product['name'])) {
            return false;
        }

        if (!isset($product['barCode']) || !$product['barCode']) {
            return false;
        }

        if (!isset($product['price'])) {
            return false;
        }

        return true;
    }

    // ============================================
    // Helper Methods
    // ============================================

    public function testProduct(int $id): array
    {
        try {
            $client = new Client([
                'timeout' => $this->timeout,
                'verify'  => false,
            ]);

            $request  = $this->buildRequest($id);
            $response = $client->send($request);
            $data     = json_decode($response->getBody()->getContents(), true);

            Log::info("✅ Test successful for product {$id}");
            return $data;

        } catch (Exception $e) {
            Log::error("❌ Test failed for product {$id}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function scanSpecificIds(array $ids): array
    {
        // ✅ Validation
        if (empty($ids)) {
            throw new Exception('IDs array cannot be empty.');
        }

        $originalStart = $this->start_id;
        $originalEnd   = $this->end_id;

        try {
            $this->start_id = min($ids);
            $this->end_id   = max($ids);
            return $this->scanChunk($ids);
        } finally {
            $this->start_id = $originalStart;
            $this->end_id   = $originalEnd;
        }
    }

    public function resumeFromId(int $startId): array
    {
        if ($this->end_id === 0) {
            throw new Exception('end_id not set. Call setIdRange() first.');
        }

        $this->start_id = $startId;
        return $this->scanAllIds();
    }

    public function getStats(): array
    {
        return [
            'api_url'     => $this->api_url,
            'start_id'    => $this->start_id ?: null,
            'end_id'      => $this->end_id ?: null,
            'total_ids'   => ($this->start_id && $this->end_id)
                ? ($this->end_id - $this->start_id + 1)
                : null,
            'concurrency' => $this->concurrent_requests,
            'chunk_size'  => $this->chunk_size,
            'delay_ms'    => $this->delay_ms,
            'use_scraper' => $this->use_scraper,
        ];
    }
}