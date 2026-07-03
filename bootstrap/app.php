<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('dashboard') || $request->is('dashboard/*')) {
                return route('dashboard.login');
            }
            return route('web.main.index');
        });
//        $middleware->append(\Litespeed\LSCache\LSCacheMiddleware::class);
//        $middleware->append(\Litespeed\LSCache\LSTagsMiddleware::class);
        $middleware->alias([
            'localize' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
            'check.role' => \App\Http\Middleware\CheckRole::class,
            'doNotCacheResponse' => \Spatie\ResponseCache\Middlewares\DoNotCacheResponse::class,
        ]);
        $middleware->encryptCookies(except: [
            '_fbp',
            '_fbc',
        ]);
        $middleware->web(append: [
            \Spatie\ResponseCache\Middlewares\CacheResponse::class,
            \App\Http\Middleware\TrackFacebookPageView::class,
            \App\Http\Middleware\StoreFbc::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('sitemap:generate')->daily();
        $schedule->command('livewire:cleanup')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
