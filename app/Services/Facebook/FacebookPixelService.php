<?php

namespace App\Services\Facebook;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FacebookPixelService
{
    protected string $pixelId;
    protected string $accessToken;
    protected string $apiVersion = 'v24.0';
    protected string $endpoint;

    protected static array $sentEvents = [];

    public function __construct()
    {
        $this->pixelId      = config('services.facebook.pixel_id', '1280014533998229');
        $this->accessToken  = config('services.facebook.access_token', 'EAACRpZCqfAR0BQqXVLIuKjIkrDyqOw4KZC68mb5Ov3nHlnUGwQ55YBtDqSqt3ht8g44ClFnK5eNEqT75qeMcuh749JvbfONOqAfjeaLcYZBNzfJhrdRcZAzy7ThPbvjK5p717jLFvWH6Q7KdDWmLVYIJNQw1fbBKVo9eitQtCgONvp1U1R6XhdDtZAKUnNgZDZD');
        $this->apiVersion   = config('services.facebook.api_version', 'v24.0');
        $this->endpoint     = "https://graph.facebook.com/{$this->apiVersion}/{$this->pixelId}/events";

        if (empty($this->pixelId) || empty($this->accessToken)) {
            Log::warning('⚠️ Facebook Pixel credentials not configured');
        }
    }

    // ✅ FIXED FBC
    private function getValidFbc(): ?string
    {
        $fbc = request()->cookie('_fbc') ?? ($_COOKIE['_fbc'] ?? null);

        if ($fbc && preg_match('/^fb\.1\.\d+\..+$/', $fbc)) {
            return $fbc;
        }

        $fbclid = request()->query('fbclid');

        if ($fbclid) {
            return 'fb.1.' . round(microtime(true) * 1000) . '.' . $fbclid;
        }

        return null;
    }

    // ✅ BASE USER DATA
    private function buildBaseUserData(): array
    {
        $userData = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
        ];

        $fbp = request()->cookie('_fbp') ?? ($_COOKIE['_fbp'] ?? null);
        $fbc = $this->getValidFbc();

        if ($fbp) $userData['fbp'] = $fbp;
        if ($fbc) $userData['fbc'] = $fbc;

        // safety
        if (!empty($userData['fbc']) && !preg_match('/^fb\.1\.\d+\..+$/', $userData['fbc'])) {
            unset($userData['fbc']);
        }

        Log::info('🍪 buildBaseUserData', [
            'has_fbp'   => isset($userData['fbp']),
            'has_fbc'   => isset($userData['fbc']),
            'fbp_value' => $userData['fbp'] ?? 'NULL',
            'fbc_value' => $userData['fbc'] ?? 'NULL',
            'fbclid'    => request()->query('fbclid'),
        ]);

        return $userData;
    }

    // ✅ MAIN EVENT BUILDER
    private function buildEventData(string $eventName, array $customData = []): array
    {
        $eventData = [
            'event_name'       => $eventName,
            'event_time'       => time(),
            'event_source_url' => $customData['event_source_url'] ?? request()->url(),
            'action_source'    => 'website',
            'user_data'        => $this->buildBaseUserData(),
        ];

        unset($customData['event_source_url']);

        if (isset($customData['event_id'])) {
            $eventData['event_id'] = $customData['event_id'];
            unset($customData['event_id']);
        }

        if (!empty($customData)) {
            $eventData['custom_data'] = $customData;
        }

        return $eventData;
    }

    // ✅ SEND EVENT
    public function trackEvent(string $eventName, array $customData = []): bool
    {
        try {
            $eventData = $this->buildEventData($eventName, $customData);

            $response = Http::post($this->endpoint, [
                'data'         => [$eventData],
                'access_token' => $this->accessToken,
            ]);

            return $response->successful();

        } catch (Exception $e) {
            Log::error('Facebook error: ' . $e->getMessage());
            return false;
        }
    }

    // ✅ PUBLIC EVENTS

    public function trackViewContent(array $product): bool
    {
        return $this->trackEvent('ViewContent', [
            'content_name' => $product['name'] ?? null,
            'content_ids'  => [$product['id'] ?? null],
            'content_type' => 'product',
            'value'        => $product['price'] ?? 0,
            'currency'     => 'GEL',
        ]);
    }

    public function trackAddToCart(array $product): bool
    {
        return $this->trackEvent('AddToCart', [
            'content_name' => $product['name'] ?? null,
            'content_ids'  => [$product['id'] ?? null],
            'content_type' => 'product',
            'value'        => $product['price'] ?? 0,
            'currency'     => 'GEL',
        ]);
    }

    public function trackCheckout(float $value): bool
    {
        return $this->trackEvent('InitiateCheckout', [
            'value'    => $value,
            'currency' => 'GEL',
        ]);
    }

    public function trackPurchase(float $value): bool
    {
        return $this->trackEvent('Purchase', [
            'value'    => $value,
            'currency' => 'GEL',
        ]);
    }

    // ✅ SAVE PIXEL DATA
    public function savePixelData(int $orderId): void
    {
        try {
            $fbp = request()->cookie('_fbp') ?? null;
            $fbc = $this->getValidFbc();

            \App\Models\Order\OrderPixelData::updateOrCreate(
                ['order_id' => $orderId],
                [
                    'fbp'       => $fbp,
                    'fbc'       => $fbc,
                    'client_ip' => request()->ip(),
                ]
            );

        } catch (Exception $e) {
            Log::error('savePixelData error: ' . $e->getMessage());
        }
    }
}