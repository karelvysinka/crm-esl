<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Trust reverse proxies (e.g. Traefik / Nginx) so Laravel correctly
 * detects HTTPS scheme, host and client IP. Without this the app may
 * believe requests are HTTP, which can cause mismatches for secure
 * cookies / CSRF expectations and lead to 419 errors in some proxy
 * setups.
 */
class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     * Using * is acceptable when sitting behind a single controlled ingress (Traefik).
     * Adjust to an explicit IP list if needed later.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     * (Matches Laravel 11 default constant set)
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
