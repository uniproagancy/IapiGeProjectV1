<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Services\Facebook\FacebookPixelService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TBCInstallmentController extends Controller
{
    private string $merchantKey = '416353635-82138e94-8cd4-4553-98ef-195f7dfdbe3d';

    // ============================================
    // Token
    // ============================================

    private function token(): string
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.tbcbank.ge/oauth/token', [
            'client_id'     => 'rpaBGYDgUP6qC07OxkJjxN3jf6SLcwsZ',
            'client_secret' => 'tyttcQg6eDCiW1Y1',
            'grant_type'    => 'client_credentials',
            'scope'         => 'online_installments',
        ]);

        return $response->json('access_token');
    }

    // ============================================
    // Status Check
    // ============================================

    public function status(): void
    {
        $orders = Order::whereIn('payment_id', [7])
            ->where('payment_status_id', 1)
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $token = $this->token(); // ✅ ერთხელ ვიღებთ ყველა order-ისთვის

        foreach ($orders as $order) {
            if (empty($order->transaction->payment_order_id)) {
                continue;
            }

            try {
                $sessionId = $order->transaction->payment_order_id;

                $response = Http::withHeaders([
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])->withBody(json_encode([
                    'merchantKey' => $this->merchantKey,
                ]), 'application/json')
                    ->get("https://api.tbcbank.ge/v1/online-installments/applications/{$sessionId}/status");

                $data   = $response->json();
                dd($data);
//                $status = $data['status'] ?? $data['installmentStatus'] ?? null;
//
//                Log::info('TBC Installment status', [
//                    'order_id'   => $order->id,
//                    'session_id' => $sessionId,
//                    'status'     => $status,
//                    'response'   => $data,
//                ]);
//
//                if ($status === 'Confirmed') {
//                    // ✅ მხოლოდ თუ ჯერ არ არის გადახდილი
//                    if ($order->payment_status_id !== 2) {
//                        $order->update(['payment_status_id' => 2]);
//                        $this->trackPurchase($order->fresh(['items', 'items.product']));
//                    }
//                } elseif (in_array($status, ['Rejected', 'Cancelled', 'Expired'])) {
//                    $order->update(['payment_status_id' => 3]);
//                }

            } catch (\Exception $e) {
                Log::error('TBC status check error: ' . $e->getMessage(), [
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
            if ($order->items->isEmpty()) {
                Log::warning('⚠️ Purchase: order has no items', ['order_id' => $order->id]);
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

            Log::info('✅ Purchase tracked (TBC Installment)', [
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