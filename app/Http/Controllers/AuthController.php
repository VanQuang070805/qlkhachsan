<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // Đăng nhập
    // ──────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $role = trim($request->input('role', 'customer'));
        $password = $request->input('password', '');

        if ($role === 'staff') {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);
            $username = trim($request->input('username', ''));
            $user = User::where('username', $username)->first();
        } else {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);
            $email = trim($request->input('email', ''));
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            return back()->withInput($request->only($role === 'staff' ? 'username' : 'email'))
                ->with('error', 'Tài khoản không tồn tại!');
        }

        // Kiểm tra mật khẩu (hỗ trợ Hash Laravel, MD5 cũ, và văn bản thuần plaintext)
        $validPassword = false;
        try {
            if (Hash::check($password, $user->password)) {
                $validPassword = true;
            }
        } catch (\Throwable $e) {
            // Bắt ngoại lệ nếu chuỗi trong CSDL không khớp định dạng Bcrypt
        }

        if (!$validPassword && md5($password) === $user->password) {
            $validPassword = true;
        } elseif (!$validPassword && $password === $user->password) {
            $validPassword = true;
        }

        if (!$validPassword) {
            return back()->withInput($request->only($role === 'staff' ? 'username' : 'email'))
                ->with('error', 'Mật khẩu không đúng.');
        }

        // Chặn người dùng bị khóa tài khoản
        $isLocked = ($user->role !== 'customer' && !$user->verified) || ($user->role === 'customer' && !$user->verified && !$user->otp_code);
        \Illuminate\Support\Facades\Log::info("Login Lock Check Debug:", [
            'email' => $user->email,
            'role' => $user->role,
            'verified' => $user->verified,
            'otp_code' => $user->otp_code,
            'isLocked' => $isLocked
        ]);
        if ($isLocked) {
            return back()->withInput($request->only($role === 'staff' ? 'username' : 'email'))
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        if ($role !== 'staff' && !$user->isVerified()) {
            // Lưu email để trang verify dùng lại
            session(['pending_verify_email' => $user->email]);
            return redirect()->route('verify')
                ->with('error', 'Tài khoản chưa được xác thực. Vui lòng nhập mã OTP.');
        }

        // Đối với staff, kiểm tra xem role có đúng là staff không (receptionist hoặc admin)
        if ($role === 'staff' && !in_array($user->role, ['receptionist', 'admin'])) {
            return back()->withInput($request->only('username'))
                ->with('error', 'Tài khoản khách hàng không được phép đăng nhập tại đây!');
        }

        $this->loginUser($user);

        $fallback = match ($user->role) {
            'admin' => route('admin.dashboard'),
            'receptionist' => route('staff.bookings'),
            default => route('home'),
        };

       return redirect()->intended($fallback)
            ->with('success', 'Đăng nhập thành công. Chào mừng, ' . $user->fullname . '!');
    }

    // ──────────────────────────────────────────────────────────
    // Đăng ký
    // ──────────────────────────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:200',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|string|max:20',
            'password' => 'required|string|min:6',
        ], [
            'email.unique' => 'Email này đã được sử dụng.',
        ]);

        [$otp, $expiresAt] = $this->generateOtp();

       $user = User::create([
            'username'       => $request->email,
            'fullname'       => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'password'       => Hash::make($request->password),
            'role'           => 'customer',
            'verified'       => false,
            'otp_code'       => $otp,
            'otp_expires_at' => $expiresAt,
        ]);
        $this->sendOtpEmail($user->email, $otp, 'Xác thực tài khoản Royal Hotel');

        session(['pending_verify_email' => $user->email]);

        return redirect()->route('verify')
            ->with('success', 'Đăng ký thành công! Mã OTP đã gửi đến ' . $user->email . '.');
    }

    // ──────────────────────────────────────────────────────────
    // Xác thực tài khoản qua OTP
    // ──────────────────────────────────────────────────────────

    public function showVerify()
    {
        $email = session('pending_verify_email');

        if (!$email) {
            return redirect()->route('login');
        }

        return view('auth.verify_account', compact('email'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $email = session('pending_verify_email');
        $user  = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('register')
                ->with('error', 'Phiên xác thực không hợp lệ. Vui lòng đăng ký lại.');
        }

        if (!$user->verified && !$user->otp_code) {
            session()->forget('pending_verify_email');
            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        if ($user->otp_code !== $request->otp) {
            return back()->with('error', 'Mã OTP không đúng. Vui lòng thử lại.');
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return back()->with('error', 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.');
        }

        $user->update([
            'verified'       => true,
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        session()->forget('pending_verify_email');
        $this->loginUser($user);

        return redirect()->intended(route('home'))
            ->with('success', 'Tài khoản đã được xác thực. Chào mừng, ' . $user->fullname . '!');
    }

    // ──────────────────────────────────────────────────────────
    // Quên mật khẩu
    // ──────────────────────────────────────────────────────────

    public function showForgot()
    {
        return view('auth.forgot_password');
    }

    public function sendReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Luôn trả lời thành công để tránh user enumeration
        if ($user) {
            $isLocked = ($user->role !== 'customer' && !$user->verified) || ($user->role === 'customer' && !$user->verified && !$user->otp_code);
            if (!$isLocked) {
                [$otp, $expiresAt] = $this->generateOtp();

                $user->update([
                    'otp_code'       => $otp,
                    'otp_expires_at' => $expiresAt,
                ]);

                $this->sendOtpEmail($user->email, $otp, 'Đặt lại mật khẩu Royal Hotel');

                session(['reset_email' => $user->email]);
            }
        }

        return redirect()->route('password.verify-otp')
            ->with('success', 'Nếu email tồn tại, mã OTP đã được gửi đến hộp thư của bạn.');
    }

    // ──────────────────────────────────────────────────────────
    // Xác thực OTP đặt lại mật khẩu
    // ──────────────────────────────────────────────────────────

    public function showVerifyOtp()
    {
        if (!session('reset_email')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.verify_reset_otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $email = session('reset_email');
        $user  = User::where('email', $email)->first();

        if (!$user || $user->otp_code !== $request->otp) {
            return back()->with('error', 'Mã OTP không đúng.');
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return back()->with('error', 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.');
        }

        // Xác nhận OTP hợp lệ → cho phép đặt lại mật khẩu
        session(['reset_verified' => true]);

        return redirect()->route('password.reset');
    }

    // ──────────────────────────────────────────────────────────
    // Đặt lại mật khẩu
    // ──────────────────────────────────────────────────────────

    public function showReset()
    {
        if (!session('reset_email') || !session('reset_verified')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset_password');
    }

    public function reset(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string',
        ], [
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $email = session('reset_email');
        $user  = User::where('email', $email)->first();

        if (!$user || !session('reset_verified')) {
            return redirect()->route('password.forgot')
                ->with('error', 'Phiên đặt lại mật khẩu không hợp lệ.');
        }

        $user->update([
            'password'       => Hash::make($request->password),
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        session()->forget(['reset_email', 'reset_verified']);

        return redirect()->route('login')
            ->with('success', 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.');
    }

    // ──────────────────────────────────────────────────────────
    // Đăng xuất
    // ──────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        session()->flush();

        return redirect()->route('login')
            ->with('success', 'Bạn đã đăng xuất.');
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    /**
     * Ghi thông tin user vào session (dùng chung cho middleware auth.custom / verified.custom).
     */
    private function loginUser(User $user): void
    {
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
    }

    /**
     * Tạo mã OTP 6 chữ số và thời hạn 10 phút.
     *
     * @return array{string, Carbon}
     */
    private function generateOtp(): array
    {
        $otp       = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(10);
        return [$otp, $expiresAt];
    }

    /**
     * Gửi email chứa mã OTP.
     * Dùng Mail::raw để không cần tạo Mailable riêng.
     */
    private function sendOtpEmail(string $toEmail, string $otp, string $subject): void
    {
        try {
            Mail::raw(
                "Mã OTP của bạn là: {$otp}\n\nMã có hiệu lực trong 10 phút.\n\nNếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.\n\nTrân trọng,\nRoyal Hotel",
                function ($message) use ($toEmail, $subject) {
                    $message->to($toEmail)->subject($subject);
                }
            );
        } catch (\Throwable $e) {
            // Log lỗi email nhưng không crash luồng chính
            logger()->error("Gửi OTP thất bại tới {$toEmail}: " . $e->getMessage());
        }
    }

    /**
     * Gửi lại mã OTP xác thực tài khoản đăng ký.
     */
    public function resendVerifyOtp()
    {
        $email = session('pending_verify_email');
        if (!$email) {
            return redirect()->route('register');
        }

        // Giới hạn 1 phút
        $lastSent = session('last_otp_sent', 0);
        if (time() - $lastSent < 60) {
            $seconds = 60 - (time() - $lastSent);
            return redirect()->route('verify')->with('error', "Vui lòng đợi {$seconds} giây nữa mới được gửi lại mã.");
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('register');
        }

        if (!$user->verified && !$user->otp_code) {
            session()->forget('pending_verify_email');
            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        [$otp, $expiresAt] = $this->generateOtp();
        $user->update([
            'otp_code'       => $otp,
            'otp_expires_at' => $expiresAt,
        ]);

        $this->sendOtpEmail($user->email, $otp, 'Xác thực tài khoản Royal Hotel');
        session(['last_otp_sent' => time()]);

        return redirect()->route('verify')->with('success', 'Mã OTP mới đã được gửi thành công. Vui lòng kiểm tra email của bạn.');
    }

    /**
     * Gửi lại mã OTP đặt lại mật khẩu.
     */
    public function resendResetOtp()
    {
        $email = session('reset_email');
        if (!$email) {
            return redirect()->route('password.forgot');
        }

        // Giới hạn 1 phút
        $lastSent = session('last_otp_sent', 0);
        if (time() - $lastSent < 60) {
            $seconds = 60 - (time() - $lastSent);
            return redirect()->route('password.verify-otp')->with('error', "Vui lòng đợi {$seconds} giây nữa mới được gửi lại mã.");
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            $isLocked = ($user->role !== 'customer' && !$user->verified) || ($user->role === 'customer' && !$user->verified && !$user->otp_code);
            if (!$isLocked) {
                [$otp, $expiresAt] = $this->generateOtp();
                $user->update([
                    'otp_code'       => $otp,
                    'otp_expires_at' => $expiresAt,
                ]);

                $this->sendOtpEmail($user->email, $otp, 'Đặt lại mật khẩu Royal Hotel');
                session(['last_otp_sent' => time()]);

                return redirect()->route('password.verify-otp')->with('success', 'Mã OTP mới đã được gửi. Kiểm tra hộp thư của bạn.');
            }
        }

        return redirect()->route('password.forgot');
    }
}
