<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Facebook\FacebookPixelService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TrackFacebookPageView
{
    /**
     * ✅ გვერდები, სადაც PageView აზრს კარგავს — ადმინი, feed-ები, სერვისული endpoint-ები
     */
    private const EXCLUDED_PATHS = [
        'dashboard',
        'dashboard/*',
        'api/*',
        'livewire/*',
        'facebook-feed',
        'google-feed',
        'generate-google-feed',
        'debug-fbp',
        'update-alta',
        'logout',
        'up',
    ];

    /**
     * ✅ კრაულერები JS-ს არ ასრულებენ — მათი PageView მხოლოდ CAPI-დან მიდიოდა
     *    და მოჩვენებით ვიზიტორებად ითვლებოდა
     */
    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'facebookcatalog',
        'meta-externalagent', 'headless', 'preview', 'monitor', 'curl', 'wget',
        'python-requests', 'go-http-client', 'okhttp', 'axios', 'guzzle',
        'lighthouse', 'pagespeed', 'ahrefs', 'semrush', 'mj12', 'dotbot',
        'petalbot', 'yandex', 'bingpreview', 'whatsapp', 'telegrambot', 'skypeuripreview',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!$this->shouldTrack($request)) {
            return $next($request);
        }

        // ✅ ერთი event_id — სერვერიც და ბრაუზერიც იმავეს იყენებენ, რომ Meta-მ გააერთიანოს
        $cacheKey = 'fb_pageview_' . md5($request->ip() . $request->userAgent() . $request->url());

        if (Cache::has($cacheKey)) {
            view()->share('fb_event_id', Cache::get($cacheKey)['event_id']);

            return $next($request);
        }

        $eventId = 'pv_' . time() . '_' . Str::random(6);

        // ✅ პროდუქტის გვერდზე ViewContent Livewire კომპონენტი ითვლის — აქ გამოვტოვოთ
        if (Route::currentRouteName() !== 'web.products.view') {
            app(FacebookPixelService::class)->trackPageView([
                'event_id' => $eventId,
            ]);
        }

        Cache::put($cacheKey, [
            'event_id'   => $eventId,
            'tracked_at' => now()->toDateTimeString(),
        ], now()->addMinutes(30));

        view()->share('fb_event_id', $eventId);

        return $next($request);
    }

    private function shouldTrack(Request $request): bool
    {
        if (!$request->isMethod('GET')) {
            return false;
        }

        // ✅ Livewire AJAX
        if ($request->header('X-Livewire') || $request->ajax() || $request->wantsJson()) {
            return false;
        }

        if ($request->is(self::EXCLUDED_PATHS)) {
            return false;
        }

        if ($this->isBot($request->userAgent())) {
            return false;
        }

        return true;
    }

    private function isBot(?string $userAgent): bool
    {
        if (empty($userAgent)) {
            return true; // ✅ UA-ს გარეშე მოთხოვნა ბრაუზერი არაა
        }

        return Str::contains(Str::lower($userAgent), self::BOT_PATTERNS);
    }
}
