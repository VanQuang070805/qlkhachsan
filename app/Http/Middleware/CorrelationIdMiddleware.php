<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $incoming = trim((string) $request->header('X-Correlation-ID', ''));
        $correlationId = ($incoming !== '' && preg_match('/^[A-Za-z0-9\-_]{6,64}$/', $incoming))
            ? $incoming
            : (string) Str::ulid();

        $request->attributes->set('correlation_id', $correlationId);

        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
