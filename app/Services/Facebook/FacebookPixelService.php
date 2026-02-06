<?php

namespace App\Services\Facebook;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FacebookPixelService
{
    protected string $pixelId;
    protected string $accessToken;
    protected string $apiVersion = 'v18.0';
    protected string $endpoint;

    /**
     * ✅ Constructor - Initialize Facebook Pixel
     */
    public function __construct()
    {
        $this->pixelId = config('services.facebook.pixel_id', '1280014533998229');
        $this->accessToken = config('services.facebook.access_token', '1565035891434576|X-sOaj_xJT4t1-UnMcNOFO_-SPc');
        $this->apiVersion = config('services.facebook.api_version', 'v18.0');
        if (empty($this->pixelId) || empty($this->accessToken)) {
            Log::warning('⚠️  Facebook Pixel credentials not configured');
        }
        $this->endpoint = "https://graph.facebook.com/{$this->apiVersion}/{$this->pixelId}/events";
    }

    /**
     * ✅ Track PageView event
     */
    public function trackPageView(array $params = []): bool
    {
        return $this->trackEvent('PageView', $params);
    }

    /**
     * ✅ Track Purchase event
     */
    public function trackPurchase(float $value, string $currency = 'GEL', array $params = []): bool
    {
        $eventData = array_merge($params, [
            'value' => $value,
            'currency' => $currency,
        ]);

        return $this->trackEvent('Purchase', $eventData);
    }

    /**
     * ✅ Track AddToCart event
     */
    public function trackAddToCart(array $product, float $value = 0, string $currency = 'GEL', array $params = []): bool
    {
        $contents = [
            [
                'id' => $product['id'] ?? null,
                'quantity' => $product['quantity'] ?? 1,
                'delivery_category' => 'curbside',
            ]
        ];

        $eventData = array_merge($params, [
            'value' => $value,
            'currency' => $currency,
            'contents' => json_encode($contents),
            'content_name' => $product['name'] ?? null,
            'content_type' => 'product',
        ]);

        return $this->trackEvent('AddToCart', $eventData);
    }

    /**
     * ✅ Track ViewContent event
     */
    public function trackViewContent(array $product, array $params = []): bool
    {
        $contents = [
            [
                'id' => $product['id'] ?? null,
                'quantity' => 1,
                'delivery_category' => 'curbside',
            ]
        ];

        $eventData = array_merge($params, [
            'content_name' => $product['name'] ?? null,
            'content_ids' => json_encode([$product['id'] ?? null]),
            'content_type' => 'product',
            'value' => $product['price'] ?? 0,
            'currency' => 'GEL',
            'contents' => json_encode($contents),
        ]);

        return $this->trackEvent('ViewContent', $eventData);
    }

