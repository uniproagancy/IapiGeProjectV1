<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BOGPaymentController extends Controller
{
    //
    public function callback(Request $request)
    {
        Log::info('test');
    }
}
