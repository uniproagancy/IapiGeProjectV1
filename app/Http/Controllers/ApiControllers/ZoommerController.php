<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Services\Products\ZoommerProduct;
use App\Services\Translation\GoogleTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoommerController extends Controller
{
    //
    public function scan()
    {
        Log::info("ZoommerController->scan");

        return app(ZoommerProduct::class)->scanAllIds();
    }
}
