<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ visitor_id — guest მომხმარებლისთვის external_id
        if (app()->runningInConsole() === false) {
            if (!session()->has('visitor_id')) {
                session(['visitor_id' => Str::uuid()->toString()]);
            }
        }
    }
}
