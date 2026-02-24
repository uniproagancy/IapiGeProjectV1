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


    public function installmentRedirect(Request $request): void
    {
        Log::warning($request);
    }

    public function installmentCheck(Request $request)
    {
        $orders = Order::whereIn('payment_id', [4,5])->where('payment_status_id', 1)->get();
        if(!empty($orders)) {
            foreach($orders as $order) {
                if(!empty($order->transaction->payment_order_id)) {
                    return (new \App\Services\Payments\BOGInstallment)->installmentCallback($order->transaction->payment_order_id);
                }
            }
        }
    }
}
