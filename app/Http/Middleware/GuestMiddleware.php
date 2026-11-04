<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class GuestMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $isStaffLogin = $request->is('internalauth*') || $request->is('staff/login*') || $request->is('admin/login*');

        if ($isStaffLogin) {
            $staffId = session('staff_user_id') ?: session('admin_user_id');
            if ($staffId) {
                $user = User::find($staffId);
                if ($user && in_array($user->role, ['admin', 'receptionist'], true)) {
                    $redirect = $user->role === 'admin' ? route('admin.dashboard') : route('staff.bookings');

                    return redirect($redirect);
                }
            }
        } else {
            $customerId = session('customer_user_id');
            if ($customerId) {
                $customer = User::find($customerId);
                if ($customer && $customer->role === 'customer' && $customer->verified) {
                    return redirect()->route('home');
                }
            }
        }

        return $next($request);
    }
}
