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
        // Đăng ký alias middleware
        $middleware->alias([
            'auth.custom' => App\Http\Middleware\AuthCustomMiddleware::class,
            'verified.custom'=> \App\Http\Middleware\VerifiedCustomMiddleware::class,
            'role'           => \App\Http\Middleware\RoleMiddleware::class,
            'guest'          => \App\Http\Middleware\GuestMiddleware::class,
        ]);

        // Exclude webhook routes khỏi CSRF
        $middleware->validateCsrfTokens(except: [
            'api/webhook/*',
            'chatbot/api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();