<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\ConfigurePortalSession::class);
        $middleware->web(append: [
            \App\Http\Middleware\CorrelationIdMiddleware::class,
            \App\Http\Middleware\ForceHttps::class,
            \App\Http\Middleware\SetContextSessionMiddleware::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\CorrelationIdMiddleware::class,
        ]);

        // Đăng ký alias middleware
        $middleware->alias([
            'auth.custom'    => App\Http\Middleware\AuthCustomMiddleware::class,
            'verified.custom'=> \App\Http\Middleware\VerifiedCustomMiddleware::class,
            'role'           => \App\Http\Middleware\RoleMiddleware::class,
            'permission'     => \App\Http\Middleware\EnsureInternalPermission::class,
            'guest'          => \App\Http\Middleware\GuestMiddleware::class,
            'idempotency'    => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);

        // Exclude webhook, internal tool, and device routes khỏi CSRF
        $middleware->validateCsrfTokens(except: [
            'api/webhook/*',
            'webhooks/v1/*',
            'internal/v1/*',
            'devices/v1/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
