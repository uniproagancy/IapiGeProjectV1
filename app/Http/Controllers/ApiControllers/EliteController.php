<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Services\Products\EliteScanService;
use Illuminate\Support\Facades\Log;

class EliteController extends Controller
{
    public function scan()
    {
        Log::info("EliteController->scan");

        return app(EliteScanService::class)->scanAllIds();
    }
}