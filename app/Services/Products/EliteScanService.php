<?php

namespace App\Services\Products;

use App\Jobs\EliteProductJob;
use Illuminate\Support\Facades\Log;
use Exception;

class EliteScanService
{
    protected string $worker_url       = '';
    protected int $concurrent_requests = 10;
    protected int $chunk_size          = 50;
    protected int $timeout             = 30;
    protected int $start_id            = 1;
    protected int $end_id              = 35000;

    public function __construct()
    {
        $this->worker_url = env('ELITE_WORKER_URL', 'https://divine-king-feac.royal-sunset-e1c6.workers.dev');
    }

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

            $token = env('ELITE_TOKEN', '');
            if (empty($token)) {
                Log::error("❌ Elite Scan: ELITE_TOKEN არ არის .env-ში");
                return ['error' => 'Token missing'];
            }

            Log::info("🔎 Elite Scan: დაიწყო", [
                'range'  => "{$this->start_id} - {$this->end_id}",
                'worker' => $this->worker_url,
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

            Log::info("📦 Elite Scan: " . count($allIds) . " ID | " . count($chunks) . " chunk");

            foreach ($chunks as $chunkIndex => $chunk) {
                Log::info("⏳ Elite Scan: chunk {$chunkIndex}/" . count($chunks) . " | IDs: {$chunk[0]} - " . end($chunk));

                $chunkStats = $this->scanChunk($chunk, $token);

                $stats['queued'] += $chunkStats['queued'];
                $stats['null']   += $chunkStats['null'];
                $stats['errors'] += $chunkStats['errors'];

                Log::info("📊 Elite chunk {$chunkIndex}: queued={$chunkStats['queued']} null={$chunkStats['null']} errors={$chunkStats['errors']}");

                unset($chunk);
                gc_collect_cycles();
                sleep(1);
            }

            $stats['duration'] = round(microtime(true) - $startTime, 2);
            Log::info("✅ Elite Scan დასრულდა", $stats);

            return $stats;

        } catch (Exception $e) {
            Log::error('❌ EliteScanService error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function scanChunk(array $ids, string $token): array
    {
        $stats = ['queued' => 0, 'null' => 0, 'errors' => 0];

        foreach ($ids as $id) {
            try {
                $result = $this->processProduct($id, $token);

                if ($result === 'queued')        $stats['queued']++;
                elseif ($result === 'not_found') $stats['null']++;
                else                             $stats['errors']++;

                usleep(500000); // 0.5 წამი

            } catch (Exception $e) {
                $stats['errors']++;
                Log::error("❌ Elite error ID={$id}: " . $e->getMessage());
            }
        }

        return $stats;
    }

    protected function processProduct(int $productId, string $token): string
    {
        $workerUrl = $this->worker_url . '?' . http_build_query([
                'type'      => 'product',
                'productId' => $productId,
                'token'     => $token,
            ]);

        $response = $this->makeRequest($workerUrl);

        if ($response === null) {
            Log::warning("⚠️ Elite: Worker ვერ მიიღო ID={$productId}");
            return 'error';
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning("⚠️ Elite: JSON parse error ID={$productId}");
            return 'error';
        }

        if (!isset($data['product']) || $data['product'] === null) {
            return 'not_found';
        }

        EliteProductJob::dispatch(
            $data['product'],
            $data['availabilityInStores'] ?? []
        )->onQueue('elite');

        $name = $data['product']['name'] ?? '?';
        Log::info("✅ Elite queued: ID={$productId} | name={$name}");

        return 'queued';
    }

    protected function makeRequest(string $url): ?string
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error) {
            Log::warning("⚠️ Elite curl error: {$error}");
            return null;
        }

        if ($httpCode === 429) {
            Log::warning("⏳ Elite: Rate limit (429), 5 წამი...");
            sleep(5);
            return null;
        }

        if ($httpCode !== 200) {
            Log::warning("⚠️ Elite: HTTP {$httpCode} | url={$url}");
            return null;
        }

        return $response ?: null;
    }
}