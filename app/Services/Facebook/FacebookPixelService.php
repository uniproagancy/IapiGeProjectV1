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
        $this->pixelId     = config('services.facebook.pixel_id', '1280014533998229');
        $this->accessToken = config('services.facebook.access_token', 'EAACRpZCqfAR0BQqXVLIuKjIkrDyqOw4KZC68mb5Ov3nHlnUGwQ55YBtDqSqt3ht8g44ClFnK5eNEqT75qeMcuh749JvbfONOqAfjeaLcYZBNzfJhrdRcZAzy7ThPbvjK5p717jLFvWH6Q7KdDWmLVYIJNQw1fbBKVo9eitQtCgONvp1U1R6XhdDtZAKUnNgZDZD');
        $this->apiVersion  = config('services.facebook.api_version', 'v24.0');
        $this->endpoint    = "https://graph.facebook.com/{$this->apiVersion}/{$this->pixelId}/events";
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

    public function trackPurchaseClean(float $value, string $currency = 'GEL', array $params = [], ?string $eventId = null): bool
    {
        try {
            $customData = ['value' => $value, 'currency' => $currency];
            foreach (['contents', 'content_type', 'content_ids', 'num_items'] as $key) {
                if (isset($params[$key])) $customData[$key] = $params[$key];
            }

            $baseUserData = $this->buildBaseUserData();

            $eventData = [
                'event_name'       => 'Purchase',
                'event_time'       => time(),
                'event_source_url' => 'https://iapi.ge/',
                'action_source'    => 'website',
                'user_data'        => $baseUserData,
                'custom_data'      => $customData,
            ];

            if ($eventId) $eventData['event_id'] = $eventId;

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'         => [$eventData],
                'access_token' => $this->accessToken,
            ]);

            if ($response->successful()) return true;

            Log::error('❌ Purchase clean failed', ['response' => $response->body()]);
            return false;

        } catch (Exception $e) {
            Log::error('❌ trackPurchaseClean error: ' . $e->getMessage());
            return false;
        }
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
        $contents   = array_map(fn($item) => ['id' => $item['id'] ?? null, 'quantity' => $item['quantity'] ?? 1], $items);
        $customData = ['value' => $value, 'currency' => $currency, 'contents' => $contents, 'content_type' => 'product'];
        if ($eventId) $customData['event_id'] = $eventId;
        return $this->trackEvent('InitiateCheckout', $customData);
    }

    public function trackCheckoutWithTest(string $testCode, float $value, string $currency = 'GEL', array $items = [], array $params = [], ?string $eventId = null): bool
    {
        $contents   = array_map(fn($item) => ['id' => $item['id'] ?? null, 'quantity' => $item['quantity'] ?? 1], $items);
        $customData = ['value' => $value, 'currency' => $currency, 'contents' => $contents, 'content_type' => 'product'];
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
                return false;
            }

            if (empty($this->pixelId) || empty($this->accessToken)) {
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
            return true;

        } catch (Exception $e) {
            Log::error("❌ Facebook Pixel event error: {$e->getMessage()}", ['event' => $eventName]);
            return false;
        }
    }

    private function trackEventWithTest(string $eventName, array $customData, string $testCode): bool
    {
        try {
            if (empty($this->pixelId) || empty($this->accessToken)) {
                return false;
            }

            $eventData = $this->buildEventData($eventName, $customData);

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'            => [$eventData],
                'access_token'    => $this->accessToken,
                'test_event_code' => $testCode,
            ]);

            if (!$response->successful()) {
                Log::error("❌ TEST Event failed: {$eventName}", ['response' => $response->body()]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error("❌ trackEventWithTest error: " . $e->getMessage());
            return false;
        }
    }

    private function trackEventWithCustomUserData(string $eventName, array $customData, array $customUserData): bool
    {
        try {
            if (empty($this->pixelId) || empty($this->accessToken)) {
                return false;
            }

            $eventData = [
                'event_name'       => $eventName,
                'event_time'       => time(),
                'event_source_url' => $customData['event_source_url'] ?? request()->url(),
                'action_source'    => 'website',
            ];

            unset($customData['event_source_url']);

            if (isset($customData['event_id'])) {
                $eventData['event_id'] = $customData['event_id'];
                unset($customData['event_id']);
            }

            $userData = $this->buildBaseUserData();

            if (!empty($customUserData['email']))      $userData['em'] = hash('sha256', strtolower(trim($customUserData['email'])));
            if (!empty($customUserData['first_name'])) $userData['fn'] = hash('sha256', strtolower(trim($customUserData['first_name'])));
            if (!empty($customUserData['last_name']))  $userData['ln'] = hash('sha256', strtolower(trim($customUserData['last_name'])));
            if (!empty($customUserData['city']))       $userData['ct'] = hash('sha256', strtolower(trim($customUserData['city'])));
            if (!empty($customUserData['external_id'])) $userData['external_id'] = hash('sha256', (string) $customUserData['external_id']);

            if (!empty($customUserData['phone'])) {
                $phone = preg_replace('/\D/', '', $customUserData['phone']);
                if (strlen($phone) >= 9) $userData['ph'] = hash('sha256', $phone);
            }

            $eventData['user_data'] = $userData;
            if (!empty($customData)) $eventData['custom_data'] = $customData;

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'         => [$eventData],
                'access_token' => $this->accessToken,
            ]);

            if (!$response->successful()) {
                Log::error("❌ Event failed: {$eventName}", ['response' => $response->body()]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error("❌ trackEventWithCustomUserData error: " . $e->getMessage());
            return false;
        }
    }

    private function trackEventWithCustomUserDataAndTest(string $eventName, array $customData, array $customUserData, string $testCode): bool
    {
        try {
            if (empty($this->pixelId) || empty($this->accessToken)) {
                return false;
            }

            $eventData = [
                'event_name'       => $eventName,
                'event_time'       => time(),
                'event_source_url' => $customData['event_source_url'] ?? request()->url(),
                'action_source'    => 'website',
            ];

            unset($customData['event_source_url']);

            if (isset($customData['event_id'])) {
                $eventData['event_id'] = $customData['event_id'];
                unset($customData['event_id']);
            }

            $userData = $this->buildBaseUserData();

            if (!empty($customUserData['email']))      $userData['em'] = hash('sha256', strtolower(trim($customUserData['email'])));
            if (!empty($customUserData['first_name'])) $userData['fn'] = hash('sha256', strtolower(trim($customUserData['first_name'])));
            if (!empty($customUserData['last_name']))  $userData['ln'] = hash('sha256', strtolower(trim($customUserData['last_name'])));
            if (!empty($customUserData['city']))       $userData['ct'] = hash('sha256', strtolower(trim($customUserData['city'])));
            if (!empty($customUserData['external_id'])) $userData['external_id'] = hash('sha256', (string) $customUserData['external_id']);

            if (!empty($customUserData['phone'])) {
                $phone = preg_replace('/\D/', '', $customUserData['phone']);
                if (strlen($phone) >= 9) $userData['ph'] = hash('sha256', $phone);
            }

            $eventData['user_data'] = $userData;
            if (!empty($customData)) $eventData['custom_data'] = $customData;

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'            => [$eventData],
                'access_token'    => $this->accessToken,
                'test_event_code' => $testCode,
            ]);

            if (!$response->successful()) {
                Log::error("❌ TEST Event failed: {$eventName}", ['response' => $response->body()]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error("❌ trackEventWithCustomUserDataAndTest error: " . $e->getMessage());
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

        return $eventData;
    }

    private function buildBaseUserData(): array
    {
        $userData = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
        ];

        $fbp = request()->cookie('_fbp') ?? ($_COOKIE['_fbp'] ?? null);
        $fbc = request()->cookie('_fbc') ?? ($_COOKIE['_fbc'] ?? null);

        if (empty($fbc)) {
            $fbclid = request()->query('fbclid');
            if ($fbclid) {
                $fbc = 'fb.1.' . round(microtime(true) * 1000) . '.' . $fbclid;
            }
        }

        if ($fbp) $userData['fbp'] = $fbp;
        if ($fbc) $userData['fbc'] = $fbc;

        return $userData;
    }

    private function buildUserData($user = null): array
    {
        $userData = $this->buildBaseUserData();

        if (!$user) $user = auth()->user();

        if ($user && auth()->check()) {
            if (!empty($user->email))    $userData['em'] = hash('sha256', strtolower(trim($user->email)));
            if (!empty($user->name))     $userData['fn'] = hash('sha256', strtolower(trim($user->name)));
            if (!empty($user->lastname)) $userData['ln'] = hash('sha256', strtolower(trim($user->lastname)));
            if (!empty($user->city))     $userData['ct'] = hash('sha256', strtolower(trim($user->city)));
            if (!empty($user->state))    $userData['st'] = hash('sha256', strtolower(trim($user->state)));
            if (!empty($user->zip))      $userData['zp'] = hash('sha256', strtolower(trim($user->zip)));
            if (!empty($user->country))  $userData['country'] = hash('sha256', strtolower(trim($user->country)));
            if (!empty($user->id))       $userData['external_id'] = hash('sha256', (string) $user->id);

            if (!empty($user->phone)) {
                $phone = preg_replace('/\D/', '', $user->phone);
                if (strlen($phone) >= 9) $userData['ph'] = hash('sha256', $phone);
            }
        } else {
            if (session()->has('visitor_id')) {
                $userData['external_id'] = hash('sha256', session('visitor_id'));
            }
        }

        return $userData;
    }

    // ─────────────────────────────────────────────
    // PIXEL DATA — ბაზაში შენახვა
    // ─────────────────────────────────────────────

    public function savePixelData(int $orderId, string $eventId, array $userData = []): void
    {
        try {
            $fbp = request()->cookie('_fbp') ?? ($_COOKIE['_fbp'] ?? null);
            $fbc = request()->cookie('_fbc') ?? ($_COOKIE['_fbc'] ?? null);

            if (empty($fbc)) {
                $fbclid = request()->query('fbclid');
                if ($fbclid) {
                    $fbc = 'fb.1.' . round(microtime(true) * 1000) . '.' . $fbclid;
                }
            }

            $data = [
                'order_id'          => $orderId,
                'event_id'          => $eventId,
                'fbp'               => $fbp,
                'fbc'               => $fbc,
                'client_ip'         => request()->ip(),
                'client_user_agent' => request()->userAgent(),
                'external_id'       => hash('sha256', (string) $orderId),
            ];

            if (!empty($userData['email'])) $data['em'] = hash('sha256', strtolower(trim($userData['email'])));
            if (!empty($userData['first_name'])) $data['fn'] = hash('sha256', strtolower(trim($userData['first_name'])));
            if (!empty($userData['last_name']))  $data['ln'] = hash('sha256', strtolower(trim($userData['last_name'])));

            if (!empty($userData['phone'])) {
                $phone = preg_replace('/\D/', '', $userData['phone']);
                if (strlen($phone) >= 9) $data['ph'] = hash('sha256', $phone);
            }

            \App\Models\Order\OrderPixelData::updateOrCreate(
                ['order_id' => $orderId],
                $data
            );

        } catch (\Exception $e) {
            Log::error('❌ savePixelData error: ' . $e->getMessage(), ['order_id' => $orderId]);
        }
    }

    public function trackPurchaseWithPixelData(float $value, string $currency = 'GEL', array $params = [], string $eventId = '', \App\Models\Order\OrderPixelData $pixelData = null): bool
    {
        try {
            $customData = ['value' => $value, 'currency' => $currency];
            foreach (['contents', 'content_type', 'content_ids', 'num_items'] as $key) {
                if (isset($params[$key])) $customData[$key] = $params[$key];
            }

            $userData = [
                'client_ip_address' => $pixelData->client_ip,
                'client_user_agent' => $pixelData->client_user_agent,
            ];

            if ($pixelData->fbp)                   $userData['fbp']         = $pixelData->fbp;
            if ($pixelData->fbc)                   $userData['fbc']         = $pixelData->fbc;
            if ($pixelData->em)                    $userData['em']          = $pixelData->em;
            if ($pixelData->ph)                    $userData['ph']          = $pixelData->ph;
            if ($pixelData->fn)                    $userData['fn']          = $pixelData->fn;
            if ($pixelData->ln)                    $userData['ln']          = $pixelData->ln;
            if (!empty($pixelData->external_id))   $userData['external_id'] = $pixelData->external_id;

            $eventData = [
                'event_name'       => 'Purchase',
                'event_time'       => time(),
                'event_source_url' => 'https://iapi.ge/',
                'action_source'    => 'website',
                'event_id'         => $eventId,
                'user_data'        => $userData,
                'custom_data'      => $customData,
            ];

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'         => [$eventData],
                'access_token' => $this->accessToken,
            ]);

            if (!$response->successful()) {
                Log::error('❌ Purchase failed', ['response' => $response->body()]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error('❌ trackPurchaseWithPixelData error: ' . $e->getMessage());
            return false;
        }
    }

    public function trackPurchaseWithPixelDataAndTest(string $testCode, float $value, string $currency = 'GEL', array $params = [], string $eventId = '', \App\Models\Order\OrderPixelData $pixelData = null): bool
    {
        try {
            $customData = ['value' => $value, 'currency' => $currency];
            foreach (['contents', 'content_type', 'content_ids', 'num_items'] as $key) {
                if (isset($params[$key])) $customData[$key] = $params[$key];
            }

            $userData = [
                'client_ip_address' => $pixelData->client_ip,
                'client_user_agent' => $pixelData->client_user_agent,
            ];

            if ($pixelData->fbp)                   $userData['fbp']         = $pixelData->fbp;
            if ($pixelData->fbc)                   $userData['fbc']         = $pixelData->fbc;
            if ($pixelData->em)                    $userData['em']          = $pixelData->em;
            if ($pixelData->ph)                    $userData['ph']          = $pixelData->ph;
            if ($pixelData->fn)                    $userData['fn']          = $pixelData->fn;
            if ($pixelData->ln)                    $userData['ln']          = $pixelData->ln;
            if (!empty($pixelData->external_id))   $userData['external_id'] = $pixelData->external_id;

            $eventData = [
                'event_name'       => 'Purchase',
                'event_time'       => time(),
                'event_source_url' => 'https://iapi.ge/',
                'action_source'    => 'website',
                'event_id'         => $eventId,
                'user_data'        => $userData,
                'custom_data'      => $customData,
            ];

            $response = Http::timeout(10)->post($this->endpoint, [
                'data'            => [$eventData],
                'access_token'    => $this->accessToken,
                'test_event_code' => $testCode,
            ]);

            if (!$response->successful()) {
                Log::error('❌ TEST Purchase failed', ['response' => $response->body()]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error('❌ trackPurchaseWithPixelDataAndTest error: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // TEST / DEBUG METHODS
    // ─────────────────────────────────────────────

    public function test(): bool
    {
        try {
            $response = Http::timeout(10)->post($this->endpoint, [
                'data' => [[
                    'event_name'    => 'TestEvent',
                    'event_time'    => time(),
                    'action_source' => 'website',
                    'user_data'     => [
                        'client_ip_address' => request()->ip(),
                        'client_user_agent' => request()->userAgent(),
                    ],
                ]],
                'access_token' => $this->accessToken,
            ]);

            if (!$response->successful()) {
                Log::error('❌ Facebook Pixel test failed', ['response' => $response->body()]);
                return false;
            }

            return true;

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

            if (!$response->successful()) {
                Log::error('❌ Facebook Pixel test failed', ['response' => $response->body()]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error('❌ Facebook Pixel test error: ' . $e->getMessage());
            return false;
        }
    }

    public function getConfig(): array
    {
        return [
            'pixel_id'    => $this->pixelId,
            'api_version' => $this->apiVersion,
            'endpoint'    => $this->endpoint,
            'configured'  => !empty($this->pixelId) && !empty($this->accessToken),
        ];
    }
}