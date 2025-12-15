<?php

namespace App\Services\Products;

use App\Jobs\CreateAltaProductJob;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class AltaProduct
{
    protected string $api_url = 'https://api.alta.ge';
    protected string $zenrows_api_key = '720dd891d818c11518c803e250b7f62b9f887d9b';
    protected int $concurrent_requests = 50;
    protected int $chunk_size = 500;
    protected int $timeout = 10;
    protected int $start_id = 45755;
    protected int $end_id = 45770;

    public function scanAllIds(): array
    {
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
        foreach ($chunks as $chunk) {
            $chunkStats = $this->scanChunk($chunk);
            $stats['queued'] += $chunkStats['queued'];
            $stats['null'] += $chunkStats['null'];
            $stats['errors'] += $chunkStats['errors'];
        }
        $stats['duration'] = round(microtime(true) - $startTime, 2);
        return $stats;
    }

    public function scanChunk(array $ids): array
    {
        $stats = ['queued' => 0, 'null' => 0, 'errors' => 0];
        $client = new Client(['timeout' => $this->timeout, 'http_errors' => false]);
        $requests = function ($ids) {
            foreach ($ids as $id) {
                $targetUrl = $this->api_url."/v1/Products/details?productId={$id}";
                $zenrowsUrl = "https://api.zenrows.com/v1/?apikey={$this->zenrows_api_key}&url=" . urlencode($targetUrl);
                yield $id => new Request('GET', $zenrowsUrl);
            }
        };
        $pool = new Pool($client, $requests($ids), [
            'concurrency' => $this->concurrent_requests,
            'fulfilled' => function ($response, $id) use (&$stats) {
                if ($response->getStatusCode() !== 200) {
                    $stats['errors']++;
                    return;
                }
                $data = json_decode($response->getBody(), true);
                if (!isset($data['product'])) {
                    $stats['null']++;
                    return;
                }
                CreateAltaProductJob::dispatch($data['product'])->onQueue('alta');
                $stats['queued']++;
            },
            'rejected' => function ($reason, $id) use (&$stats) {
                $stats['errors']++;
            },
        ]);
        $pool->promise()->wait();
        return $stats;
    }
}