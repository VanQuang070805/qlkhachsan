<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifiedCustomMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $isStaffPath = $request->is('staff*') || $request->is('admin*') || $request->is('receptionist*');
        $user = $isStaffPath ? (session('staff_user') ?? session('user')) : (session('customer_user') ?? session('user'));

        if (!$user || !($user['verified'] ?? false)) {
            session()->put('url.intended', $request->fullUrl());
            return redirect()->route('verify')
                ->with('error', 'Vui lòng xác thực email trước khi tiếp tục.');
        }

        return $next($request);
    }
}