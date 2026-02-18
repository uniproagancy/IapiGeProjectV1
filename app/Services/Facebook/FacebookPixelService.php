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

    // ✅ Track sent events to prevent duplicates within same request
    protected static array $sentEvents = [];

    /**
     * ✅ Constructor - Initialize Facebook Pixel
     */
    public function __construct()
    {
        $this->pixelId = config('services.facebook.pixel_id', '1280014533998229');
        $this->accessToken = config('services.facebook.access_token', 'EAACRpZCqfAR0BQqXVLIuKjIkrDyqOw4KZC68mb5Ov3nHlnUGwQ55YBtDqSqt3ht8g44ClFnK5eNEqT75qeMcuh749JvbfONOqAfjeaLcYZBNzfJhrdRcZAzy7ThPbvjK5p717jLFvWH6Q7KdDWmLVYIJNQw1fbBKVo9eitQtCgONvp1U1R6XhdDtZAKUnNgZDZD');
        $this->apiVersion = config('services.facebook.api_version', 'v24.0');

        if (empty($this->pixelId) || empty($this->accessToken)) {
            Log::warning('⚠️  Facebook Pixel credentials not configured');
        }

        $this->endpoint = "https://graph.facebook.com/{$this->apiVersion}/{$this->pixelId}/events";
    }

    /**
     * ✅ Track PageView event
     */
    public function trackPageView(array $params = [], ?string $eventId = null): bool
    {
        if ($eventId) {
            $params['event_id'] = $eventId;
        }
        return $this->trackEvent('PageView', $params);
    }

    /**
     * ✅ Track PageView event with Test Code
     */
    public function trackPageViewWithTest(string $testCode, array $params = [], ?string $eventId = null): bool
    {
        if ($eventId) {
            $params['event_id'] = $eventId;
        }
        return $this->trackEventWithTest('PageView', $params, $testCode);
    }

    /**
     * ✅ Track Purchase event
     */
    public function trackPurchase(float $value, string $currency = 'GEL', array $params = [], ?string $eventId = null): bool
    {
        $customData = [
            'value' => $value,
            'currency' => $currency,
        ];

        if (isset($params['contents'])) {
            $customData['contents'] = $params['contents'];
        }
        if (isset($params['content_type'])) {
            $customData['content_type'] = $params['content_type'];
        }
        if (isset($params['content_ids'])) {
            $customData['content_ids'] = $params['content_ids'];
        }
        if (isset($params['num_items'])) {
            $customData['num_items'] = $params['num_items'];
        }
        if ($eventId) {
            $customData['event_id'] = $eventId;
        }

        return $this->trackEvent('Purchase', $customData);
    }

    /**
     * ✅ Track Purchase event with Test Code
     */
    public function trackPurchaseWithTest(string $testCode, float $value, string $currency = 'GEL', array $params = [], ?string $eventId = null): bool
    {
        $customData = [
            'value' => $value,
            'currency' => $currency,
        ];

        if (isset($params['contents'])) {
            $customData['contents'] = $params['contents'];
        }
        if (isset($params['content_type'])) {
            $customData['content_type'] = $params['content_type'];
        }
        if (isset($params['content_ids'])) {
            $customData['content_ids'] = $params['content_ids'];
        }
        if (isset($params['num_items'])) {
            $customData['num_items'] = $params['num_items'];
        }
        if ($eventId) {
            $customData['event_id'] = $eventId;
        }

        return $this->trackEventWithTest('Purchase', $customData, $testCode);
    }

    /**
     * ✅ Track AddToCart event
     */
    public function trackAddToCart(array $product, float $value = 0, string $currency = 'GEL', array $params = [], ?string $eventId = null): bool
    {
        $contents = [
            [
                'id'       => $product['id'] ?? null,
                'quantity' => $product['quantity'] ?? 1,
            ]
        ];

        $customData = [
            'value'        => $value,
            'currency'     => $currency,
            'contents'     => $contents,
            'content_name' => $product['name'] ?? null,
            'content_type' => 'product',
            'content_ids'  => [$product['id'] ?? null],
        ];

        // ✅ event_source_url params-დან
        if (!empty($params['event_source_url'])) {
            $customData['event_source_url'] = $params['event_source_url'];
        }

        if ($eventId) {
            $customData['event_id'] = $eventId;
        }

        return $this->trackEvent('AddToCart', $customData);
    }
    /**
     * ✅ Track AddToCart event with Test Code
     */
    public function trackAddToCartWithTest(string $testCode, array $product, float $value = 0, string $currency = 'GEL', array $params = [], ?string $eventId = null): bool
    {
        $contents = [
            [
                'id'       => $product['id'] ?? null,
                'quantity' => $product['quantity'] ?? 1,
            ]
        ];

        $customData = [
            'value'        => $value,
            'currency'     => $currency,
            'contents'     => $contents,
            'content_name' => $product['name'] ?? null,
            'content_type' => 'product',
            'content_ids'  => [$product['id'] ?? null],
        ];

        // ✅ event_source_url params-დან
        if (!empty($params['event_source_url'])) {
            $customData['event_source_url'] = $params['event_source_url'];
        }

        if ($eventId) {
            $customData['event_id'] = $eventId;
        }

        return $this->trackEventWithTest('AddToCart', $customData, $testCode);
    }

    /**
     * ✅ Track ViewContent event
     */
    public function trackViewContent(array $product, array $params = [], ?string $eventId = null): bool
    {
        $contents = [
            [
                'id' => $product['id'] ?? null,
                'quantity' => 1,
            ]
        ];

        $customData = [
            'content_name' => $product['name'] ?? null,
            'content_ids' => [$product['id'] ?? null],
            'content_type' => 'product',
            'value' => $product['price'] ?? 0,
            'currency' => 'GEL',
            'contents' => $contents,
        ];

        if ($eventId) {
            $customData['event_id'] = $eventId;
        }

        return $this->trackEvent('ViewContent', $customData);
    }

    /**
     * ✅ Track ViewContent event with Test Code
     */
    public function trackViewContentWithTest(string $testCode, array $product, array $params = [], ?string $eventId = null): bool
    {
        $contents = [
            [
                'id' => $product['id'] ?? null,
                'quantity' => 1,
            ]
        ];

        $customData = [
            'content_name' => $product['name'] ?? null,
            'content_ids' => [$product['id'] ?? null],
            'content_type' => 'product',
            'value' => $product['price'] ?? 0,
            'currency' => 'GEL',
            'contents' => $contents,
        ];

        if ($eventId) {
            $customData['event_id'] = $eventId;
        }

        return $this->trackEventWithTest('ViewContent', $customData, $testCode);
    }

    /**
     * ✅ Track InitiateCheckout event
     */
    public function trackCheckout(float $value, string $currency = 'GEL', array $items = [], array $params = [], ?string $eventId = null): bool
    {
        $contents = [];
        foreach ($items as $item) {
            $contents[] = [
                'id' => $item['id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
            ];
        }

        $customData = [
            'value' => $value,
            'currency' => $currency,
            'contents' => $contents,
            'content_type' => 'product',
        ];

        if ($eventId) {
            $customData['event_id'] = $eventId;
        }

        return $this->trackEvent('InitiateCheckout', $customData);
    }

    /**
     * ✅ Track InitiateCheckout event with Test Code
     */
    public function trackCheckoutWithTest(string $testCode, float $value, string $currency = 'GEL', array $items = [], array $params = [], ?string $eventId = null): bool
    {
        $contents = [];
        foreach ($items as $item) {
            $contents[] = [
                'id' => $item['id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
            ];
        }

        $customData = [
            'value' => $value,
            'currency' => $currency,
            'contents' => $contents,
            'content_type' => 'product',
        ];

        if ($eventId) {
            $customData['event_id'] = $eventId;
        }

        return $this->trackEventWithTest('InitiateCheckout', $customData, $testCode);
    }

    /**
     * ✅ Track Lead event
     */
    public function trackLead(array $userData = [], array $customData = [], ?string $eventId = null): bool
    {
        $eventData = [
            'content_category' => 'checkout',
        ];

        if (!empty($customData)) {
            $eventData = array_merge($eventData, $customData);
        }

        if ($eventId) {
            $eventData['event_id'] = $eventId;
        }

        if (!empty($userData)) {
            return $this->trackEventWithCustomUserData('Lead', $eventData, $userData);
        }

        return $this->trackEvent('Lead', $eventData);
    }

    /**
     * ✅ Track Lead with Test Code
     */
    public function trackLeadWithTest(string $testCode, array $userData = [], array $customData = [], ?string $eventId = null): bool
    {
        $eventData = [
            'content_category' => 'checkout',
        ];

        if (!empty($customData)) {
            $eventData = array_merge($eventData, $customData);
        }

        if ($eventId) {
            $eventData['event_id'] = $eventId;
        }

        if (!empty($userData)) {
            return $this->trackEventWithCustomUserDataAndTest('Lead', $eventData, $userData, $testCode);
        }

        return $this->trackEventWithTest('Lead', $eventData, $testCode);
    }

    /**
     * ✅ Track Custom event
     */
    public function trackCustomEvent(string $eventName, array $params = [], ?string $eventId = null): bool
    {
        if ($eventId) {
            $params['event_id'] = $eventId;
        }
        return $this->trackEvent($eventName, $params);
    }

    /**
     * ✅ Track Custom event with Test Code
     */
    public function trackCustomEventWithTest(string $testCode, string $eventName, array $params = [], ?string $eventId = null): bool
    {
        if ($eventId) {
            $params['event_id'] = $eventId;
        }
        return $this->trackEventWithTest($eventName, $params, $testCode);
    }

    /**
     * ✅ Track event - Main method
     */
    public function trackEvent(string $eventName, array $customData = []): bool
    {
        try {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
            $callerChain = [];

            foreach ($trace as $index => $item) {
                if (isset($item['file'])) {
                    $callerChain[] = [
                        'step' => $index,
                        'file' => str_replace(base_path(), '', $item['file']),
                        'line' => $item['line'] ?? 0,
                        'class' => $item['class'] ?? 'N/A',
                        'function' => $item['function'] ?? 'N/A',
                    ];
                }
            }

            Log::info("🔍 FULL STACK TRACE for {$eventName}", [
                'caller_chain' => $callerChain,
                'url' => request()->url(),
                'method' => request()->method(),
            ]);

            $eventKey = $eventName . '_' . md5(json_encode($customData) . request()->url());

            if (isset(self::$sentEvents[$eventKey])) {
                Log::warning("🚫 DUPLICATE EVENT PREVENTED: {$eventName}", [
                    'event_key' => $eventKey,
                    'first_sent_at' => self::$sentEvents[$eventKey]['time'],
                    'first_sent_from' => self::$sentEvents[$eventKey]['caller'],
                    'duplicate_attempt_from' => $callerChain[0] ?? 'unknown',
                ]);
                return false;
            }

            if (empty($this->pixelId) || empty($this->accessToken)) {
                Log::warning("⚠️  Facebook Pixel not configured, skipping event: {$eventName}");
                return false;
            }

            $eventData = $this->buildEventData($eventName, $customData);

            Log::info("📤 Sending Facebook Pixel event: {$eventName}", [
                'event_key' => $eventKey,
                'called_from' => $callerChain[0] ?? 'unknown',
                'has_event_id' => isset($customData['event_id']),
                'data' => $eventData,
            ]);

            $response = Http::timeout(10)
                ->post($this->endpoint, [
                    'data' => [$eventData],
                    'access_token' => $this->accessToken,
                ]);

            if (!$response->successful()) {
                Log::error("❌ Facebook Pixel error: " . $response->status(), [
                    'response' => $response->body(),
                    'event' => $eventName,
                ]);
                return false;
            }

            self::$sentEvents[$eventKey] = [
                'time' => now()->toDateTimeString(),
                'caller' => $callerChain[0] ?? 'unknown',
            ];

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
     * ✅ Track event with Test Code
     */
    private function trackEventWithTest(string $eventName, array $customData, string $testCode): bool
    {
        try {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
            $callerChain = [];

            foreach ($trace as $index => $item) {
                if (isset($item['file'])) {
                    $callerChain[] = [
                        'step' => $index,
                        'file' => str_replace(base_path(), '', $item['file']),
                        'line' => $item['line'] ?? 0,
                        'class' => $item['class'] ?? 'N/A',
                        'function' => $item['function'] ?? 'N/A',
                    ];
                }
            }

            Log::info("🔍 TEST EVENT STACK TRACE for {$eventName}", [
                'test_code' => $testCode,
                'caller_chain' => $callerChain,
            ]);

            if (empty($this->pixelId) || empty($this->accessToken)) {
                Log::warning("⚠️  Facebook Pixel not configured");
                return false;
            }

            $eventData = $this->buildEventData($eventName, $customData);

            Log::info("📤 Sending TEST Facebook Pixel event: {$eventName}", [
                'test_code' => $testCode,
                'called_from' => $callerChain[0] ?? 'unknown',
                'has_event_id' => isset($customData['event_id']),
            ]);

            $response = Http::timeout(10)
                ->post($this->endpoint, [
                    'data' => [$eventData],
                    'access_token' => $this->accessToken,
                    'test_event_code' => $testCode,
                ]);

            if ($response->successful()) {
                Log::info("✅ TEST Event sent: {$eventName}", [
                    'response' => $response->json(),
                    'called_from' => $callerChain[0] ?? 'unknown',
                ]);
                return true;
            }

            Log::error("❌ TEST Event failed", [
                'response' => $response->body(),
            ]);
            return false;

        } catch (Exception $e) {
            Log::error("❌ Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ Track event with custom user data (არაავტორიზებული)
     */
    private function trackEventWithCustomUserData(string $eventName, array $customData, array $customUserData): bool
    {
        try {
            if (empty($this->pixelId) || empty($this->accessToken)) {
                Log::warning("⚠️  Facebook Pixel not configured");
                return false;
            }

            $eventData = [
                'event_name' => $eventName,
                'event_time' => time(),
                'event_source_url' => request()->url(),
                'action_source' => 'website',
            ];

            if (isset($customData['event_id'])) {
                $eventData['event_id'] = $customData['event_id'];
                unset($customData['event_id']);
            }

            $userData = $this->buildBaseUserData();

            if (!empty($customUserData['email'])) {
                $userData['em'] = hash('sha256', strtolower(trim($customUserData['email'])));
            }
            if (!empty($customUserData['phone'])) {
                $phone = preg_replace('/\D/', '', $customUserData['phone']);
                if (strlen($phone) >= 10) {
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

            Log::info("📤 Sending Facebook Pixel event with custom user data: {$eventName}");

            $response = Http::timeout(10)
                ->post($this->endpoint, [
                    'data' => [$eventData],
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

    /**
     * ✅ Track event with custom user data AND test code
     */
    private function trackEventWithCustomUserDataAndTest(string $eventName, array $customData, array $customUserData, string $testCode): bool
    {
        try {
            if (empty($this->pixelId) || empty($this->accessToken)) {
                Log::warning("⚠️  Facebook Pixel not configured");
                return false;
            }

            $eventData = [
                'event_name' => $eventName,
                'event_time' => time(),
                'event_source_url' => request()->url(),
                'action_source' => 'website',
            ];

            if (isset($customData['event_id'])) {
                $eventData['event_id'] = $customData['event_id'];
                unset($customData['event_id']);
            }

            $userData = $this->buildBaseUserData();

            if (!empty($customUserData['email'])) {
                $userData['em'] = hash('sha256', strtolower(trim($customUserData['email'])));
            }
            if (!empty($customUserData['phone'])) {
                $phone = preg_replace('/\D/', '', $customUserData['phone']);
                if (strlen($phone) >= 10) {
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
                'has_email' => !empty($customUserData['email']),
                'has_phone' => !empty($customUserData['phone']),
            ]);

            $response = Http::timeout(10)
                ->post($this->endpoint, [
                    'data' => [$eventData],
                    'access_token' => $this->accessToken,
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

    /**
     * ✅ Build event data with standard parameters
     */
    private function buildEventData(string $eventName, array $customData = []): array
    {
        $eventSourceUrl = $customData['event_source_url'] ?? request()->url();
        unset($customData['event_source_url']);

        $eventData = [
            'event_name' => $eventName,
            'event_time' => time(),
            'event_source_url' => $eventSourceUrl,
            'action_source' => 'website',
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
            'event' => $eventName,
            'is_authorized' => auth()->check(),
            'user_data_keys' => array_keys($eventData['user_data']),
            'has_email' => isset($eventData['user_data']['em']),
            'has_phone' => isset($eventData['user_data']['ph']),
            'has_external_id' => isset($eventData['user_data']['external_id']),
            'has_fbc' => isset($eventData['user_data']['fbc']),
            'has_fbp' => isset($eventData['user_data']['fbp']),
            'has_event_id' => isset($eventData['event_id']),
        ]);

        return $eventData;
    }

    /**
     * ✅ Build base user data (IP, User Agent, fbc, fbp) - საბაზო მონაცემები ყველასთვის
     */
    private function buildBaseUserData(): array
    {
        $userData = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
        ];

        // ✅ fbc და fbp ყველასთვის - ავტორიზებული თუ guest
        if (request()->cookie('_fbp')) {
            $userData['fbp'] = request()->cookie('_fbp');
        }

        if (request()->cookie('_fbc')) {
            $userData['fbc'] = request()->cookie('_fbc');
        }

        return $userData;
    }

    /**
     * ✅ Build user data - ავტორიზებული მომხმარებლის მონაცემებიც ემატება
     */
    private function buildUserData($user = null): array
    {
        // ✅ საბაზო მონაცემები (IP, Agent, fbc, fbp) ყველასთვის
        $userData = $this->buildBaseUserData();

        if (!$user) {
            $user = auth()->user();
        }

        if ($user && auth()->check()) {

            if (!empty($user->email)) {
                $userData['em'] = hash('sha256', strtolower(trim($user->email)));
            }

            if (!empty($user->phone)) {
                $phone = preg_replace('/\D/', '', $user->phone);
                if (strlen($phone) >= 10) {
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
                $userData['external_id'] = hash('sha256', (string)$user->id);
            }

            Log::info('✅ Authorized user data added to Pixel event', [
                'user_id' => $user->id,
                'has_email' => !empty($user->email),
                'has_phone' => !empty($user->phone),
                'has_fbc' => isset($userData['fbc']),
                'has_fbp' => isset($userData['fbp']),
            ]);

        } else {
            Log::info('ℹ️ Guest user data', [
                'ip' => request()->ip(),
                'has_fbp_cookie' => isset($userData['fbp']),
                'has_fbc_cookie' => isset($userData['fbc']),
            ]);
        }

        return $userData;
    }

    /**
     * ✅ Track CompleteRegistration event
     */
    public function trackCompleteRegistration(array $userData = [], array $customData = [], ?string $eventId = null): bool
    {
        $eventData = [
            'content_name' => 'registration',
            'status' => 'completed',
        ];

        if (!empty($customData)) {
            $eventData = array_merge($eventData, $customData);
        }

        if ($eventId) {
            $eventData['event_id'] = $eventId;
        }

        if (!empty($userData)) {
            return $this->trackEventWithCustomUserData('CompleteRegistration', $eventData, $userData);
        }

        return $this->trackEvent('CompleteRegistration', $eventData);
    }

    /**
     * ✅ Track CompleteRegistration with Test Code
     */
    public function trackCompleteRegistrationWithTest(string $testCode, array $userData = [], array $customData = [], ?string $eventId = null): bool
    {
        $eventData = [
            'content_name' => 'registration',
            'status' => 'completed',
        ];

        if (!empty($customData)) {
            $eventData = array_merge($eventData, $customData);
        }

        if ($eventId) {
            $eventData['event_id'] = $eventId;
        }

        if (!empty($userData)) {
            return $this->trackEventWithCustomUserDataAndTest('CompleteRegistration', $eventData, $userData, $testCode);
        }

        return $this->trackEventWithTest('CompleteRegistration', $eventData, $testCode);
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
                'action_source' => 'website',
                'user_data' => [
                    'client_ip_address' => request()->ip(),
                    'client_user_agent' => request()->userAgent(),
                ],
            ];

            $response = Http::timeout(10)
                ->post($this->endpoint, [
                    'data' => [$testData],
                    'access_token' => $this->accessToken,
                ]);

            if ($response->successful()) {
                Log::info('✅ Facebook Pixel test successful', ['response' => $response->json()]);
                return true;
            } else {
                Log::error('❌ Facebook Pixel test failed: ' . $response->status(), ['response' => $response->body()]);
                return false;
            }

        } catch (Exception $e) {
            Log::error('❌ Facebook Pixel test error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ Test with Event Code
     */
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
                    'client_ip_address' => request()->ip(),
                    'client_user_agent' => request()->userAgent(),
                    'em' => hash('sha256', 'test@example.com'),
                    'ph' => hash('sha256', '1234567890'),
                ],
            ];

            $response = Http::timeout(10)
                ->post($this->endpoint, [
                    'data' => [$eventData],
                    'access_token' => $this->accessToken,
                    'test_event_code' => $testEventCode,
                ]);

            if ($response->successful()) {
                Log::info('✅ Facebook Pixel test successful', ['response' => $response->json()]);
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