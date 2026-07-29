<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Jobs\ZoommerProductJob;
use App\Services\Products\ZoommerProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoommerController extends Controller
{
    public function scan()
    {
        Log::info("ZoommerController->scan");
        return app(ZoommerProduct::class)->scanAllIds();
    }

    /**
     * GET /zoommer/debug?id=51347
     * კონკრეტული პროდუქტის განახლება debug რეჟიმში
     */
    public function debug(Request $request)
    {
        $productId = (int) $request->get('id');
        if (!$productId) {
            return response()->json(['error' => 'id პარამეტრი საჭიროა'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        Log::info("🔍 Zoommer Debug: პროდუქტის ხელით განახლება | id={$productId}");

        try {
            // პროდუქტის მონაცემები
            $productResponse = Http::timeout(30)
                ->withHeaders([
                    'Accept'          => 'application/json',
                    'Accept-Language' => 'ka',
                    'Referer'         => 'https://zoommer.ge/',
                    'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                    'os'              => 'web',
                    'Cookie'          => 'zoommer-access_token=' . env('ZOOMMER_ACCESS_TOKEN') . '; zoommer-cookie_agreed=true; cf_clearance=' . env('ZOOMMER_CF_CLEARANCE'),
                ])
                ->get("https://zoommer.ge/api/proxy/v1/Products/details?productId={$productId}");

            if (!$productResponse->successful()) {
                return response()->json([
                    'error'  => 'Zoommer API შეცდომა',
                    'status' => $productResponse->status(),
                ], 500, [], JSON_UNESCAPED_UNICODE);
            }

            $data        = $productResponse->json();
            $productData = $data['product'] ?? null;
            $availability = $data['availabilityInStores'] ?? [];

            if (!$productData) {
                return response()->json(['error' => 'პროდუქტი ვერ მოიძებნა Zoommer-ზე'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // availability ინფო
            $tbilisiStores = collect($availability)->filter(
                fn($s) => stripos($s['city'] ?? '', 'Tbilisi') !== false
                    || stripos($s['city'] ?? '', 'თბილისი') !== false
            );

            $storeInfo = $tbilisiStores->map(fn($s) => [
                'branch'  => $s['branchName'],
                'inStock' => $s['inStock'],
            ])->values();

            Log::info("🔍 Zoommer Debug: {$productData['name']} | barCode={$productData['barCode']} | price={$productData['price']}");

            // Job-ის გაშვება სინქრონულად
            $job = new ZoommerProductJob($productData, $availability);
            $job->handle();

            return response()->json([
                'success'        => true,
                'zoommer_id'     => $productId,
                'name'           => $productData['name'],
                'barCode'        => $productData['barCode'],
                'price'          => $productData['price'],
                'previousPrice'  => $productData['previousPrice'],
                'isInStock'      => $productData['isInStock'],
                'tbilisi_stores' => $storeInfo,
                'message'        => 'განახლება დასრულდა — შეამოწმე laravel.log',
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            Log::error("🔍 Zoommer Debug Error: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}