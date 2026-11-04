<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kiểm tra role người dùng.
 * Dùng: Route::middleware('role:admin') hoặc 'role:receptionist,admin'
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $isAdminPath = $request->is('admin*');
        $isStaffPath = $isAdminPath || $request->is('staff*') || $request->is('receptionist*');

        $userId = null;
        if ($isStaffPath) {
            $userId = session('staff_user_id') ?: session('admin_user_id') ?: Auth::guard('staff')->id() ?: Auth::guard('admin')->id();
            if (! $userId && session('auth_user_id')) {
                $userCheck = User::find(session('auth_user_id'));
                if ($userCheck && in_array($userCheck->role, ['admin', 'receptionist'], true)) {
                    $userId = $userCheck->id;
                }
            }
        } else {
            $userId = session('customer_user_id') ?: Auth::guard('customer')->id();
            if (! $userId && session('auth_user_id')) {
                $userCheck = User::find(session('auth_user_id'));
                if ($userCheck && $userCheck->role === 'customer') {
                    $userId = $userCheck->id;
                }
            }
        }

        $loginRoute = $isAdminPath ? 'admin.login' : ($isStaffPath ? 'staff.login' : 'login');

        if (! $userId) {
            return redirect()->route($loginRoute)->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        $user = User::find($userId);

        if (! $user || ! in_array($user->role, $roles, true)) {
            if ($user) {
                $targetRedirect = match ($user->role) {
                    'admin' => route('admin.dashboard'),
                    'receptionist' => route('staff.bookings'),
                    default => route('home'),
                };

                return redirect($targetRedirect)->with('error', 'Tài khoản của bạn không có quyền truy cập trang này.');
            }

            return redirect()->route($loginRoute)->with('error', 'Tài khoản không hợp lệ hoặc không có quyền truy cập.');
        }

        session([
            'auth_user_id' => $user->id,
            'user_id'      => $user->id,
            'user' => [
                'id'       => $user->id,
                'fullname' => $user->fullname,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'role'     => $user->role,
                'verified' => $user->verified,
            ],
        ]);

        Auth::guard('web')->setUser($user);

        return $next($request);
    }
}
