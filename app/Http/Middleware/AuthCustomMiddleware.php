<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthCustomMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $isAdminPath = $request->is('admin*');
        $isStaffPath = $isAdminPath || $request->is('staff*') || $request->is('receptionist*') || $request->is('internalauth*');

        $userId = null;
        if ($isStaffPath) {
            $userId = session('staff_user_id') ?: session('admin_user_id') ?: Auth::guard('staff')->id() ?: Auth::guard('admin')->id();
            if (! $userId && session('user_id')) {
                $userCheck = User::find(session('user_id'));
                if ($userCheck && in_array($userCheck->role, ['admin', 'receptionist'], true)) {
                    $userId = $userCheck->id;
                }
            }
        } else {
            $userId = session('customer_user_id') ?: Auth::guard('customer')->id();
            if (! $userId && session('user_id')) {
                $userCheck = User::find(session('user_id'));
                if ($userCheck && $userCheck->role === 'customer') {
                    $userId = $userCheck->id;
                }
            }
        }

        $loginRoute = $isAdminPath ? 'admin.login' : ($isStaffPath ? 'staff.login' : 'login');

        if (! $userId) {
            session()->put('url.intended', $request->getRequestUri());

            return redirect()->route($loginRoute)
                ->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        $dbUser = User::find($userId);
        $validRoleForContext = $isStaffPath
            ? ($dbUser && in_array($dbUser->role, ['admin', 'receptionist'], true))
            : ($dbUser && $dbUser->role === 'customer');

        if (! $dbUser || ! $validRoleForContext) {
            if (! $dbUser) {
                if ($isStaffPath) {
                    session()->forget(['staff_user_id', 'staff_user', 'admin_user_id', 'admin_user']);
                } else {
                    session()->forget(['customer_user_id', 'customer_user']);
                }
                session()->forget(['user_id', 'auth_user_id', 'user']);

                return redirect()->route($loginRoute)
                    ->with('error', 'Tài khoản không tồn tại. Vui lòng đăng nhập lại.');
            }

            if ($isStaffPath && $dbUser->role === 'customer') {
                session()->forget(['staff_user_id', 'staff_user', 'admin_user_id', 'admin_user']);

                return redirect()->route('home')
                    ->with('error', 'Tài khoản của bạn không có quyền truy cập trang này.');
            }

            return redirect()->route($loginRoute)
                ->with('error', 'Vui lòng đăng nhập đúng cổng truy cập để tiếp tục.');
        }

        $isLocked = ($dbUser->role !== 'customer' && ! $dbUser->verified) || ($dbUser->role === 'customer' && ! $dbUser->verified && ! $dbUser->otp_code);
        if ($isLocked) {
            if ($isStaffPath) {
                session()->forget(['staff_user_id', 'staff_user', 'admin_user_id', 'admin_user']);
            } else {
                session()->forget(['customer_user_id', 'customer_user']);
            }
            session()->forget(['user_id', 'auth_user_id', 'user']);

            return redirect()->route($loginRoute)
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        $contextUserData = [
            'id'       => $dbUser->id,
            'fullname' => $dbUser->fullname,
            'email'    => $dbUser->email,
            'phone'    => $dbUser->phone,
            'role'     => $dbUser->role,
            'verified' => $dbUser->verified,
        ];

        session([
            'user_id'      => $dbUser->id,
            'auth_user_id' => $dbUser->id,
            'user'         => $contextUserData,
        ]);

        Auth::guard('web')->setUser($dbUser);
        if ($isStaffPath) {
            Auth::guard('staff')->setUser($dbUser);
            if ($dbUser->isAdmin()) {
                Auth::guard('admin')->setUser($dbUser);
            }
        } else {
            Auth::guard('customer')->setUser($dbUser);
        }

        return $next($request);
    }
}
