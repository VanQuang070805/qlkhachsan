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
        // Nếu đã đăng nhập thì chuyển hướng đến trang phù hợp
        if (session('user')) {
            $user = session('user');
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
                ->with('error', 'Tài khoản không tồn tại!');
        }

        // Tương thích các hình thức mật khẩu (bcrypt của Laravel, md5 cũ và văn bản thuần plaintext)
        $validPassword = false;
        try {
            if (Hash::check($password, $user->password)) {
                $validPassword = true;
            }
        } catch (\Throwable $e) {
            // Bắt ngoại lệ nếu chuỗi hash không đúng định dạng Bcrypt
        }

        if (!$validPassword && md5($password) === $user->password) {
            $validPassword = true;
        } elseif (!$validPassword && $password === $user->password) {
            $validPassword = true;
        }

        if (!$validPassword) {
            return back()->withInput($request->only('username'))
                ->with('error', 'Sai mật khẩu!');
        }

        // Chặn nhân viên bị khóa tài khoản
        if (!$user->verified) {
            return back()->withInput($request->only('username'))
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        // Kiểm tra quyền nhân viên (receptionist hoặc admin)
        $role = $user->role ?? 'customer';
        if ($role === 'customer') {
            return back()->withInput($request->only('username'))
                ->with('error', 'Tài khoản khách hàng không được phép đăng nhập tại đây!');
        }

        // Lưu thông tin đăng nhập vào Session của Laravel
        session([
            'auth_user_id' => $user->id,
            'user_id'      => $user->id,  // tương thích AuthCustomMiddleware
            'user' => [
                'id'       => $user->id,
                'fullname' => $user->fullname,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'role'     => $user->role,
                'verified' => $user->verified,
            ],
        ]);

        $redirect = match ($user->role) {
    'admin'        => route('admin.dashboard'),
    'receptionist' => route('staff.bookings'),
    default        => route('home'),
};

return redirect($redirect)
    ->with('success', 'Đăng nhập thành công! Chào mừng quay trở lại, ' . $user->fullname . '.');
    }

    /**
     * Đăng xuất nhân viên khỏi hệ thống.
     */
    public function logout()
    {
        session()->flush();
        return redirect()->route('login')
            ->with('success', 'Bạn đã đăng xuất khỏi hệ thống.');
    }
}
