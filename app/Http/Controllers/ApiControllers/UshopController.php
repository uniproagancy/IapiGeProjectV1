<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Services\Products\UshopProduct;
use Illuminate\Http\Request;

class UshopController extends Controller
{
    //
    public function index()
    {
        return app(UshopProduct::class)->scanAllIds();
    }
}
