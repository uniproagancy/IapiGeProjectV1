<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderTransaction;
use App\Services\Facebook\FacebookPixelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BOGPaymentController extends Controller
{
    public function callback(Request $request)
    {
        if(!empty($request)) {
            Log::info($request->body);

            $transaction = OrderTransaction::where('payment_order_id', $request->body['order_id'])->first();
            $transaction->update(['response' => $request]);

            if(!empty($transaction)){
                $order = Order::where('id', $transaction->order_id)->first();

                if(!empty($order)){
                    if($request->body['order_status']['key'] === 'completed'){
                        $order->update(['payment_status_id' => 2]);
                        $this->trackPurchase($order);
                    }
                }
            }
        }
    }

    /**
     * ✅ Track Facebook Pixel Purchase Event
     */
    private function trackPurchase($order)
    {
        try {
            // ✅ პროდუქტების ინფორმაცია
            $contents = [];
            $contentIds = [];

            foreach ($order->items as $item) {
                $contents[] = [
                    'id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'item_price' => $item->price,
                ];
                $contentIds[] = $item->product_id;
            }

            $customData = [
                'value' => $order->amount,
                'currency' => 'GEL',
                'content_type' => 'product',
                'contents' => $contents,
                'content_ids' => $contentIds,
                'num_items' => count($contents),
            ];

            app(FacebookPixelService::class)->trackPurchase(
                value: $order->amount,
                currency: 'GEL',
                params: $customData
            );

            Log::info('✅ Facebook Pixel Purchase tracked', [
                'order_id' => $order->id,
                'amount' => $order->amount,
                'products_count' => count($contents),
                'content_ids' => $contentIds,
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook Pixel Purchase error: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
            ]);
        }
    }
}