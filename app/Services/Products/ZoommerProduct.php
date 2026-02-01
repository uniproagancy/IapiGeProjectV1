<?php


namespace App\Services\Products;

use App\Jobs\ZoommerProductJob;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class ZoommerProduct
{
    protected string $api_url = 'https://api.zoommer.ge';
    protected int $concurrent_requests = 50;
    protected int $chunk_size = 500;
    protected int $timeout = 60;
    protected int $start_id = 60000;
    protected int $end_id = 61000;

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

    protected function scanChunk(array $ids): array
    {
        $stats = ['queued' => 0, 'null' => 0, 'errors' => 0];
        $client = new Client(['timeout' => $this->timeout, 'http_errors' => false]);
        $requests = function ($ids) {
            foreach ($ids as $id) {
                yield $id => new Request('GET', $this->api_url . "/v1/Products/details?productId={$id}");
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
                if (!isset($data['product']) || $data['product'] === null || !$data['product']['isInStock']) {
                    $stats['null']++;
                    return;
                }
                ZoommerProductJob::dispatch($data['product'], $data['availabilityInStores'])->onQueue('zoommer');
                $stats['queued']++;
            },
            'rejected' => function ($reason, $id) use (&$stats) {
                $stats['errors']++;
                Log::warning("Request failed for ID {$id}: " . $reason);
            },
        ]);
        $pool->promise()->wait();
        return $stats;
    }
}