<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StoreFbc
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->has('fbclid')) {
            $fbclid = $request->query('fbclid');
            $timestamp = now()->timestamp * 1000; // milliseconds
            $fbc = 'fb.1.' . $timestamp . '.' . $fbclid;

            cookie()->queue(
                cookie('_fbc', $fbc, 90 * 24 * 60, '/', null, true, false) // 90 days
            );
        }

        return $response;
    }
}