    /**
     * ✅ Track Checkout event
     */
    public function trackCheckout(float $value, string $currency = 'GEL', array $items = [], array $params = []): bool
    {
        $contents = [];
        foreach ($items as $item) {
            $contents[] = [
                'id' => $item['id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
            ];
        }

        $eventData = array_merge($params, [
            'value' => $value,
            'currency' => $currency,
            'contents' => json_encode($contents),
            'content_type' => 'product',
        ]);

        return $this->trackEvent('InitiateCheckout', $eventData);
    }

    /**
     * ✅ Track Custom event
     */
    public function trackCustomEvent(string $eventName, array $params = []): bool
    {
        return $this->trackEvent($eventName, $params);
    }

    /**
     * ✅ Track event - Main method
     */
    public function trackEvent(string $eventName, array $params = []): bool
    {
        try {
            // ✅ Validate required fields
            if (empty($this->pixelId) || empty($this->accessToken)) {
                Log::warning("⚠️  Facebook Pixel not configured, skipping event: {$eventName}");
                return false;
            }

            // ✅ Build event data
            $eventData = $this->buildEventData($eventName, $params);

            Log::info("📤 Sending Facebook Pixel event: {$eventName}", [
                'data' => $eventData,
            ]);

            // ✅ Send to Facebook
            $response = Http::timeout(10)
                ->post($this->endpoint, [
                    'data' => json_encode([$eventData]),
                    'access_token' => $this->accessToken,
                ]);

            if (!$response->successful()) {
                Log::error("❌ Facebook Pixel error: " . $response->status(), [
                    'response' => $response->body(),
                    'event' => $eventName,
                ]);
                return false;
            }

            Log::info("✅ Facebook Pixel event sent: {$eventName}", [
                'response' => $response->json(),
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("❌ Error sending Facebook Pixel event: {$e->getMessage()}", [
                'event' => $eventName,
                'error' => $e,
            ]);
            return false;
        }
    }

    /**
     * ✅ Build event data with standard parameters
     */
    private function buildEventData(string $eventName, array $params = []): array
    {
        $eventData = [
            'event_name' => $eventName,
            'event_time' => time(),
            'event_source_url' => request()->url(),
            'opt_out' => false,
        ];

        // ✅ Add user data if available
        if (auth()->check()) {
            $user = auth()->user();
            $eventData['user_data'] = $this->buildUserData($user);
        }

        // ✅ Merge custom parameters
        $eventData = array_merge($eventData, $params);

        return $eventData;
    }

    /**
     * ✅ Build user data (hashed for privacy)
     */
    private function buildUserData($user): array
    {
        $userData = [];

        // ✅ Email (hashed)
        if (!empty($user->email)) {
            $userData['em'] = hash('sha256', strtolower(trim($user->email)));
        }

        // ✅ Phone (hashed, 10+ digits)
        if (!empty($user->phone)) {
            $phone = preg_replace('/\D/', '', $user->phone);
            if (strlen($phone) >= 10) {
                $userData['ph'] = hash('sha256', $phone);
            }
        }

        // ✅ First name (hashed)
        if (!empty($user->name)) {
            $userData['fn'] = hash('sha256', strtolower(trim($user->name)));
        }

        // ✅ Last name (hashed)
        if (!empty($user->lastname)) {
            $userData['ln'] = hash('sha256', strtolower(trim($user->lastname)));
        }

        // ✅ City
        if (!empty($user->city)) {
            $userData['ct'] = hash('sha256', strtolower(trim($user->city)));
        }

        // ✅ State
        if (!empty($user->state)) {
            $userData['st'] = hash('sha256', strtolower(trim($user->state)));
        }

        // ✅ Zip code
        if (!empty($user->zip)) {
            $userData['zp'] = hash('sha256', strtolower(trim($user->zip)));
        }

        // ✅ Country
        if (!empty($user->country)) {
            $userData['country'] = hash('sha256', strtolower(trim($user->country)));
        }

        // ✅ External ID (customer ID)
        if (!empty($user->id)) {
            $userData['external_id'] = hash('sha256', (string)$user->id);
        }

        return $userData;
    }

    /**
     * ✅ Test Pixel connection
     */
    public function test(): bool
    {
        try {
            Log::info('🧪 Testing Facebook Pixel connection');

            $testData = [
                'event_name' => 'TestEvent',
                'event_time' => time(),
            ];

            $response = Http::timeout(10)
                ->post($this->endpoint, [
                    'data' => json_encode([$testData]),
                    'access_token' => $this->accessToken,
                ]);

            if ($response->successful()) {
                Log::info('✅ Facebook Pixel test successful', [
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error('❌ Facebook Pixel test failed: ' . $response->status(), [
                    'response' => $response->body(),
                ]);
                return false;
            }

        } catch (Exception $e) {
            Log::error('❌ Facebook Pixel test error: ' . $e->getMessage());
            return false;
        }
    }

    public function testWithCode(string $testEventCode = 'TEST36108'): bool
    {
        try {
            Log::info('🧪 Testing Facebook Pixel with test code: ' . $testEventCode);

            $eventData = [
                'event_name' => 'PageView',
                'event_time' => time(),
                'event_source_url' => 'https://iapi.ge/',
                'action_source' => 'website',
                'user_data' => [
                    'em' => hash('sha256', 'test@example.com'),
                    'ph' => hash('sha256', '1234567890'),
                ],
            ];

            $response = Http::timeout(10)
                ->post($this->endpoint, [
                    'data' => json_encode([$eventData]),
                    'access_token' => $this->accessToken,
                    'test_event_code' => $testEventCode, // ✅ აქ ემატება ტესტ კოდი
                ]);

            if ($response->successful()) {
                Log::info('✅ Facebook Pixel test successful', [
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error('❌ Facebook Pixel test failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('❌ Facebook Pixel test error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ Get Pixel configuration
     */
    public function getConfig(): array
    {
        return [
            'pixel_id' => $this->pixelId,
            'api_version' => $this->apiVersion,
            'endpoint' => $this->endpoint,
            'configured' => !empty($this->pixelId) && !empty($this->accessToken),
        ];
    }
}