<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BOGPaymentController extends Controller
{
    //
    public function callback(Request $request)
    {
        if(!empty($request)) {
            $transaction = OrderTransaction::where('payment_order_id', $request->get('order_id'))->first();
            Log::info($request->order_status->key);
            Log::warning($request->order_status['key']);
            if(!empty($transaction)){
                $order = Order::where('id', $transaction->order_id)->first();
                if(!empty($order)){
                    if($request->order_status->key === 'completed'){
                        $order->update(['payment_status' => 2]);
                    }
                }
            }
        }
    }
}
