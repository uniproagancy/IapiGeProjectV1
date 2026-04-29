<?php

namespace App\Services\Products;

use App\Jobs\ZoommerProductJob;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ZoommerProduct
{
    protected string $api_url = 'https://zoommer.ge/api/proxy/';
    protected int $concurrent_requests = 20; // შემცირდა 50-დან 20-მდე
    protected int $chunk_size = 100; // შემცირდა 500-დან 100-მდე
    protected int $timeout = 30;
    protected int $start_id = 1;
    protected int $end_id = 100000;

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
            Log::error('ZoommerProduct scanAllIds error: ' . $e->getMessage());
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
                        if ($response->getStatusCode() !== 200) {
                            $stats['errors']++;
                            return;
                        }

                        $body = $response->getBody()->getContents();
                        $data = json_decode($body, true);

                        if (!isset($data['product']) ||
                            $data['product'] === null) {
                            $stats['null']++;
                            return;
                        }

                        ZoommerProductJob::dispatch(
                            $data['product'],
                            $data['availabilityInStores'] ?? []
                        )->onQueue('zoommer');

                        $stats['queued']++;

                    } catch (Exception $e) {
                        Log::error("Error processing product {$id}: " . $e->getMessage());
                        $stats['errors']++;
                    }
                },
                'rejected' => function ($reason, $id) use (&$stats) {
                    $stats['errors']++;
                    Log::warning("Request failed for ID {$id}: " . $reason);
                },
            ]);

            $pool->promise()->wait();

        } catch (Exception $e) {
            Log::error('ZoommerProduct scanChunk error: ' . $e->getMessage());
            $stats['errors'] += count($ids);
        }

        return $stats;
    }
}