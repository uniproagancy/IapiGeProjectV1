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
    public function handle(Request $request, Closure $next)
    {
        // ✅ Livewire AJAX და POST request-ები გამოვტოვოთ
        if (
            !$request->isMethod('GET') ||
            $request->is('livewire/*') ||
            $request->header('X-Livewire')
        ) {
            return $next($request);
        }

        // ✅ Generate unique cache key for this user + URL
        $cacheKey = 'fb_pageview_' . md5($request->ip() . $request->userAgent() . $request->url());

        Log::info('🔍 Middleware: Checking PageView', [
            'cache_key'      => $cacheKey,
            'url'            => $request->url(),
            'ip'             => $request->ip(),
            'already_tracked' => Cache::has($cacheKey),
        ]);

        if (!Cache::has($cacheKey)) {
            $eventId = 'pv_' . time() . '_' . Str::random(6);

            Log::info('✅ Middleware: Tracking PageView');

            // ✅ პროდუქტის გვერდზე ViewContent Livewire კომპონენტი ითვლის — აქ გამოვტოვოთ
            if (Route::currentRouteName() !== 'web.product.view') {
                app(FacebookPixelService::class)->trackPageView([
                    'event_id' => $eventId,
                ]);
            }

            Cache::put($cacheKey, [
                'event_id'   => $eventId,
                'tracked_at' => now()->toDateTimeString(),
            ], 60);

            view()->share('fb_event_id', $eventId);

        } else {
            $cached = Cache::get($cacheKey);

            Log::warning('⏭️ Middleware: PageView ALREADY TRACKED', [
                'cached_event_id' => $cached['event_id'],
                'tracked_at'      => $cached['tracked_at'],
            ]);

            view()->share('fb_event_id', $cached['event_id']);
        }

        return $next($request);
    }
}