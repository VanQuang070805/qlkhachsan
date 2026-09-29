<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Kiểm tra role người dùng.
 * Dùng: Route::middleware('role:admin') hoặc 'role:receptionist,admin'
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $isStaffPath = $request->is('staff*') || $request->is('admin*') || $request->is('receptionist*');

        $userId = null;
        if ($isStaffPath) {
            $userId = session('staff_user_id');
            if (!$userId && session('auth_user_id')) {
                $userCheck = User::find(session('auth_user_id'));
                if ($userCheck && in_array($userCheck->role, ['admin', 'receptionist'])) {
                    $userId = $userCheck->id;
                }
            }
        } else {
            $userId = session('customer_user_id');
            if (!$userId && session('auth_user_id')) {
                $userCheck = User::find(session('auth_user_id'));
                if ($userCheck && $userCheck->role === 'customer') {
                    $userId = $userCheck->id;
                }
            }
        }

        $loginRoute = $isStaffPath ? 'internalauth.login' : 'login';

        if (!$userId) {
            return redirect()->route($loginRoute)->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        $user = User::find($userId);

        if (!$user || !in_array($user->role, $roles)) {
            // Đảm bảo không bị crash lỗi 403 đỏ màn hình, chuyển hướng mềm mại
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

        // Đồng bộ dữ liệu session active cho request hiện tại
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

        return $next($request);
    }
}
