<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class InternalAuthController extends Controller
{
    public function showStaffLogin()
    {
        $user = $this->currentInternalUser();
        if ($user && $user->role === 'receptionist') {
            return redirect($this->internalLandingRoute($user));
        }

        return view('auth.internal_login', [
            'portal' => 'staff',
            'formAction' => route('staff.login.submit'),
            'portalTitle' => 'Cổng Nhân Viên',
            'portalSubtitle' => 'Đăng nhập hệ thống vận hành lễ tân, sơ đồ phòng và Face ID.',
        ]);
    }

    public function showAdminLogin()
    {
        $user = $this->currentInternalUser();
        if ($user && $user->role === 'admin') {
            return redirect($this->internalLandingRoute($user));
        }

        return view('auth.internal_login', [
            'portal' => 'admin',
            'formAction' => route('admin.login.submit'),
            'portalTitle' => 'Cổng Quản Trị',
            'portalSubtitle' => 'Đăng nhập hệ thống quản trị doanh thu, hạng phòng và phân quyền.',
        ]);
    }

    public function redirectLegacyLogin()
    {
        return redirect()->route('staff.login');
    }

    public function showLogin()
    {
        return $this->showStaffLogin();
    }

    public function loginStaff(Request $request)
    {
        return $this->authenticateByAllowedRoles($request, ['receptionist']);
    }

    public function loginAdmin(Request $request)
    {
        return $this->authenticateByAllowedRoles($request, ['admin']);
    }

    public function login(Request $request)
    {
        return $this->authenticateByAllowedRoles($request, ['receptionist', 'admin']);
    }

    /**
     * @param  array<int, string>  $allowedRoles
     */
    private function authenticateByAllowedRoles(Request $request, array $allowedRoles)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        $user = User::where('username', $username)->first();
        $passwordMatches = Hash::check(
            $password,
            $user?->password ?? '$2y$12$0hJ6Kpv2spWbD3i3U5L1ueYB0vU/1m0hO9uCl0KD6jMkbRr9W5JxS'
        );

        if (! $user || ! $passwordMatches || ! in_array($user->role, $allowedRoles, true)) {
            $message = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['username' => [$message]]], 422);
            }

            return back()->withInput($request->only('username'))->withErrors(['username' => $message]);
        }

        if (! $user->verified) {
            $lockedMessage = 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';
            if ($request->expectsJson()) {
                return response()->json([
                    'errors' => ['username' => [$lockedMessage]],
                ], 422);
            }

            return back()->withInput($request->only('username'))
                ->withErrors(['username' => $lockedMessage]);
        }

        $userData = [
            'id'       => $user->id,
            'fullname' => $user->fullname,
            'email'    => $user->email,
            'phone'    => $user->phone,
            'role'     => $user->role,
            'verified' => $user->verified,
        ];

        $preservedCustomerId = session('customer_user_id');
        $preservedCustomerData = session('customer_user');

        $request->session()->regenerate();

        Auth::guard('staff')->login($user);
        if ($user->isAdmin()) {
            Auth::guard('admin')->login($user);
            session([
                'admin_user_id' => $user->id,
                'admin_user'    => $userData,
            ]);
        } else {
            Auth::guard('admin')->logout();
            session()->forget(['admin_user_id', 'admin_user']);
        }

        Auth::guard('web')->setUser($user);

        session([
            'staff_user_id' => $user->id,
            'staff_user'    => $userData,
            'auth_user_id'  => $user->id,
            'user_id'       => $user->id,
            'user'          => $userData,
        ]);

        if ($preservedCustomerId) {
            session([
                'customer_user_id' => $preservedCustomerId,
                'customer_user'    => $preservedCustomerData,
            ]);
        }

        $redirect = $this->internalLandingRoute($user);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $redirect,
            ]);
        }

        return redirect($redirect);
    }

    private function currentInternalUser(): ?User
    {
        $userId = session('staff_user_id') ?: session('admin_user_id') ?: Auth::guard('staff')->id() ?: Auth::guard('admin')->id();
        if (! $userId) {
            return null;
        }

        $user = User::find($userId);

        return ($user && in_array($user->role, ['admin', 'receptionist'], true) && $user->verified) ? $user : null;
    }

    private function internalLandingRoute(User $user): string
    {
        if ($user->isAdmin()) {
            return route('admin.dashboard');
        }

        if ($user->hasInternalPermission('rp_view_reports')) {
            return route('admin.reports');
        }

        if ($user->hasInternalPermission('bp_view_schedule')) {
            return route('staff.bookings');
        }

        if ($user->hasInternalPermission('tn_refund')) {
            return route('staff.cancellations');
        }

        return route('staff.profile');
    }

    public function logoutStaff(Request $request)
    {
        return $this->performInternalLogout($request, 'staff.login');
    }

    public function logoutAdmin(Request $request)
    {
        return $this->performInternalLogout($request, 'admin.login');
    }

    public function logout(Request $request)
    {
        return $this->performInternalLogout($request, 'staff.login');
    }

    private function performInternalLogout(Request $request, string $redirectRoute)
    {
        $preservedCustomerId = session('customer_user_id');
        $preservedCustomerData = session('customer_user');

        Auth::guard('staff')->logout();
        Auth::guard('admin')->logout();

        if ($preservedCustomerId && ($customer = User::where('role', 'customer')->find($preservedCustomerId))) {
            session()->forget(['staff_user_id', 'staff_user', 'admin_user_id', 'admin_user']);
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            Auth::guard('customer')->login($customer);
            Auth::guard('web')->login($customer);
            session([
                'customer_user_id' => $customer->id,
                'customer_user'    => $preservedCustomerData ?: [
                    'id'       => $customer->id,
                    'fullname' => $customer->fullname,
                    'email'    => $customer->email,
                    'phone'    => $customer->phone,
                    'role'     => $customer->role,
                    'verified' => $customer->verified,
                ],
                'auth_user_id' => $customer->id,
                'user_id'      => $customer->id,
                'user'         => $preservedCustomerData,
            ]);
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route($redirectRoute);
    }
}
