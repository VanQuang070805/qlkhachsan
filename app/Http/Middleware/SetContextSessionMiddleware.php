<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class SetContextSessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $isStaffPath = $request->is('staff*') || $request->is('admin*') || $request->is('receptionist*') || $request->is('internalauth*');

        if ($isStaffPath) {
            $staffId = session('staff_user_id');
            if ($staffId) {
                $staffUser = session('staff_user');
                if (!$staffUser) {
                    $u = User::find($staffId);
                    if ($u) {
                        $staffUser = [
                            'id'       => $u->id,
                            'fullname' => $u->fullname,
                            'email'    => $u->email,
                            'phone'    => $u->phone,
                            'role'     => $u->role,
                            'verified' => $u->verified,
                        ];
                        session(['staff_user' => $staffUser]);
                    }
                }
                session([
                    'user_id'      => $staffId,
                    'auth_user_id' => $staffId,
                    'user'         => $staffUser,
                ]);
            } else {
                session()->forget(['user_id', 'auth_user_id', 'user']);
            }
        } else {
            $customerId = session('customer_user_id');
            if (!$customerId && auth()->viaRemember() && auth()->user()?->role === 'customer' && auth()->user()->verified) {
                $customerId = auth()->id();
                session(['customer_user_id' => $customerId]);
            }
            if ($customerId) {
                $customerUser = session('customer_user');
                if (!$customerUser) {
                    $u = User::find($customerId);
                    if ($u) {
                        $customerUser = [
                            'id'       => $u->id,
                            'fullname' => $u->fullname,
                            'email'    => $u->email,
                            'phone'    => $u->phone,
                            'role'     => $u->role,
                            'verified' => $u->verified,
                        ];
                        session(['customer_user' => $customerUser]);
                    }
                }
                session([
                    'user_id'      => $customerId,
                    'auth_user_id' => $customerId,
                    'user'         => $customerUser,
                ]);
            } else {
                session()->forget(['user_id', 'auth_user_id', 'user']);
            }
        }

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(self)');

        return $response;
    }
}
