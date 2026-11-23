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
        $port = (int) $request->getPort();
        $contextHeader = strtolower(trim((string) ($request->header('X-Portal-Context') ?: config('services.portal.context', ''))));
        $host = strtolower($request->getHost());

        $cookie = match ($port) {
            18080, 10800 => 'rosaliza_customer_session',
            18081, 10801 => 'rosaliza_staff_session',
            18082, 10802 => 'rosaliza_admin_session',
            default => match (true) {
                $contextHeader === 'admin' || str_contains($host, 'admin') => 'rosaliza_admin_session',
                $contextHeader === 'staff' || str_contains($host, 'staff') => 'rosaliza_staff_session',
                $contextHeader === 'customer' => 'rosaliza_customer_session',
                default => null,
            },
        };

        if ($cookie !== null) {
            config(['session.cookie' => $cookie]);
            if (app()->resolved('session')) {
                app('session')->driver()->setName($cookie);
            }
        }

        if (in_array($port, [10800, 10801, 10802], true)) {
            $base = $request->getScheme().'://'.$request->getHost();
            config([
                'services.portal.customer_url' => $base.':10800',
                'services.portal.staff_url' => $base.':10801',
                'services.portal.admin_url' => $base.':10802',
            ]);
        } elseif (in_array($port, [18080, 18081, 18082], true)) {
            $base = $request->getScheme().'://'.$request->getHost();
            config([
                'services.portal.customer_url' => $base.':18080',
                'services.portal.staff_url' => $base.':18081',
                'services.portal.admin_url' => $base.':18082',
            ]);
        }

        return $next($request);
    }
}
