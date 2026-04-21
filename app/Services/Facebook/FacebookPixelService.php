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

    // ─────────────────────────────────────────────
    // PUBLIC TRACK METHODS
    // ─────────────────────────────────────────────

    public function trackPageView(array $params = [], ?string $eventId = null): bool
    {
        if ($eventId) $params['event_id'] = $eventId;
        return $this->trackEvent('PageView', $params);
    }

    public function trackPageViewWithTest(string $testCode, array $params = [], ?string $eventId = null): bool
    {
        if ($eventId) $params['event_id'] = $eventId;
        return $this->trackEventWithTest('PageView', $params, $testCode);
    }

    public function trackPurchase(float $value, string $currency = 'GEL', array $params = [], ?string $eventId = null): bool
    {
        $customData = ['value' => $value, 'currency' => $currency];

        foreach (['contents', 'content_type', 'content_ids', 'num_items'] as $key) {
            if (isset($params[$key])) $customData[$key] = $params[$key];
        }

        if ($eventId) $customData['event_id'] = $eventId;

        return $this->trackEvent('Purchase', $customData);
    }

    public function trackPurchaseWithTest(string $testCode, float $value, string $currency = 'GEL', array $params = [], ?string $eventId = null): bool
    {
        $customData = ['value' => $value, 'currency' => $currency];

        foreach (['contents', 'content_type', 'content_ids', 'num_items'] as $key) {
            if (isset($params[$key])) $customData[$key] = $params[$key];
        }

        if ($eventId) $customData['event_id'] = $eventId;

        return $this->trackEventWithTest('Purchase', $customData, $testCode);
    }

    public function trackAddToCart(array $product, float $value = 0, string $currency = 'GEL', array $params = [], ?string $eventId = null): bool
    {
        $customData = [
            'value'        => $value,
            'currency'     => $currency,
            'contents'     => [['id' => $product['id'] ?? null, 'quantity' => $product['quantity'] ?? 1]],
            'content_name' => $product['name'] ?? null,
            'content_type' => 'product',
            'content_ids'  => [$product['id'] ?? null],
        ];

        if (!empty($params['event_source_url'])) $customData['event_source_url'] = $params['event_source_url'];
        if ($eventId) $customData['event_id'] = $eventId;

        return $this->trackEvent('AddToCart', $customData);
    }

    public function trackAddToCartWithTest(string $testCode, array $product, float $value = 0, string $currency = 'GEL', array $params = [], ?string $eventId = null): bool
    {
        $customData = [
            'value'        => $value,
            'currency'     => $currency,
            'contents'     => [['id' => $product['id'] ?? null, 'quantity' => $product['quantity'] ?? 1]],
            'content_name' => $product['name'] ?? null,
            'content_type' => 'product',
            'content_ids'  => [$product['id'] ?? null],
        ];

        if (!empty($params['event_source_url'])) $customData['event_source_url'] = $params['event_source_url'];
        if ($eventId) $customData['event_id'] = $eventId;

        return $this->trackEventWithTest('AddToCart', $customData, $testCode);
    }

    public function trackViewContent(array $product, array $params = [], ?string $eventId = null): bool
    {
        $customData = [
            'content_name' => $product['name'] ?? null,
            'content_ids'  => [$product['id'] ?? null],
            'content_type' => 'product',
            'value'        => $product['price'] ?? 0,
            'currency'     => 'GEL',
            'contents'     => [['id' => $product['id'] ?? null, 'quantity' => 1]],
        ];

        if ($eventId) $customData['event_id'] = $eventId;

        return $this->trackEvent('ViewContent', $customData);
    }

    public function trackViewContentWithTest(string $testCode, array $product, array $params = [], ?string $eventId = null): bool
    {
        $customData = [
            'content_name' => $product['name'] ?? null,
            'content_ids'  => [$product['id'] ?? null],
            'content_type' => 'product',
            'value'        => $product['price'] ?? 0,
            'currency'     => 'GEL',
            'contents'     => [['id' => $product['id'] ?? null, 'quantity' => 1]],
        ];

        if ($eventId) $customData['event_id'] = $eventId;

        return $this->trackEventWithTest('ViewContent', $customData, $testCode);
    }

    public function trackCheckout(float $value, string $currency = 'GEL', array $items = [], array $params = [], ?string $eventId = null): bool
    {
        $contents = array_map(fn($item) => ['id' => $item['id'] ?? null, 'quantity' => $item['quantity'] ?? 1], $items);

        $customData = [
            'value'        => $value,
            'currency'     => $currency,
            'contents'     => $contents,
            'content_type' => 'product',
        ];

        if ($eventId) $customData['event_id'] = $eventId;

        return $this->trackEvent('InitiateCheckout', $customData);
    }

    public function trackCheckoutWithTest(string $testCode, float $value, string $currency = 'GEL', array $items = [], array $params = [], ?string $eventId = null): bool
    {
        $contents = array_map(fn($item) => ['id' => $item['id'] ?? null, 'quantity' => $item['quantity'] ?? 1], $items);

        $customData = [
            'value'        => $value,
            'currency'     => $currency,
            'contents'     => $contents,
            'content_type' => 'product',
        ];

        if ($eventId) $customData['event_id'] = $eventId;

        return $this->trackEventWithTest('InitiateCheckout', $customData, $testCode);
    }

    public function trackLead(array $userData = [], array $customData = [], ?string $eventId = null): bool
    {
        $eventData = array_merge(['content_category' => 'checkout'], $customData);
        if ($eventId) $eventData['event_id'] = $eventId;

        if (!empty($userData)) {
            return $this->trackEventWithCustomUserData('Lead', $eventData, $userData);
        }

        return $this->trackEvent('Lead', $eventData);
    }

    public function trackLeadWithTest(string $testCode, array $userData = [], array $customData = [], ?string $eventId = null): bool
    {
        $eventData = array_merge(['content_category' => 'checkout'], $customData);
        if ($eventId) $eventData['event_id'] = $eventId;

        if (!empty($userData)) {
            return $this->trackEventWithCustomUserDataAndTest('Lead', $eventData, $userData, $testCode);
        }

        return $this->trackEventWithTest('Lead', $eventData, $testCode);
    }

    public function trackCompleteRegistration(array $userData = [], array $customData = [], ?string $eventId = null): bool
    {
        $eventData = array_merge(['content_name' => 'registration', 'status' => 'completed'], $customData);
        if ($eventId) $eventData['event_id'] = $eventId;

        if (!empty($userData)) {
            return $this->trackEventWithCustomUserData('CompleteRegistration', $eventData, $userData);
        }

        return $this->trackEvent('CompleteRegistration', $eventData);
    }

    public function trackCompleteRegistrationWithTest(string $testCode, array $userData = [], array $customData = [], ?string $eventId = null): bool
    {
        $eventData = array_merge(['content_name' => 'registration', 'status' => 'completed'], $customData);
        if ($eventId) $eventData['event_id'] = $eventId;

        if (!empty($userData)) {
            return $this->trackEventWithCustomUserDataAndTest('CompleteRegistration', $eventData, $userData, $testCode);
        }

        return $this->trackEventWithTest('CompleteRegistration', $eventData, $testCode);
    }

    public function trackCustomEvent(string $eventName, array $params = [], ?string $eventId = null): bool
    {
        if ($eventId) $params['event_id'] = $eventId;
        return $this->trackEvent($eventName, $params);
    }

    public function trackCustomEventWithTest(string $testCode, string $eventName, array $params = [], ?string $eventId = null): bool
    {
        if ($eventId) $params['event_id'] = $eventId;
        return $this->trackEventWithTest($eventName, $params, $testCode);
    }

    // ─────────────────────────────────────────────
    // CORE SEND METHODS
    // ─────────────────────────────────────────────

    public function trackEvent(string $eventName, array $customData = []): bool
    {
        try {
            $eventKey = $eventName . '_' . md5(json_encode($customData) . request()->url());

            if (isset(self::$sentEvents[$eventKey])) {
                Log::warning("🚫 DUPLICATE EVENT PREVENTED: {$eventName}", [
                    'event_key'        => $eventKey,
                    'first_sent_at'    => self::$sentEvents[$eventKey]['time'],
                ]);
                return false;
            }

            if (empty($this->pixelId) || empty($this->accessToken)) {
                Log::warning("⚠️ Facebook Pixel not configured, skipping: {$eventName}");
                return false;
            }

            $eventData = $this->buildEventData($eventName, $customData);

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'         => [$eventData],
                'access_token' => $this->accessToken,
            ]);

            if (!$response->successful()) {
                Log::error("❌ Facebook Pixel error: " . $response->status(), [
                    'response' => $response->body(),
                    'event'    => $eventName,
                ]);
                return false;
            }

            self::$sentEvents[$eventKey] = ['time' => now()->toDateTimeString()];

            Log::info("✅ Facebook Pixel event sent: {$eventName}", ['response' => $response->json()]);

            return true;

        } catch (Exception $e) {
            Log::error("❌ Error sending Facebook Pixel event: {$e->getMessage()}", ['event' => $eventName]);
            return false;
        }
    }

    private function trackEventWithTest(string $eventName, array $customData, string $testCode): bool
    {
        try {
            if (empty($this->pixelId) || empty($this->accessToken)) {
                Log::warning("⚠️ Facebook Pixel not configured");
                return false;
            }

            $eventData = $this->buildEventData($eventName, $customData);

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'            => [$eventData],
                'access_token'    => $this->accessToken,
                'test_event_code' => $testCode,
            ]);

            if ($response->successful()) {
                Log::info("✅ TEST Event sent: {$eventName}", ['response' => $response->json()]);
                return true;
            }

            Log::error("❌ TEST Event failed", ['response' => $response->body()]);
            return false;

        } catch (Exception $e) {
            Log::error("❌ Error: " . $e->getMessage());
            return false;
        }
    }

    // ✅ Guest მომხმარებლისთვის — custom user data-ით
    private function trackEventWithCustomUserData(string $eventName, array $customData, array $customUserData): bool
    {
        try {
            if (empty($this->pixelId) || empty($this->accessToken)) {
                Log::warning("⚠️ Facebook Pixel not configured");
                return false;
            }

            $eventData = [
                'event_name'       => $eventName,
                'event_time'       => time(),
                // ✅ event_source_url customData-დან ან request-დან
                'event_source_url' => $customData['event_source_url'] ?? request()->url(),
                'action_source'    => 'website',
            ];

            unset($customData['event_source_url']);

            if (isset($customData['event_id'])) {
                $eventData['event_id'] = $customData['event_id'];
                unset($customData['event_id']);
            }

            // ✅ buildBaseUserData — fbp/fbc/ip/agent ყველასთვის
            $userData = $this->buildBaseUserData();

            if (!empty($customUserData['email'])) {
                $userData['em'] = hash('sha256', strtolower(trim($customUserData['email'])));
            }
            if (!empty($customUserData['phone'])) {
                $phone = preg_replace('/\D/', '', $customUserData['phone']);
                if (strlen($phone) >= 9) { // ✅ 9 — ქართული ნომრისთვის
                    $userData['ph'] = hash('sha256', $phone);
                }
            }
            if (!empty($customUserData['first_name'])) {
                $userData['fn'] = hash('sha256', strtolower(trim($customUserData['first_name'])));
            }
            if (!empty($customUserData['last_name'])) {
                $userData['ln'] = hash('sha256', strtolower(trim($customUserData['last_name'])));
            }
            if (!empty($customUserData['city'])) {
                $userData['ct'] = hash('sha256', strtolower(trim($customUserData['city'])));
            }

            $eventData['user_data'] = $userData;

            if (!empty($customData)) {
                $eventData['custom_data'] = $customData;
            }

            Log::info("📤 Sending Facebook Pixel event: {$eventName}", [
                'has_fbp'   => isset($userData['fbp']),
                'has_fbc'   => isset($userData['fbc']),
                'has_phone' => isset($userData['ph']),
                'has_email' => isset($userData['em']),
                'has_fn'    => isset($userData['fn']),
                'has_ln'    => isset($userData['ln']),
            ]);

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'         => [$eventData],
                'access_token' => $this->accessToken,
            ]);

            if ($response->successful()) {
                Log::info("✅ Event sent: {$eventName}", ['response' => $response->json()]);
                return true;
            }

            Log::error("❌ Event failed", ['response' => $response->body()]);
            return false;

        } catch (Exception $e) {
            Log::error("❌ Error: " . $e->getMessage());
            return false;
        }
    }

    private function trackEventWithCustomUserDataAndTest(string $eventName, array $customData, array $customUserData, string $testCode): bool
    {
        try {
            if (empty($this->pixelId) || empty($this->accessToken)) {
                Log::warning("⚠️ Facebook Pixel not configured");
                return false;
            }

            $eventData = [
                'event_name'       => $eventName,
                'event_time'       => time(),
                // ✅ event_source_url customData-დან ან request-დან
                'event_source_url' => $customData['event_source_url'] ?? request()->url(),
                'action_source'    => 'website',
            ];

            unset($customData['event_source_url']);

            if (isset($customData['event_id'])) {
                $eventData['event_id'] = $customData['event_id'];
                unset($customData['event_id']);
            }

            // ✅ buildBaseUserData — fbp/fbc/ip/agent ყველასთვის
            $userData = $this->buildBaseUserData();

            if (!empty($customUserData['email'])) {
                $userData['em'] = hash('sha256', strtolower(trim($customUserData['email'])));
            }
            if (!empty($customUserData['phone'])) {
                $phone = preg_replace('/\D/', '', $customUserData['phone']);
                if (strlen($phone) >= 9) { // ✅ 9 — ქართული ნომრისთვის
                    $userData['ph'] = hash('sha256', $phone);
                }
            }
            if (!empty($customUserData['first_name'])) {
                $userData['fn'] = hash('sha256', strtolower(trim($customUserData['first_name'])));
            }
            if (!empty($customUserData['last_name'])) {
                $userData['ln'] = hash('sha256', strtolower(trim($customUserData['last_name'])));
            }
            if (!empty($customUserData['city'])) {
                $userData['ct'] = hash('sha256', strtolower(trim($customUserData['city'])));
            }

            $eventData['user_data'] = $userData;

            if (!empty($customData)) {
                $eventData['custom_data'] = $customData;
            }

            Log::info("📤 Sending TEST Facebook Pixel event: {$eventName}", [
                'test_code' => $testCode,
                'has_fbp'   => isset($userData['fbp']),
                'has_phone' => isset($userData['ph']),
                'has_email' => isset($userData['em']),
            ]);

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'            => [$eventData],
                'access_token'    => $this->accessToken,
                'test_event_code' => $testCode,
            ]);

            if ($response->successful()) {
                Log::info("✅ TEST Event sent: {$eventName}", ['response' => $response->json()]);
                return true;
            }

            Log::error("❌ TEST Event failed", ['response' => $response->body()]);
            return false;

        } catch (Exception $e) {
            Log::error("❌ Error: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // BUILD HELPERS
    // ─────────────────────────────────────────────

    private function buildEventData(string $eventName, array $customData = []): array
    {
        $eventSourceUrl = $customData['event_source_url'] ?? request()->url();
        unset($customData['event_source_url']);

        $eventData = [
            'event_name'       => $eventName,
            'event_time'       => time(),
            'event_source_url' => $eventSourceUrl,
            'action_source'    => 'website',
        ];

        if (isset($customData['event_id'])) {
            $eventData['event_id'] = $customData['event_id'];
            unset($customData['event_id']);
        }

        $eventData['user_data'] = $this->buildUserData();

        if (!empty($customData)) {
            $eventData['custom_data'] = $customData;
        }

        Log::info('📊 Facebook Pixel Event Data', [
            'event'            => $eventName,
            'is_authorized'    => auth()->check(),
            'has_email'        => isset($eventData['user_data']['em']),
            'has_phone'        => isset($eventData['user_data']['ph']),
            'has_external_id'  => isset($eventData['user_data']['external_id']),
            'has_fbc'          => isset($eventData['user_data']['fbc']),
            'has_fbp'          => isset($eventData['user_data']['fbp']),
            'has_event_id'     => isset($eventData['event_id']),
        ]);

        return $eventData;
    }

    // ✅ საბაზო მონაცემები — IP, Agent, fbp, fbc — ყველასთვის
    private function buildBaseUserData(): array
    {
        $userData = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
        ];

        $fbp = request()->cookie('_fbp') ?? ($_COOKIE['_fbp'] ?? null);
        $fbc = request()->cookie('_fbc') ?? ($_COOKIE['_fbc'] ?? null);

        if ($fbp) $userData['fbp'] = $fbp;
        if ($fbc) $userData['fbc'] = $fbc;

        Log::info('🍪 buildBaseUserData', [
            'has_fbp'   => isset($userData['fbp']),
            'has_fbc'   => isset($userData['fbc']),
            'fbp_value' => $userData['fbp'] ?? 'NULL',
        ]);

        return $userData;
    }

    // ✅ ავტორიზებული მომხმარებლის მონაცემები
    private function buildUserData($user = null): array
    {
        $userData = $this->buildBaseUserData();

        if (!$user) $user = auth()->user();

        if ($user && auth()->check()) {
            if (!empty($user->email)) {
                $userData['em'] = hash('sha256', strtolower(trim($user->email)));
            }
            if (!empty($user->phone)) {
                $phone = preg_replace('/\D/', '', $user->phone);
                if (strlen($phone) >= 9) {
                    $userData['ph'] = hash('sha256', $phone);
                }
            }
            if (!empty($user->name)) {
                $userData['fn'] = hash('sha256', strtolower(trim($user->name)));
            }
            if (!empty($user->lastname)) {
                $userData['ln'] = hash('sha256', strtolower(trim($user->lastname)));
            }
            if (!empty($user->city)) {
                $userData['ct'] = hash('sha256', strtolower(trim($user->city)));
            }
            if (!empty($user->state)) {
                $userData['st'] = hash('sha256', strtolower(trim($user->state)));
            }
            if (!empty($user->zip)) {
                $userData['zp'] = hash('sha256', strtolower(trim($user->zip)));
            }
            if (!empty($user->country)) {
                $userData['country'] = hash('sha256', strtolower(trim($user->country)));
            }
            if (!empty($user->id)) {
                $userData['external_id'] = hash('sha256', (string) $user->id);
            }

            Log::info('✅ Authorized user data added', [
                'user_id'   => $user->id,
                'has_email' => !empty($user->email),
                'has_phone' => !empty($user->phone),
                'has_fbp'   => isset($userData['fbp']),
            ]);
        } else {
            Log::info('ℹ️ Guest user', [
                'has_fbp' => isset($userData['fbp']),
                'has_fbc' => isset($userData['fbc']),
            ]);
        }

        return $userData;
    }

    // ─────────────────────────────────────────────
    // TEST / DEBUG METHODS
    // ─────────────────────────────────────────────

    public function test(): bool
    {
        try {
            $response = Http::timeout(10)->post($this->endpoint, [
                'data' => [[
                    'event_name'  => 'TestEvent',
                    'event_time'  => time(),
                    'action_source' => 'website',
                    'user_data'   => [
                        'client_ip_address' => request()->ip(),
                        'client_user_agent' => request()->userAgent(),
                    ],
                ]],
                'access_token' => $this->accessToken,
            ]);

            if ($response->successful()) {
                Log::info('✅ Facebook Pixel test successful', ['response' => $response->json()]);
                return true;
            }

            Log::error('❌ Facebook Pixel test failed', ['response' => $response->body()]);
            return false;

        } catch (Exception $e) {
            Log::error('❌ Facebook Pixel test error: ' . $e->getMessage());
            return false;
        }
    }

    public function testWithCode(string $testEventCode = 'TEST36108'): bool
    {
        try {
            $response = Http::timeout(10)->post($this->endpoint, [
                'data' => [[
                    'event_name'       => 'PageView',
                    'event_time'       => time(),
                    'event_source_url' => 'https://iapi.ge/',
                    'action_source'    => 'website',
                    'user_data'        => [
                        'client_ip_address' => request()->ip(),
                        'client_user_agent' => request()->userAgent(),
                    ],
                ]],
                'access_token'    => $this->accessToken,
                'test_event_code' => $testEventCode,
            ]);

            if ($response->successful()) {
                Log::info('✅ Facebook Pixel test successful', ['response' => $response->json()]);
                return true;
            }

            Log::error('❌ Facebook Pixel test failed', ['response' => $response->body()]);
            return false;

        } catch (Exception $e) {
            Log::error('❌ Facebook Pixel test error: ' . $e->getMessage());
            return false;
        }
    }

    public function getConfig(): array
    {
        return [
            'pixel_id'   => $this->pixelId,
            'api_version' => $this->apiVersion,
            'endpoint'   => $this->endpoint,
            'configured' => !empty($this->pixelId) && !empty($this->accessToken),
        ];
    }
}