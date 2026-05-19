<?php

namespace App\Services\Products;

use App\Jobs\AltaProductJob;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AltaService
{
    protected string $api_url          = 'https://alta.ge/api/proxy/';
    protected int $concurrent_requests = 5;
    protected int $chunk_size          = 50;
    protected int $timeout             = 30;
    protected int $start_id            = 1;
    protected int $end_id              = 100000;

    public function __construct(
        int $startId = 1,
        int $endId   = 100000,
    ) {
        $this->start_id = $startId;
        $this->end_id   = $endId;
    }

    public function scanAllIds(): array
    {
        try {
            ini_set('memory_limit', '2048M');
            ini_set('max_execution_time', '0');

            $startTime = microtime(true);
            $stats     = [
                'total'  => 0,
                'queued' => 0,
                'null'   => 0,
                'errors' => 0,
            ];

            $allIds         = range($this->start_id, $this->end_id);
            $stats['total'] = count($allIds);
            $chunks         = array_chunk($allIds, $this->chunk_size);

            foreach ($chunks as $chunkIndex => $chunk) {
                Log::info("Alta: Processing chunk {$chunkIndex}/" . count($chunks));

                $chunkStats      = $this->scanChunk($chunk);
                $stats['queued'] += $chunkStats['queued'];
                $stats['null']   += $chunkStats['null'];
                $stats['errors'] += $chunkStats['errors'];

                unset($chunk);
                gc_collect_cycles();

                usleep(1000000);
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
                'timeout'         => $this->timeout,
                'connect_timeout' => 15,
                'http_errors'     => false,
                'verify'          => false,
                'headers'         => [
                    'Accept-Language' => 'ka',
                ],
                'curl'            => [
                    CURLOPT_DNS_CACHE_TIMEOUT => 300,
                    CURLOPT_IPRESOLVE         => CURL_IPRESOLVE_V4,
                ],
            ]);

            $requests = function ($ids) {
                foreach ($ids as $id) {
                    yield $id => new Request(
                        'GET',
                        $this->api_url . "v1/Products/details?productId={$id}"
                    );
                }
            };

            $pool = new Pool($client, $requests($ids), [
                'concurrency' => $this->concurrent_requests,
                'fulfilled'   => function ($response, $id) use (&$stats) {
                    try {
                        if ($response->getStatusCode() !== 200) {
                            $stats['errors']++;
                            return;
                        }

                        $body = $response->getBody()->getContents();
                        $data = json_decode($body, true);

                        if (!isset($data['product']) || $data['product'] === null) {
                            $stats['null']++;
                            return;
                        }

                        AltaProductJob::dispatch(
                            $data['product'],
                            $data['availabilityInStores'] ?? []
                        )->onQueue('alta');

                        $stats['queued']++;

                    } catch (Exception $e) {
                        Log::error("Alta: Error processing product {$id}: " . $e->getMessage());
                        $stats['errors']++;
                    }
                },
                'rejected'    => function ($reason, $id) use (&$stats) {
                    $stats['errors']++;
                    Log::warning("Alta: Request failed for ID {$id}: " . $reason);
                },
            ]);

            $pool->promise()->wait();

        } catch (Exception $e) {
            Log::error('AltaProduct scanChunk error: ' . $e->getMessage());
            $stats['errors'] += count($ids);
        }

        return $stats;
    }
}