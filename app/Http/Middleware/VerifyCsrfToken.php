<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs excluded from CSRF.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Add webhook or external callback endpoints here if needed
    ];

    /**
     * Wrap parent handle to log rich context on CSRF mismatch (for elusive 419 debugging).
     */
    public function handle($request, Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            \Log::warning('CSRF token mismatch', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'method' => $request->method(),
                'session_id' => $request->session()->getId() ?? null,
                'session_token' => $request->session()->token(),
                'input__token' => Str::limit($request->input('_token') ?? '', 16, '...'),
                'header_csrf' => Str::limit($request->header('X-CSRF-TOKEN') ?? '', 16, '...'),
                'cookie_xsrf' => Str::limit($request->cookie('XSRF-TOKEN') ?? '', 16, '...'),
                'user_agent' => Str::limit($request->userAgent() ?? '', 120, '...'),
                'session_cookie_present' => $request->hasCookie(config('session.cookie')),
                'expected_cookie' => config('session.cookie'),
                'domain' => config('session.domain'),
                'secure' => config('session.secure'),
                'same_site' => config('session.same_site'),
            ]);
            throw $e; // rethrow for default 419 response
        }
    }
}
