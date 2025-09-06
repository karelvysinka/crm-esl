<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Ensures that after a successful authentication the session ID was regenerated.
 * Adds a safeguard: if not regenerated yet (edge case), force regeneration.
 */
class RotateSessionId
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->user() && !$request->session()->has('_rotated')) {
            $request->session()->regenerate();
            $request->session()->put('_rotated', true);
        }
        return $response;
    }
}
