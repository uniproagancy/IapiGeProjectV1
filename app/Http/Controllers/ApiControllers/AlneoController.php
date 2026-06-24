<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Jobs\AlneoScanJob;
use Illuminate\Support\Facades\Log;

class AlneoController extends Controller
{
    /**
     * GET /alneo/scan
     */
    public function scan()
    {
        Log::info("🔎 Alneo Scan: გაშვება ლინკით");

        AlneoScanJob::dispatch()->onQueue('alneo');

        return response()->json([
            'success' => true,
            'message' => 'Alneo სკანი დაიწყო — job queue-შია',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
