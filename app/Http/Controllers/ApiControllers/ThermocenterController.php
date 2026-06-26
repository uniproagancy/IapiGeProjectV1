<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Jobs\ThermocenterScanJob;
use Illuminate\Support\Facades\Log;

class ThermocenterController extends Controller
{
    public function scan()
    {
        Log::info('🌡️ Thermocenter: სკანი დაიწყო');
        ThermocenterScanJob::dispatch()->onQueue('thermocenter');
        return response()->json(['status' => 'ok', 'message' => 'Thermocenter scan dispatched']);
    }
}