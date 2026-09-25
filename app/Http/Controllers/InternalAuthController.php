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
            $user = session('staff_user');
            $redirect = match ($user['role'] ?? 'customer') {
                'admin' => route('admin.dashboard'),
                'receptionist' => route('staff.bookings'),
                default => route('home'),
            };
            return redirect($redirect);
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
        ]);

        $username = trim($request->input('username', ''));
        $password = $request->input('password', '');

        $user = User::where('username', $username)->first();

        if (!$user) {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Không tìm thấy tài khoản này.']);
        }

        $validPassword = Hash::check($password, $user->password);

        if (!$validPassword) {
            return back()->withInput($request->only('username'))
                ->withErrors(['password' => 'Mật khẩu chưa chính xác.']);
        }

        // Chặn nhân viên bị khóa tài khoản
        if (!$user->verified) {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.']);
        }

        // Kiểm tra quyền nhân viên (receptionist hoặc admin)
        $role = $user->role ?? 'customer';
        if ($role === 'customer') {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Tài khoản này không có quyền truy cập hệ thống nội bộ.']);
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

        $redirect = match ($user->role) {
            'admin'        => route('admin.dashboard'),
            'receptionist' => route('staff.bookings'),
            default        => route('home'),
        };

        return redirect($redirect);
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
