<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Services\Products\AltaProduct;
use Illuminate\Http\Request;

class AltaController extends Controller
{
    //
    public function scan()
    {
        return app(AltaProduct::class)->scanAllIds();
    }
}
