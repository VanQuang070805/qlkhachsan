<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Alias: 'guest'
 * Nếu đã đăng nhập → redirect về trang chủ
 */
class GuestMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (session('auth_user_id')) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}