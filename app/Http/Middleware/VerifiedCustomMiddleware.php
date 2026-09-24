<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifiedCustomMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = session('user');

        if (!$user || !$user['verified']) {
            session()->put('url.intended', $request->fullUrl());
            return redirect()->route('verify')
                ->with('error', 'Vui lòng xác thực email trước khi tiếp tục.');
        }

        return $next($request);
    }
}