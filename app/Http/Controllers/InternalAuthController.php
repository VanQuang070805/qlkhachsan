<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class InternalAuthController extends Controller
{
    /**
     * Hiển thị trang đăng nhập nội bộ của nhân viên.
     */
    public function showLogin()
    {
        // Nếu đã đăng nhập nhân viên thì chuyển hướng đến trang phù hợp
        if (session('staff_user')) {
            $userId = session('staff_user_id') ?: session('auth_user_id');
            $user = $userId ? User::find($userId) : null;
            if ($user && in_array($user->role, ['admin', 'receptionist'], true)) {
                return redirect($this->internalLandingRoute($user));
            }
        }

        return view('auth.internal_login');
    }

    /**
     * Xử lý đăng nhập bằng username cho nhân viên.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $username = trim($request->input('username', ''));
        $password = $request->input('password', '');

        $user = User::where('username', $username)->first();
        $passwordMatches = Hash::check(
            $password,
            $user?->password ?? '$2y$12$0hJ6Kpv2spWbD3i3U5L1ueYB0vU/1m0hO9uCl0KD6jMkbRr9W5JxS'
        );

        if (!$user || !$passwordMatches || !in_array($user->role, ['receptionist', 'admin'], true)) {
            $message = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
            if ($request->expectsJson()) return response()->json(['errors' => ['username' => [$message]]], 422);
            return back()->withInput($request->only('username'))->withErrors(['username' => $message]);
        }

        // Chặn nhân viên bị khóa tài khoản
        if (!$user->verified) {
            if ($request->expectsJson()) {
                return response()->json([
                    'errors' => ['username' => ['Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.']]
                ], 422);
            }
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.']);
        }

        $userData = [
            'id'       => $user->id,
            'fullname' => $user->fullname,
            'email'    => $user->email,
            'phone'    => $user->phone,
            'role'     => $user->role,
            'verified' => $user->verified,
        ];

        $request->session()->regenerate();
        auth()->login($user);

        // Lưu session riêng cho Nhân viên (Staff/Admin)
        session([
            'staff_user_id' => $user->id,
            'staff_user'    => $userData,
            'auth_user_id'  => $user->id,
            'user_id'       => $user->id,
            'user'          => $userData,
        ]);

        $redirect = $this->internalLandingRoute($user);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $redirect
            ]);
        }

        return redirect($redirect);
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

        return route('receptionist.profile');
    }

    /**
     * Đăng xuất nhân viên khỏi hệ thống.
     */
    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('internalauth.login');
    }
}
