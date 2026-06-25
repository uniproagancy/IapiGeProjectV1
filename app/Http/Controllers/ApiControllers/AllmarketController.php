<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Jobs\AllmarketScanJob;
use Illuminate\Support\Facades\Log;

class AllmarketController extends Controller
{
    /**
     * GET /allmarket/scan
     */
    public function scan()
    {
        Log::info("🔎 Allmarket Scan: გაშვება ლინკით");

        AllmarketScanJob::dispatch()->onQueue('allmarket');

        return response()->json([
            'success' => true,
            'message' => 'Allmarket სკანი დაიწყო — job queue-შია',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}