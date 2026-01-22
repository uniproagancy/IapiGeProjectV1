<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Services\Products\ZoommerProduct;
use App\Services\Translation\GoogleTranslation;
use Illuminate\Http\Request;

class ZoommerController extends Controller
{
    //
    public function scan()
    {
        return app(ZoommerProduct::class)->scanAllIds();
    }
}
