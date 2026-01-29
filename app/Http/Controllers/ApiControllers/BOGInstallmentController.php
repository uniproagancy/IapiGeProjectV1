<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BOGInstallmentController extends Controller
{
    //
    public function createInstallment(Request $request)
    {
        if (empty($request->order_id)) {
            Log::error('BOGInstallment: missing order_id');
        }
        $order = Order::find($request->order_id);
        return (new \App\Services\Payments\BOGInstallment)->createInstallmentOrder($order, intval($request->month));
    }

    public function createPartInstallment(Request $request)
    {
        if (empty($request->order_id)) {
            Log::error('BOGPartInstallment: missing order_id');
        }
        $order = Order::find($request->order_id);
        return (new \App\Services\Payments\BOGInstallment)->createPartInstallmentOrder($order, intval($request->month));
    }

    public function callbackSuccess(Request $request)
    {
        if ($request->isMethod('POST')) {

        } else {
            return false;
        }
    }

    public function callbackError(Request $request)
    {
        if ($request->isMethod('POST')) {

        } else {
            return false;
        }
    }

    public function redirectSuccess()
    {

    }


    public function redirectReject()
    {

    }


    public function redirectFail()
    {

    }
}
