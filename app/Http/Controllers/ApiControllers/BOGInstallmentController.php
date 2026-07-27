<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Services\Facebook\FacebookPixelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BOGInstallmentController extends Controller
{
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

    public function checkManualy(Request $request)
    {
        $orderData = (new \App\Services\Payments\BOGInstallment)
            ->installmentCallback($request->order_id);
        dd($orderData);
    }

    public function installmentCheck(Request $request)
    {
        $orders = Order::whereIn('payment_id', [4, 5])
            ->where('payment_status_id', 1)
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        foreach ($orders as $order) {
            if (empty($order->transaction->payment_order_id)) {
                continue;
            }

            try {
                $orderData = (new \App\Services\Payments\BOGInstallment)
                    ->installmentCallback($order->transaction->payment_order_id);

                $newStatus = $orderData['installment_status'] === 'success' ? 2 : 3;

                Order::find($orderData['shop_order_id'])->update([
                    'payment_status_id' => $newStatus,
                ]);

                // ✅ Purchase Pixel — მხოლოდ success-ზე
                if ($newStatus === 2) {
                    $this->trackPurchase(Order::find($orderData['shop_order_id']));
                }

            } catch (\Exception $e) {
                Log::error('BOGInstallment callback error: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                ]);
            }
        }
    }

    // ============================================
    // Facebook Pixel - Purchase
    // ============================================

    private function trackPurchase(Order $order): void
    {
        try {
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
                    'contents'     => $contents,       // ✅ params-ში
                    'content_ids'  => $contentIds,
                    'content_type' => 'product',
                    'num_items'    => count($contents),
                    'order_id'     => $order->id,
                ],
                eventId: $eventId
            );

            Log::info('✅ Purchase tracked', [
                'order_id' => $order->id,
                'event_id' => $eventId,
                'amount'   => $order->amount,
                'items'    => count($contents),
            ]);

        } catch (\Exception $e) {
            Log::error('Purchase pixel error: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
            ]);
        }
    }
}