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
    protected string $api_url = 'https://api.alta.ge';
    protected int $concurrent_requests = 20; // შემცირდა 50-დან 20-მდე
    protected int $chunk_size = 100; // შემცირდა 500-დან 100-მდე
    protected int $timeout = 30;
    protected int $start_id = 45763;
    protected int $end_id = 45770;

    public function scanAllIds(): array
    {
        try {
            // Memory limit-ის გაზრდა
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '0');

            $startTime = microtime(true);
            $stats = [
                'total' => 0,
                'queued' => 0,
                'null' => 0,
                'errors' => 0,
            ];

            $allIds = range($this->start_id, $this->end_id);
            $stats['total'] = count($allIds);
            $chunks = array_chunk($allIds, $this->chunk_size);

            foreach ($chunks as $chunkIndex => $chunk) {
                Log::info("Processing chunk {$chunkIndex}/" . count($chunks));

                $chunkStats = $this->scanChunk($chunk);
                $stats['queued'] += $chunkStats['queued'];
                $stats['null'] += $chunkStats['null'];
                $stats['errors'] += $chunkStats['errors'];

                // Memory cleanup
                unset($chunk);
                gc_collect_cycles();
            }

            $stats['duration'] = round(microtime(true) - $startTime, 2);
            return $stats;

        } catch (Exception $e) {
            Log::error('AltaProduct scanAllIds error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function scanChunk(array $ids): array
    {
        $stats = ['queued' => 0, 'null' => 0, 'errors' => 0];

        try {
            $client = new Client([
                'timeout' => $this->timeout,
                'connect_timeout' => 10,
                'http_errors' => false,
                'verify' => false, // SSL verification (production-ში true უნდა იყოს)
            ]);

            $requests = function ($ids) {
                foreach ($ids as $id) {
                    yield $id => new Request(
                        'GET',
                        $this->api_url . "/v1/Products/details?productId={$id}"
                    );
                }
            };

            $pool = new Pool($client, $requests($ids), [
                'concurrency' => $this->concurrent_requests,
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
            Log::error('AltaProduct scanChunk error: ' . $e->getMessage());
            $stats['errors'] += count($ids);
        }

        return $stats;
    }
}