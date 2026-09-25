<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * Alias: 'guest'
 * Nếu đã đăng nhập → redirect về trang phù hợp với role
 */
class GuestMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $isStaffLogin = $request->is('internalauth*');

        if ($isStaffLogin) {
            $staffId = session('staff_user_id');
            if ($staffId) {
                $user = User::find($staffId);
                if ($user) {
                    $redirect = $user->role === 'admin' ? route('admin.dashboard') : route('staff.bookings');
                    return redirect($redirect);
                }
            }
        } else {
            $customerId = session('customer_user_id');
            if ($customerId) {
                return redirect()->route('home');
            }
        }

        return $next($request);
    }
}