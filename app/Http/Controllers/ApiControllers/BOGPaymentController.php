<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderTransaction;
use App\Services\Facebook\FacebookPixelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BOGPaymentController extends Controller
{
    public function callback(Request $request)
    {
        if (empty($request->body)) {
            Log::warning('⚠️ BOGPayment: empty request body');
            return;
        }

        Log::info('📦 BOGPayment callback received', ['body' => $request->body]);

        // ✅ order_id შემოწმება
        if (empty($request->body['order_id'])) {
            Log::warning('⚠️ BOGPayment: missing order_id in body');
            return;
        }

        $transaction = OrderTransaction::where('payment_order_id', $request->body['order_id'])->first();

        if (empty($transaction)) {
            Log::warning('⚠️ BOGPayment: transaction not found', [
                'payment_order_id' => $request->body['order_id'],
            ]);
            return;
        }

        $transaction->update(['response' => $request->body]);

        $order = Order::with(['items', 'items.product'])->find($transaction->order_id);

        if (empty($order)) {
            Log::warning('⚠️ BOGPayment: order not found', [
                'order_id' => $transaction->order_id,
            ]);
            return;
        }

        $status = $request->body['order_status']['key'] ?? null;

        if ($status === 'completed') {
            // ✅ მხოლოდ თუ ჯერ არ არის გადახდილი
            if ($order->payment_status_id !== 2) {
                $order->update(['payment_status_id' => 2]);
                $this->trackPurchase($order->fresh(['items', 'items.product']));
            } else {
                Log::info('⏭️ BOGPayment: order already paid, skipping Purchase event', [
                    'order_id' => $order->id,
                ]);
            }
        } else {
            Log::info('ℹ️ BOGPayment: order status is not completed', [
                'order_id' => $order->id,
                'status'   => $status,
            ]);
        }
    }

    private function trackPurchase(Order $order): void
    {
        try {
            if ($order->items->isEmpty()) {
                Log::warning('⚠️ Purchase: order has no items', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            $eventId    = 'purchase_' . time() . '_' . Str::random(6);
            $contents   = [];
            $contentIds = [];

            foreach ($order->items as $item) {
                $contents[]   = [
                    'id'         => $item->product_id,
                    'quantity'   => $item->quantity,
                    'item_price' => $item->price,
                ];
                $contentIds[] = $item->product_id;
            }

            app(FacebookPixelService::class)->trackPurchase(
                value: $order->amount,
                currency: 'GEL',
                params: [
                    'contents'     => $contents,
                    'content_ids'  => $contentIds,
                    'content_type' => 'product',
                    'num_items'    => count($contents),
                ],
                eventId: $eventId
            );

            Log::info('✅ Purchase tracked (BOG Payment)', [
                'order_id' => $order->id,
                'event_id' => $eventId,
                'amount'   => $order->amount,
                'items'    => count($contents),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Purchase pixel error: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
            ]);
        }
    }
}