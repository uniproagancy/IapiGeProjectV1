<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderPixelData;
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
        // ✅ მხოლოდ მოლოდინში მყოფი შეკვეთები — დამუშავებულებს აღარ ვეხებით
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

                $target = Order::find($orderData['shop_order_id']);

                if (empty($target)) {
                    Log::warning('⚠️ BOGInstallment: order not found', [
                        'shop_order_id' => $orderData['shop_order_id'] ?? null,
                    ]);
                    continue;
                }

                $previousStatus = (int) $target->payment_status_id;
                $newStatus      = $orderData['installment_status'] === 'success' ? 2 : 3;

                $target->update(['payment_status_id' => $newStatus]);

                // ✅ Purchase Pixel — მხოლოდ რეალურ გადასვლაზე, ერთხელ
                if ($newStatus === 2 && $previousStatus !== 2) {
                    $this->trackPurchase($target->fresh(['items', 'items.product']));
                } elseif ($newStatus === 2) {
                    Log::info('⏭️ BOGInstallment: order already paid, skipping Purchase event', [
                        'order_id' => $target->id,
                    ]);
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
            // ✅ დეტერმინისტული — Meta 48სთ-იან ფანჯარაში დუბლიკატს თავად გააერთიანებს
            $eventId    = 'purchase_order_' . $order->id;
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

            $params = [
                'contents'     => $contents,
                'content_ids'  => $contentIds,
                'content_type' => 'product',
                'num_items'    => count($contents),
            ];

            $service = app(FacebookPixelService::class);

            // ✅ შეკვეთისას დამახსოვრებული fbp/fbc/IP/UA + იდენტობა.
            //    ეს კოდი cron-ში სრულდება — request()-ს კლიენტთან კავშირი არ აქვს.
            $pixelData = OrderPixelData::where('order_id', $order->id)->first();

            if ($pixelData) {
                $service->trackPurchaseWithPixelData($order->amount, 'GEL', $params, $eventId, $pixelData);
                $pixelData->update(['purchase_event_id' => $eventId]);
            } else {
                $service->trackPurchase($order->amount, 'GEL', $params, $eventId);
            }

            Log::info('✅ Purchase tracked', [
                'order_id'       => $order->id,
                'event_id'       => $eventId,
                'amount'         => $order->amount,
                'items'          => count($contents),
                'has_pixel_data' => !is_null($pixelData),
            ]);

        } catch (\Exception $e) {
            Log::error('Purchase pixel error: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
            ]);
        }
    }
}