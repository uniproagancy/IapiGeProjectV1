<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BogRefundController extends Controller
{
    /**
     * GET /bog/refund?order_id=xxx&amount=10.5
     */
    public function refund(Request $request)
    {
        $orderId = trim($request->get('order_id', ''));
        $amount  = $request->get('amount');

        if (empty($orderId)) {
            return response()->json(['error' => 'order_id საჭიროა'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $token = $this->getAccessToken();

            if (!$token) {
                return response()->json(['error' => 'BOG token ვერ მოიძებნა'], 500, [], JSON_UNESCAPED_UNICODE);
            }

            $body = [];
            if ($amount !== null && $amount !== '') {
                $body['amount'] = (float) $amount;
            }

            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://api.bog.ge/payments/v1/payment/refund/{$orderId}", $body);

            Log::info("💸 BOG Refund: order_id={$orderId}" . ($amount ? " | amount={$amount}" : ' | სრული') . " | status={$response->status()}");

            return response()->json($response->json(), $response->status(), [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            Log::error("❌ BOG Refund error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    private function getAccessToken(): ?string
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode('10003075' . ':' . 'sziq796zJImm')
            ])
            ->post('https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            Log::error("❌ BOG Auth error: " . $response->status());
            return null;
        }

        return $response->json('access_token');
    }
}