<?php

namespace App\Services\Products;

use App\Jobs\CreateEliteProductJob;
use App\Models\EliteProduct;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class EliteScanService
{
    private string $apiUrl;
    private string $token;
    private int $startId     = 1;
    private int $endId       = 35000;
    private int $chunkSize   = 500;
    private int $concurrency = 10;
    private int $timeout     = 30;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('services.elite.api_url'), '/') . '/v1/Products/details';
        $this->token  = (string) config('services.elite.token', '');

        if (!$this->token) {
            Log::warning('⚠️ Elite: ELITE_API_TOKEN არ არის .env-ში');
        }
    }

    public function setRange(int $start, int $end): self
    {
        $this->startId = $start;
        $this->endId   = $end;
        return $this;
    }

    public function setConcurrency(int $n): self
    {
        $this->concurrency = $n;
        return $this;
    }

    public function setChunkSize(int $n): self
    {
        $this->chunkSize = $n;
        return $this;
    }

    public function scanAllIds(): array
    {
        @ini_set('memory_limit', '1024M');

        // Alta-ს მსგავსად: barCode => elite_product_id map
        $barCodeMap = EliteProduct::where('synced', false)
            ->pluck('id', 'bar_code')
            ->toArray();

        if (empty($barCodeMap)) {
            Log::warning("⚠️ Elite Scan: BarCode-ები ბაზაში არ არის. ჯერ Excel ატვირთე.");
            return ['total' => 0, 'queued' => 0, 'null' => 0, 'errors' => 0];
        }

        Log::info("🔎 Elite Scan: დაიწყო", [
            'range'    => "{$this->startId} - {$this->endId}",
            'barcodes' => count($barCodeMap),
        ]);

        $stats     = ['total' => 0, 'queued' => 0, 'null' => 0, 'errors' => 0];
        $startTime = microtime(true);

        $chunks = array_chunk(range($this->startId, $this->endId), $this->chunkSize);

        foreach ($chunks as $i => $chunk) {
            $chunkStats = $this->scanChunk($chunk, $barCodeMap);

            $stats['total']  += count($chunk);
            $stats['queued'] += $chunkStats['queued'];
            $stats['null']   += $chunkStats['null'];
            $stats['errors'] += $chunkStats['errors'];

            Log::info("⏳ Elite Scan: chunk " . ($i + 1) . "/" . count($chunks), $chunkStats);
        }

        $stats['duration'] = round(microtime(true) - $startTime, 2);
        Log::info("✅ Elite Scan დასრულდა", $stats);

        return $stats;
    }

    protected function scanChunk(array $ids, array $barCodeMap): array
    {
        $stats = ['queued' => 0, 'null' => 0, 'errors' => 0];

        $client = new Client([
            'timeout'     => $this->timeout,
            'http_errors' => false,
            'headers'     => [
                'Authorization'   => 'Bearer ' . $this->token,
                'Accept'          => 'application/json, text/plain, */*',
                'Accept-Language' => 'ka',
                'Origin'          => 'https://ee.ge',
                'Referer'         => 'https://ee.ge/',
                'os'              => 'web',
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
            ],
        ]);

        $requests = function ($ids) {
            foreach ($ids as $id) {
                yield $id => new Request('GET', "{$this->apiUrl}?productId={$id}");
            }
        };

        $pool = new Pool($client, $requests($ids), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $id) use (&$stats, $barCodeMap) {
                $status = $response->getStatusCode();

                if ($status === 401) {
                    Log::error("🔒 Elite: 401 — Bearer token ვადაგასულია");
                    $stats['errors']++;
                    return;
                }

                if ($status !== 200) {
                    $stats['errors']++;
                    return;
                }

                $data = json_decode($response->getBody(), true);

                // Alta-ს მსგავსად: isInStock შემოწმება
                if (
                    empty($data['product']) ||
                    !isset($data['product']['isInStock']) ||
                    !$data['product']['isInStock']
                ) {
                    $stats['null']++;
                    return;
                }

                $barCode = (string) ($data['product']['barCode'] ?? '');

                // Alta-ს მსგავსად: barCode შემოწმება ბაზაში
                if (!$barCode || !isset($barCodeMap[$barCode])) {
                    $stats['null']++;
                    return;
                }

                // Alta-ს მსგავსად: product + availabilityInStores გადაეცემა
                CreateEliteProductJob::dispatch(
                    $data['product'],
                    $data['availabilityInStores'] ?? [],
                    $barCodeMap[$barCode]
                )->onQueue('elite');

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