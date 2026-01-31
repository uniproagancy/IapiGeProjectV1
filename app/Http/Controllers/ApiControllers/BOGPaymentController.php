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
            Log::info($request->body);
            $transaction = OrderTransaction::where('payment_order_id', $request->body['order_id'])->first();
            $transaction->update(['response' => $request]);
            if(!empty($transaction)){
                $order = Order::where('id', $request->body['order_id'])->first();
                if(!empty($order)){
                    Log::info("Callback");
                    Log::info($request->body['order_status']['key']);
                    if($request->body['order_status']['key'] === 'completed'){
                        $order->update(['payment_status_id' => 2]);
                        Log::info("Payment Completed");
                    }
                }
            }
        }
    }
}
