<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthCustomMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('user_id')) {
            session()->put('url.intended', $request->fullUrl());
            return redirect()->route('login')
                ->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        // Kiểm tra thời gian thực từ CSDL xem tài khoản có bị khóa không
        $dbUser = \App\Models\User::find(session('user_id'));
        if (!$dbUser) {
            session()->forget(['user_id', 'auth_user_id', 'user']);
            return redirect()->route('login')
                ->with('error', 'Tài khoản không tồn tại. Vui lòng đăng nhập lại.');
        }

        $isLocked = ($dbUser->role !== 'customer' && !$dbUser->verified) || ($dbUser->role === 'customer' && !$dbUser->verified && !$dbUser->otp_code);
        if ($isLocked) {
            session()->forget(['user_id', 'auth_user_id', 'user']);
            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        if (!auth()->check()) {
            auth()->loginUsingId(session('user_id'));
        }

        return $next($request);
    }
}