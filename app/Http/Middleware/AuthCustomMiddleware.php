<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AuthCustomMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $isStaffPath = $request->is('staff*') || $request->is('admin*') || $request->is('receptionist*') || $request->is('internalauth*');

        $userId = null;
        if ($isStaffPath) {
            $userId = session('staff_user_id');
            if (!$userId && session('user_id')) {
                $userCheck = User::find(session('user_id'));
                if ($userCheck && in_array($userCheck->role, ['admin', 'receptionist'])) {
                    $userId = $userCheck->id;
                }
            }
        } else {
            $userId = session('customer_user_id');
            if (!$userId && session('user_id')) {
                $userCheck = User::find(session('user_id'));
                if ($userCheck && $userCheck->role === 'customer') {
                    $userId = $userCheck->id;
                }
            }
        }

        $loginRoute = $isStaffPath ? 'internalauth.login' : 'login';

        if (!$userId) {
            session()->put('url.intended', $request->fullUrl());
            return redirect()->route($loginRoute)
                ->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        // Kiểm tra thời gian thực từ CSDL xem tài khoản có bị khóa không
        $dbUser = User::find($userId);
        if (!$dbUser) {
            if ($isStaffPath) {
                session()->forget(['staff_user_id', 'staff_user']);
            } else {
                session()->forget(['customer_user_id', 'customer_user']);
            }
            session()->forget(['user_id', 'auth_user_id', 'user']);
            return redirect()->route($loginRoute)
                ->with('error', 'Tài khoản không tồn tại. Vui lòng đăng nhập lại.');
        }

        $isLocked = ($dbUser->role !== 'customer' && !$dbUser->verified) || ($dbUser->role === 'customer' && !$dbUser->verified && !$dbUser->otp_code);
        if ($isLocked) {
            if ($isStaffPath) {
                session()->forget(['staff_user_id', 'staff_user']);
            } else {
                session()->forget(['customer_user_id', 'customer_user']);
            }
            session()->forget(['user_id', 'auth_user_id', 'user']);
            return redirect()->route($loginRoute)
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        // Đảm bảo dữ liệu session chính luôn khớp với tài khoản ngữ cảnh hiện tại
        session([
            'user_id'      => $dbUser->id,
            'auth_user_id' => $dbUser->id,
            'user' => [
                'id'       => $dbUser->id,
                'fullname' => $dbUser->fullname,
                'email'    => $dbUser->email,
                'phone'    => $dbUser->phone,
                'role'     => $dbUser->role,
                'verified' => $dbUser->verified,
            ],
        ]);

        if (!auth()->check() || auth()->id() !== $dbUser->id) {
            auth()->loginUsingId($dbUser->id);
        }

        return $next($request);
    }
}
