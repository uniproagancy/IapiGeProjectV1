<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect('https://iapi.ge');
        }
        $roleId = auth()->user()->role_id;
        if ($roleId == 1 && $roleId == 5) {
            return redirect('https://iapi.ge');
        }
        return $next($request);
    }
}
