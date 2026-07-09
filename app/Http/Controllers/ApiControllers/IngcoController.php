<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\IngcoProduct;
use App\Jobs\IngcoProductJob;
use Illuminate\Support\Facades\Log;

class IngcoController extends Controller
{
    public function scan()
    {
        Log::info("🔧 Ingco Scan: გაშვება");

        $models = IngcoProduct::all();

        if ($models->isEmpty()) {
            Log::warning("⚠️ Ingco Scan: db_ingco_products ცარიელია");
            return response()->json([
                'success' => false,
                'message' => 'db_ingco_products ცარიელია — ჯერ Excel ატვირთე',
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        $count = 0;
        foreach ($models as $item) {
            IngcoProductJob::dispatch($item->sku)->onQueue('ingco');
            $count++;
        }

        Log::info("✅ Ingco Scan: {$count} job გაიგზავნა queue-ში");

        return response()->json([
            'success' => true,
            'message' => "Ingco სკანი დაიწყო — {$count} პროდუქტი queue-შია",
            'queued'  => $count,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}