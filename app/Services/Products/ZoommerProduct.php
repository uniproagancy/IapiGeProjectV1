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
    protected int $concurrent_requests = 10; // 20-დან 10-მდე (thread/DNS გადატვირთვა)
    protected int $chunk_size = 100;
    protected int $timeout = 30;
    protected int $start_id = 1;
    protected int $end_id = 100000;

    public function scanAllIds(): array
    {
        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '0');

            $startTime = microtime(true);
            $stats = [
                'total'  => 0,
                'queued' => 0,
                'null'   => 0,
                'errors' => 0,
            ];

            $allIds = range($this->start_id, $this->end_id);
            $stats['total'] = count($allIds);
            $chunks = array_chunk($allIds, $this->chunk_size);

            foreach ($chunks as $chunkIndex => $chunk) {
                Log::info("Processing chunk {$chunkIndex}/" . count($chunks));

                $chunkStats = $this->scanChunk($chunk);
                $stats['queued'] += $chunkStats['queued'];
                $stats['null']   += $chunkStats['null'];
                $stats['errors'] += $chunkStats['errors'];

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

        // User-Agent — cf_clearance-ს უნდა ემთხვეოდეს (Cloudflare ამოწმებს)
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36';

        $accessToken = env('ZOOMMER_ACCESS_TOKEN', '');
        $cfClearance = env('ZOOMMER_CF_CLEARANCE', '');

        $cookie = 'zoommer-cookie_agreed=true';
        if ($accessToken !== '') {
            $cookie .= '; zoommer-access_token=' . $accessToken;
        }
        if ($cfClearance !== '') {
            $cookie .= '; cf_clearance=' . $cfClearance;
        }

        try {
            $client = new Client([
                'timeout'         => $this->timeout,
                'connect_timeout' => 10,
                'http_errors'     => false,
                'verify'          => false,
                'headers'         => [
                    'Accept'             => 'application/json, text/plain, */*',
                    'Accept-Language'    => 'ka',
                    'Accept-Encoding'    => 'gzip, deflate, br',
                    'Referer'            => 'https://zoommer.ge/',
                    'User-Agent'         => $userAgent,
                    'os'                 => 'web',
                    'sec-ch-ua'          => '"Google Chrome";v="149", "Chromium";v="149", "Not)A;Brand";v="24"',
                    'sec-ch-ua-mobile'   => '?0',
                    'sec-ch-ua-platform' => '"Windows"',
                    'sec-fetch-dest'     => 'empty',
                    'sec-fetch-mode'     => 'cors',
                    'sec-fetch-site'     => 'same-origin',
                    'Cookie'             => $cookie,
                ],
                'curl' => [
                    CURLOPT_DNS_CACHE_TIMEOUT => 300,
                    CURLOPT_IPRESOLVE         => CURL_IPRESOLVE_V4,
                ],
            ]);

            $requests = function ($ids) {
                foreach ($ids as $id) {
                    // ერთი slash — api_url ბოლოს უკვე აქვს "/"
                    yield $id => new Request(
                        'GET',
                        $this->api_url . "v1/Products/details?productId={$id}"
                    );
                }
            };

            $pool = new Pool($client, $requests($ids), [
                'concurrency' => $this->concurrent_requests,
                'fulfilled' => function ($response, $id) use (&$stats) {
                    try {
                        $statusCode = $response->getStatusCode();

                        if ($statusCode === 403) {
                            $stats['errors']++;
                            Log::warning("⛔ Zoommer 403 (Cloudflare/token) ID {$id} — token განახლება საჭიროა");
                            return;
                        }

                        if ($statusCode !== 200) {
                            $stats['errors']++;
                            return;
                        }

                        $body = $response->getBody()->getContents();
                        $data = json_decode($body, true);

                        if (!isset($data['product']) || $data['product'] === null) {
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