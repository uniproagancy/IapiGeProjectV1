<?php

namespace App\Services\Products;

use App\Jobs\EliteProductJob;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class EliteScanService
{
    protected string $api_url          = 'https://ee-api.ee.ge/v1/Products/details';
    protected int $concurrent_requests = 10;
    protected int $chunk_size          = 500;
    protected int $timeout             = 30;
    protected int $start_id            = 1;
    protected int $end_id              = 35000;

    public function setRange(int $start, int $end): self
    {
        $this->start_id = $start;
        $this->end_id   = $end;
        return $this;
    }

    public function scanAllIds(): array
    {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', '0');

            Log::info("🔎 Elite Scan: დაიწყო", [
                'range' => "{$this->start_id} - {$this->end_id}",
            ]);

            $startTime = microtime(true);
            $stats = [
                'total'  => 0,
                'queued' => 0,
                'null'   => 0,
                'errors' => 0,
            ];

            $allIds         = range($this->start_id, $this->end_id);
            $stats['total'] = count($allIds);
            $chunks         = array_chunk($allIds, $this->chunk_size);

            foreach ($chunks as $chunkIndex => $chunk) {
                Log::info("⏳ Elite Scan: chunk {$chunkIndex}/" . count($chunks));

                $chunkStats = $this->scanChunk($chunk);

                $stats['queued'] += $chunkStats['queued'];
                $stats['null']   += $chunkStats['null'];
                $stats['errors'] += $chunkStats['errors'];

                unset($chunk);
                gc_collect_cycles();
            }

            $stats['duration'] = round(microtime(true) - $startTime, 2);

            Log::info("✅ Elite Scan დასრულდა", $stats);

            return $stats;

        } catch (Exception $e) {
            Log::error('EliteScanService scanAllIds error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function scanChunk(array $ids): array
    {
        $stats = ['queued' => 0, 'null' => 0, 'errors' => 0];

        $token = config('services.elite.token', '');

        try {
            $client = new Client([
                'timeout'         => $this->timeout,
                'connect_timeout' => 10,
                'http_errors'     => false,
                'headers'         => [
                    'Authorization'   => 'Bearer ' . $token,
                    'Accept'          => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ka',
                    'Origin'          => 'https://ee.ge',
                    'Referer'         => 'https://ee.ge/',
                    'os'              => 'web',
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
                ],
                'curl' => [
                    CURLOPT_DNS_CACHE_TIMEOUT => 300,
                    CURLOPT_IPRESOLVE         => CURL_IPRESOLVE_V4,
                ],
            ]);

            $requests = function ($ids) {
                foreach ($ids as $id) {
                    yield $id => new Request(
                        'GET',
                        $this->api_url . "?productId={$id}"
                    );
                }
            };

            $pool = new Pool($client, $requests($ids), [
                'concurrency' => $this->concurrent_requests,
                'fulfilled'   => function ($response, $id) use (&$stats) {
                    try {
                        $statusCode = $response->getStatusCode();

                        if ($statusCode === 401) {
                            $stats['errors']++;
                            Log::warning("🔒 Elite 401 ID {$id} — token განახლება საჭიროა");
                            return;
                        }

                        if ($statusCode !== 200) {
                            $stats['errors']++;
                            return;
                        }

                        $data = json_decode($response->getBody()->getContents(), true);

                        if (!isset($data['product']) || $data['product'] === null) {
                            $stats['null']++;
                            return;
                        }

                        // Zoommer-ის მსგავსად — ყველა product dispatch-დება
                        // BarCode-ის შემოწმება Job-ში ხდება
                        EliteProductJob::dispatch(
                            $data['product'],
                            $data['availabilityInStores'] ?? []
                        )->onQueue('elite');

                        $stats['queued']++;

                    } catch (Exception $e) {
                        Log::error("Elite: Error processing product ID {$id}: " . $e->getMessage());
                        $stats['errors']++;
                    }
                },
                'rejected' => function ($reason, $id) use (&$stats) {
                    $stats['errors']++;
                    Log::warning("Elite: Request failed for ID {$id}: " . $reason);
                },
            ]);

            $pool->promise()->wait();

        } catch (Exception $e) {
            Log::error('EliteScanService scanChunk error: ' . $e->getMessage());
            $stats['errors'] += count($ids);
        }

        return $stats;
    }
}