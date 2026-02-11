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
        // ✅ Generate unique cache key for this user + URL
        $cacheKey = 'fb_pageview_' . md5($request->ip() . $request->userAgent() . $request->url());

        Log::info('🔍 Middleware: Checking PageView', [
            'cache_key' => $cacheKey,
            'url' => $request->url(),
            'ip' => $request->ip(),
            'already_tracked' => Cache::has($cacheKey),
        ]);

        // ✅ Check if already tracked in last 60 seconds
        if (!Cache::has($cacheKey)) {
            $eventId = 'pv_' . time() . '_' . Str::random(6);

            Log::info('✅ Middleware: Tracking PageView');

            if(Route::current() != 'web.product.view') {
                app(FacebookPixelService::class)->trackPageViewWithTest('TEST68876', [
                    'event_id' => $eventId,
                ]);
            }

            // ✅ Cache for 60 seconds
            Cache::put($cacheKey, [
                'event_id' => $eventId,
                'tracked_at' => now()->toDateTimeString(),
            ], 60);

            // Share with views
            view()->share('fb_event_id', $eventId);
        } else {
            $cached = Cache::get($cacheKey);
            Log::warning('⏭️ Middleware: PageView ALREADY TRACKED', [
                'cached_event_id' => $cached['event_id'],
                'tracked_at' => $cached['tracked_at'],
            ]);
            view()->share('fb_event_id', $cached['event_id']);
        }

        return $next($request);
    }
}