<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SetContextSessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $portMap = [
            18080 => 'customer',
            18081 => 'staff',
            18082 => 'admin',
            18083 => 'internal',
        ];
        $portalContext = $portMap[(int) $request->getPort()]
            ?? strtolower(trim((string) ($request->header('X-Portal-Context') ?: config('services.portal.context', 'all'))));
        $isStaffPath = $request->is('staff*')
            || $request->is('admin*')
            || $request->is('receptionist*')
            || $request->is('internalauth*')
            || $request->is('api/v1/staff*')
            || $request->is('api/v1/admin*');

        if ($isStaffPath) {
            $staffId = session('staff_user_id') ?: session('admin_user_id') ?: Auth::guard('staff')->id() ?: Auth::guard('admin')->id();
            $u = $staffId ? User::find($staffId) : null;

            if ($u && in_array($u->role, ['admin', 'receptionist'], true)) {
                $staffUser = session('staff_user');
                if (! is_array($staffUser) || (int) ($staffUser['id'] ?? 0) !== (int) $u->id) {
                    $staffUser = [
                        'id'       => $u->id,
                        'fullname' => $u->fullname,
                        'email'    => $u->email,
                        'phone'    => $u->phone,
                        'role'     => $u->role,
                        'verified' => $u->verified,
                    ];
                }

                Auth::guard('web')->setUser($u);
                Auth::guard('staff')->setUser($u);
                if ($u->isAdmin()) {
                    Auth::guard('admin')->setUser($u);
                }

                session([
                    'staff_user_id' => $u->id,
                    'staff_user'    => $staffUser,
                    'user_id'       => $u->id,
                    'auth_user_id'  => $u->id,
                    'user'          => $staffUser,
                ]);
            } else {
                Auth::guard('web')->forgetUser();
                session()->forget(['user_id', 'auth_user_id', 'user']);
            }
        } else {
            $customerId = session('customer_user_id') ?: Auth::guard('customer')->id();
            if (! $customerId && auth()->viaRemember() && auth()->user()?->role === 'customer' && auth()->user()->verified) {
                $customerId = auth()->id();
                session(['customer_user_id' => $customerId]);
            }

            $u = $customerId ? User::find($customerId) : null;
            if ($u && $u->role === 'customer') {
                $customerUser = session('customer_user');
                if (! is_array($customerUser) || (int) ($customerUser['id'] ?? 0) !== (int) $u->id) {
                    $customerUser = [
                        'id'       => $u->id,
                        'fullname' => $u->fullname,
                        'email'    => $u->email,
                        'phone'    => $u->phone,
                        'role'     => $u->role,
                        'verified' => $u->verified,
                    ];
                }

                Auth::guard('web')->setUser($u);
                Auth::guard('customer')->setUser($u);

                session([
                    'customer_user_id' => $u->id,
                    'customer_user'    => $customerUser,
                    'user_id'          => $u->id,
                    'auth_user_id'     => $u->id,
                    'user'             => $customerUser,
                ]);
            } else {
                Auth::guard('web')->forgetUser();
                session()->forget(['user_id', 'auth_user_id', 'user']);
            }
        }

        if ($portalRedirect = $this->enforcePortalBoundary($request, $portalContext)) {
            return $portalRedirect;
        }

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(self)');

        return $response;
    }

    private function enforcePortalBoundary(Request $request, string $portalContext)
    {
        if ($portalContext === '' || $portalContext === 'all' || $request->is('up')) {
            return null;
        }

        $path = trim($request->path(), '/');

        if ($portalContext === 'staff') {
            if ($path === '') {
                $staffId = session('staff_user_id') ?: Auth::guard('staff')->id();
                return $staffId
                    ? redirect()->route('staff.bookings')
                    : redirect()->route('staff.login');
            }

            $allowed = $request->is('staff*')
                || $request->is('api/v1/staff*')
                || $request->is('receptionist*')
                || $request->is('internalauth*');

            if (! $allowed) {
                return $this->denyPortalMismatch($request, 'Route không thuộc cổng Nhân viên (staff portal).');
            }
        } elseif ($portalContext === 'admin') {
            if ($path === '') {
                $adminId = session('admin_user_id') ?: session('staff_user_id') ?: Auth::guard('admin')->id();
                return $adminId
                    ? redirect()->route('admin.dashboard')
                    : redirect()->route('admin.login');
            }

            $allowed = $request->is('admin*')
                || $request->is('staff*')
                || $request->is('api/v1/admin*')
                || $request->is('api/v1/staff*');

            if (! $allowed) {
                return $this->denyPortalMismatch($request, 'Route không thuộc cổng Quản trị (admin portal).');
            }
        } elseif ($portalContext === 'customer') {
            $forbiddenOnCustomer = $request->is('staff*')
                || $request->is('admin*')
                || $request->is('receptionist*')
                || $request->is('internalauth*')
                || $request->is('api/v1/staff*')
                || $request->is('api/v1/admin*')
                || $request->is('internal/v1/*');

            if ($forbiddenOnCustomer) {
                return $this->denyPortalMismatch($request, 'Route nội bộ không khả dụng trên cổng Khách hàng.');
            }
        } elseif ($portalContext === 'internal') {
            $allowed = $request->is('internal/v1/*')
                || $request->is('api/chatbot/tools/*')
                || $request->is('devices/v1/*');

            if (! $allowed) {
                return $this->denyPortalMismatch($request, 'Chỉ cho phép API nội bộ trên cổng internal.');
            }
        }

        return null;
    }

    private function denyPortalMismatch(Request $request, string $message)
    {
        $corrId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        if ($request->expectsJson() || $request->is('api/*') || $request->is('internal/*')) {
            return response()->json([
                'error' => [
                    'code' => 'PORTAL_CONTEXT_MISMATCH',
                    'message' => $message,
                    'fields' => (object) [],
                    'correlation_id' => $corrId,
                ],
            ], 403)->withHeaders([
                'X-Correlation-ID' => $corrId,
            ]);
        }

        abort(403, $message);
    }
}
