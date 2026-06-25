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
    protected string $api_url          = 'https://dry-king-29d3.royal-sunset-e1c6.workers.dev/';
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

            Log::info("🚀 Alta Scan: დაიწყო | range={$this->start_id}-{$this->end_id}");

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
            $totalChunks    = count($chunks);

            Log::info("📦 Alta Scan: სულ {$stats['total']} ID | {$totalChunks} chunk");

            // ALTA_ACCESS_TOKEN შემოწმება
            $token = env('ALTA_ACCESS_TOKEN');
            if (empty($token)) {
                Log::error("❌ Alta Scan: ALTA_ACCESS_TOKEN არ არის .env-ში!");
                return $stats;
            }
            Log::info("🔑 Alta Scan: token=" . substr($token, 0, 10) . '...');

            // AltaID ცხრილი შემოწმება
            $altaIdCount = \App\Models\AltaID::count();
            Log::info("📋 Alta Scan: AltaID ცხრილში {$altaIdCount} ჩანაწერია");
            if ($altaIdCount === 0) {
                Log::error("❌ Alta Scan: AltaID ცხრილი ცარიელია — ვერაფერი დაqueue-ვდება!");
            }

            foreach ($chunks as $chunkIndex => $chunk) {
                if ($chunkIndex % 10 === 0) {
                    Log::info("⏳ Alta: chunk {$chunkIndex}/{$totalChunks} | queued={$stats['queued']} null={$stats['null']} errors={$stats['errors']}");
                }

                $chunkStats      = $this->scanChunk($chunk);
                $stats['queued'] += $chunkStats['queued'];
                $stats['null']   += $chunkStats['null'];
                $stats['errors'] += $chunkStats['errors'];

                unset($chunk);
                gc_collect_cycles();

                usleep(1000000);
            }

            $stats['duration'] = round(microtime(true) - $startTime, 2);

            Log::info("✅ Alta Scan: დასრულდა | queued={$stats['queued']} null={$stats['null']} errors={$stats['errors']} duration={$stats['duration']}s");

            return $stats;

        } catch (Exception $e) {
            Log::error('❌ AltaProduct scanAllIds error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    protected function scanChunk(array $ids): array
    {
        $stats = ['queued' => 0, 'null' => 0, 'errors' => 0];

        try {
            $token = env('ALTA_ACCESS_TOKEN');

            $client = new Client([
                'timeout'         => $this->timeout,
                'connect_timeout' => 15,
                'http_errors'     => false,
                'verify'          => false,
                'headers'         => [
                    'Accept'          => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ka',
                    'Referer'         => 'https://alta.ge/',
                    'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
                    'os'              => 'web',
                    'Cookie'          => 'alta-access_token=' . $token . '; alta-is_user_session=0',
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
                        $this->api_url . "?id={$id}&token=" . urlencode(env('ALTA_ACCESS_TOKEN'))
                    );
                }
            };

            $pool = new Pool($client, $requests($ids), [
                'concurrency' => $this->concurrent_requests,
                'fulfilled'   => function ($response, $id) use (&$stats) {
                    try {
                        $status = $response->getStatusCode();

                        if ($status === 401 || $status === 403) {
                            Log::error("🔐 Alta: Token invalid ან expired! status={$status} id={$id}");
                            $stats['errors']++;
                            return;
                        }

                        if ($status !== 200) {
                            Log::warning("⚠️ Alta: HTTP {$status} id={$id}");
                            $stats['errors']++;
                            return;
                        }

                        $body = $response->getBody()->getContents();
                        $data = json_decode($body, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Log::warning("⚠️ Alta: JSON parse error id={$id} | " . substr($body, 0, 100));
                            $stats['errors']++;
                            return;
                        }

                        if (!isset($data['product']) || $data['product'] === null) {
                            $stats['null']++;
                            return;
                        }

                        $barCode = $data['product']['barCode'] ?? null;

                        if (empty($barCode)) {
                            $stats['null']++;
                            return;
                        }

                        $exists = \App\Models\AltaID::where('product_id', (string) $barCode)->exists();

                        if (!$exists) {
                            $stats['null']++;
                            return;
                        }

                        AltaProductJob::dispatch(
                            $data['product'],
                            $data['availabilityInStores'] ?? []
                        )->onQueue('alta');

                        $stats['queued']++;

                    } catch (Exception $e) {
                        Log::error("❌ Alta: Error processing product id={$id}: " . $e->getMessage());
                        $stats['errors']++;
                    }
                },
                'rejected'    => function ($reason, $id) use (&$stats) {
                    Log::warning("❌ Alta: Request rejected id={$id} | " . $reason->getMessage());
                    $stats['errors']++;
                },
            ]);

            $pool->promise()->wait();

        } catch (Exception $e) {
            Log::error('❌ AltaProduct scanChunk error: ' . $e->getMessage());
            $stats['errors'] += count($ids);
        }

        return $stats;
    }
}