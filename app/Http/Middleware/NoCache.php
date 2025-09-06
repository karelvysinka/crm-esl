<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Force no-store caching headers. Applied to sensitive auth endpoints (/login)
 * to avoid browsers serving a stale cached form with an outdated CSRF token
 * after logout, which can lead to intermittent 419 errors on the next submit.
 */
class NoCache
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (method_exists($response, 'header')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT');
        }
        return $response;
    }
}
