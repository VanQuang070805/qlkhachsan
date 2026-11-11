<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ConfigurePortalSession
{
    public function handle(Request $request, Closure $next)
    {
        // Cookies are scoped by host, not port. Use a different session name
        // before Laravel starts the session for each public portal.
        $cookie = match ((int) $request->getPort()) {
            18080 => 'rosaliza_customer_session',
            18081 => 'rosaliza_staff_session',
            18082 => 'rosaliza_admin_session',
            default => null,
        };

        if ($cookie !== null) {
            config(['session.cookie' => $cookie]);
        }

        return $next($request);
    }
}
