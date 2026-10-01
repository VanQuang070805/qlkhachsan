<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

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
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], ['email.required' => 'Vui lòng nhập email.', 'email.email' => 'Email chưa đúng định dạng.', 'password.required' => 'Vui lòng nhập mật khẩu.']);

        $email = mb_strtolower(trim($request->input('email', '')));
        $user = User::where('role', 'customer')->whereRaw('LOWER(email) = ?', [$email])->first();
        $passwordMatches = Hash::check(
            $request->input('password', ''),
            $user?->password ?? '$2y$12$0hJ6Kpv2spWbD3i3U5L1ueYB0vU/1m0hO9uCl0KD6jMkbRr9W5JxS'
        );
        if (!$user || !$passwordMatches) {
            $message = 'Email hoặc mật khẩu không chính xác.';
            if ($request->expectsJson()) return response()->json(['errors' => ['email' => [$message]]], 422);
            return back()->withInput(['email' => $email])->withErrors(['email' => $message]);
        }

        // Chặn người dùng bị khóa tài khoản
        $isLocked = ($user->role !== 'customer' && !$user->verified) || ($user->role === 'customer' && !$user->verified && !$user->otp_code);
        if ($isLocked) {
            if ($request->expectsJson()) {
                return response()->json([
                    'errors' => ['email' => ['Tài khoản chưa sẵn sàng để đăng nhập.']]
                ], 422);
            }
            return back()->withInput(['email' => $email])
                ->withErrors(['email' => 'Tài khoản chưa sẵn sàng để đăng nhập.']);
        }

        if (!$user->isVerified()) {
            // Lưu email để trang verify dùng lại
            session(['pending_verify_email' => $user->email]);
            return redirect()->route('verify')
                ->with('error', 'Tài khoản chưa được xác thực. Vui lòng nhập mã OTP.');
        }

        $remember = $request->boolean('remember');
        $this->loginUser($user, $remember);
        $fallback = route('home');

        if ($request->expectsJson()) {
            $redirectUrl = session()->pull('url.intended', $fallback);
            $jsonRes = response()->json([
                'success' => true,
                'redirect' => $redirectUrl,
            ]);
            if ($remember) $jsonRes->withCookie(cookie('royal_remembered_email', $user->email, 60 * 24 * 30, '/', null, $request->isSecure(), true, false, 'lax'));
            else $jsonRes->withoutCookie('royal_remembered_email');
            return $jsonRes;
        }

        $response = redirect()->intended($fallback);

        return $remember
            ? $response->withCookie(cookie('royal_remembered_email', $user->email, 60 * 24 * 30, '/', null, $request->isSecure(), true, false, 'lax'))
            : $response->withoutCookie('royal_remembered_email');
    }

    // ──────────────────────────────────────────────────────────
    // Đăng ký
    // ──────────────────────────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function account()
    {
        return view('auth.account', ['user' => auth()->user()]);
    }

    public function updateAccount(Request $request)
    {
        $user = auth()->user();
        $request->merge(['email' => mb_strtolower(trim((string) $request->email))]);
        $validated = $request->validate([
            'fullname' => 'required|string|min:2|max:200',
            'email' => ['required', 'email', 'max:200', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'regex:/^0[0-9]{9}$/'],
        ], [
            'fullname.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email chưa đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.',
        ]);

        if ($validated['email'] !== mb_strtolower($user->email)) {
            return back()->withInput()->withErrors(['email' => 'Đổi email cần xác minh lại. Chức năng này hiện chưa được bật.']);
        }

        $user->update($validated);

        return back()->with('success', 'Thông tin cá nhân đã được cập nhật.');
    }

    public function register(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->email))]);
        $request->validate([
            'name'     => 'required|string|min:2|max:200',
            'email'    => 'required|email',
            'phone'    => ['required', 'regex:/^0[0-9]{9}$/'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ], [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.min' => 'Họ và tên cần có ít nhất 2 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email chưa đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.',
            'password.required' => 'Vui lòng tạo mật khẩu.',
            'password.min' => 'Mật khẩu cần có ít nhất 10 ký tự.',
            'password.mixed' => 'Mật khẩu phải có ít nhất một chữ hoa và một chữ thường.',
            'password.numbers' => 'Mật khẩu phải có ít nhất một chữ số.',
            'password.confirmed' => 'Mật khẩu xác nhận chưa trùng khớp.',
        ]);

        $email = mb_strtolower(trim($request->email));
        $existing = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existing) {
            if ($existing->role === 'customer' && ! $existing->verified && $existing->otp_code) {
                session(['pending_verify_email' => $existing->email]);
                session()->flash('success', 'Tài khoản đang chờ xác thực. Vui lòng nhập OTP hoặc yêu cầu gửi lại mã.');

                return $request->expectsJson()
                    ? response()->json(['success' => true, 'redirect' => route('verify')])
                    : redirect()->route('verify');
            }

            throw ValidationException::withMessages(['email' => 'Email này đã được sử dụng.']);
        }

        [$otp, $expiresAt] = $this->generateOtp();

        $user = User::create([
            'username'       => $email,
            'fullname'       => $request->name,
            'email'          => $email,
            'phone'          => $request->phone,
            'password'       => Hash::make($request->password),
            'role'           => 'customer',
            'verified'       => false,
            'otp_code'       => Hash::make($otp),
            'otp_expires_at' => $expiresAt,
        ]);
        session(['pending_verify_email' => $user->email]);

        $this->scheduleOtpEmail($user, $otp, 'Xác thực tài khoản Royal Hotel');
        session()->flash('success', 'Đăng ký thành công! Mã OTP sẽ được gửi đến ' . $user->email . '.');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('verify'),
            ]);
        }

        return redirect()->route('verify');
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
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Phiên xác thực không hợp lệ. Vui lòng đăng ký lại.',
                    'redirect' => route('register')
                ], 422);
            }
            return redirect()->route('register')
                ->with('error', 'Phiên xác thực không hợp lệ. Vui lòng đăng ký lại.');
        }

        if (!$user->verified && !$user->otp_code) {
            session()->forget('pending_verify_email');
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
                    'redirect' => route('login')
                ], 422);
            }
            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        if (!$user->otp_code || !Hash::check($request->otp, $user->otp_code)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Mã OTP không đúng. Vui lòng thử lại.',
                    'errors' => ['otp' => ['Mã OTP không đúng. Vui lòng thử lại.']]
                ], 422);
            }
            return back()->with('error', 'Mã OTP không đúng. Vui lòng thử lại.');
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.',
                    'errors' => ['otp' => ['Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.']]
                ], 422);
            }
            return back()->with('error', 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.');
        }

        $user->update([
            'verified'       => true,
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        session()->forget('pending_verify_email');
        $this->loginUser($user);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => redirect()->intended(route('home'))->getTargetUrl()
            ]);
        }

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
        $request->merge(['email' => mb_strtolower(trim((string) $request->email))]);
        $request->validate([
            'email' => 'required|email',
        ]);

        session()->forget(['reset_email', 'reset_verified', 'reset_verified_at']);
        session(['reset_email' => $request->email]);
        $user = User::where('role', 'customer')->where('email', $request->email)->first();

        // Luôn trả lời thành công để tránh user enumeration
        if ($user) {
            $isLocked = ($user->role !== 'customer' && !$user->verified) || ($user->role === 'customer' && !$user->verified && !$user->otp_code);
            if (!$isLocked) {
                [$otp, $expiresAt] = $this->generateOtp();

                $user->update([
                    'otp_code'       => Hash::make($otp),
                    'otp_expires_at' => $expiresAt,
                ]);

                $this->scheduleOtpEmail($user, $otp, 'Đặt lại mật khẩu Royal Hotel');
            }
        } else {
            Hash::make(bin2hex(random_bytes(16)));
        }

        return redirect()->route('password.verify-otp')
            ->with('success', 'Nếu email tồn tại, mã OTP đã được gửi đến hộp thư của bạn.');
    }

    // ──────────────────────────────────────────────────────────
    // Xác thực OTP đặt lại mật khẩu
    // ──────────────────────────────────────────────────────────

    public function showVerifyOtp()
    {
        $email = session('reset_email');
        if (! $email) {
            return redirect()->route('password.forgot');
        }

        return view('auth.verify_reset_otp', compact('email'));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $email = session('reset_email');
        $user  = User::where('role', 'customer')->where('email', $email)->first();

        if (!$user || !$user->otp_code || !Hash::check($request->otp, $user->otp_code)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Mã OTP không đúng.',
                    'errors' => ['otp' => ['Mã OTP không đúng.']]
                ], 422);
            }
            return back()->with('error', 'Mã OTP không đúng.');
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.',
                    'errors' => ['otp' => ['Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.']]
                ], 422);
            }
            return back()->with('error', 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.');
        }

        // Xác nhận OTP hợp lệ → cho phép đặt lại mật khẩu
        session(['reset_verified' => $user->id, 'reset_verified_at' => now()->timestamp]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('password.reset')
            ]);
        }

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
        if ($request->filled('password') && $request->filled('password_confirmation')
            && $request->password !== $request->password_confirmation) {
            throw ValidationException::withMessages([
                'password_confirmation' => 'Mật khẩu xác nhận không khớp.',
            ]);
        }

        $request->validate([
            'password'              => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'password_confirmation' => 'required|string',
        ], [
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $email = session('reset_email');
        $user  = User::where('role', 'customer')->where('email', $email)->first();

        if (!$user || (int) session('reset_verified') !== (int) $user->id || now()->timestamp - (int) session('reset_verified_at', 0) > 600) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Phiên đặt lại mật khẩu không hợp lệ.',
                    'errors' => ['password' => ['Phiên đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu OTP mới.']],
                    'redirect' => route('password.forgot'),
                ], 422);
            }
            return redirect()->route('password.forgot')
                ->with('error', 'Phiên đặt lại mật khẩu không hợp lệ.');
        }

        $user->update([
            'password'       => Hash::make($request->password),
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);
        app(\App\Services\SessionRevocationService::class)->revoke($user);

        session()->forget(['reset_email', 'reset_verified', 'reset_verified_at']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login')->with('success', 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.');
    }

    // ──────────────────────────────────────────────────────────
    // Đăng xuất
    // ──────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    /**
     * Ghi thông tin user vào session (dùng chung cho middleware auth.custom / verified.custom).
     */
    private function loginUser(User $user, bool $remember = false): void
    {
        session()->regenerate();
        auth()->login($user, $remember);
        $userData = [
            'id'       => $user->id,
            'fullname' => $user->fullname,
            'email'    => $user->email,
            'phone'    => $user->phone,
            'role'     => $user->role,
            'verified' => $user->verified,
        ];

        if ($user->role === 'customer') {
            session([
                'customer_user_id' => $user->id,
                'customer_user'    => $userData,
            ]);
        } else {
            session([
                'staff_user_id' => $user->id,
                'staff_user'    => $userData,
            ]);
        }

        session([
            'auth_user_id' => $user->id,
            'user_id'      => $user->id,
            'user'         => $userData,
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
    private function sendOtpEmail(string $toEmail, string $otp, string $subject): bool
    {
        try {
            Mail::raw(
                "Mã OTP của bạn là: {$otp}\n\nMã có hiệu lực trong 10 phút.\n\nNếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.\n\nTrân trọng,\nRoyal Hotel",
                function ($message) use ($toEmail, $subject) {
                    $message->to($toEmail)->subject($subject);
                }
            );
            return true;
        } catch (\Throwable $e) {
            logger()->error('OTP delivery failed', ['exception' => get_class($e)]);
            return false;
        }
    }

    private function scheduleOtpEmail(User $user, string $otp, string $subject): void
    {
        $sent = false;
        app()->terminating(function () use ($user, $otp, $subject, &$sent): void {
            if ($sent) {
                return;
            }
            $sent = true;

            if (! $this->sendOtpEmail($user->email, $otp, $subject)) {
                Log::warning('OTP email delivery failed.', ['user_id' => $user->id, 'subject' => $subject]);
            }
        });
    }

    /**
     * Gửi lại mã OTP xác thực tài khoản đăng ký.
     */
    public function resendVerifyOtp(Request $request)
    {
        $email = session('pending_verify_email');
        if (!$email) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Phiên đã hết hạn. Vui lòng đăng ký lại.'], 422)
                : redirect()->route('register');
        }

        // Giới hạn 1 phút
        $lastSent = session('last_otp_sent', 0);
        if (time() - $lastSent < 60) {
            $seconds = 60 - (time() - $lastSent);
            if ($request->expectsJson()) {
                return response()->json(['message' => "Vui lòng đợi {$seconds} giây nữa mới được gửi lại mã."], 429);
            }
            return redirect()->route('verify')->with('error', "Vui lòng đợi {$seconds} giây nữa mới được gửi lại mã.");
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Tài khoản không tồn tại.'], 422)
                : redirect()->route('register');
        }

        if (!$user->verified && !$user->otp_code) {
            session()->forget('pending_verify_email');
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'], 422);
            }
            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        [$otp, $expiresAt] = $this->generateOtp();
        $user->update([
            'otp_code'       => Hash::make($otp),
            'otp_expires_at' => $expiresAt,
        ]);

        $this->scheduleOtpEmail($user, $otp, 'Xác thực tài khoản Royal Hotel');
        session(['last_otp_sent' => time()]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Mã OTP mới sẽ được gửi đến email của bạn.']);
        }

        return redirect()->route('verify')->with('success', 'Mã OTP mới sẽ được gửi đến email của bạn.');
    }

    /**
     * Gửi lại mã OTP đặt lại mật khẩu.
     */
    public function resendResetOtp(Request $request)
    {
        $email = session('reset_email');
        if (!$email) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Phiên đã hết hạn. Vui lòng nhập lại email.'], 422)
                : redirect()->route('password.forgot');
        }

        // Giới hạn 1 phút
        $lastSent = session('last_otp_sent', 0);
        if (time() - $lastSent < 60) {
            $seconds = 60 - (time() - $lastSent);
            if ($request->expectsJson()) {
                return response()->json(['message' => "Vui lòng đợi {$seconds} giây nữa mới được gửi lại mã."], 429);
            }
            return redirect()->route('password.verify-otp')->with('error', "Vui lòng đợi {$seconds} giây nữa mới được gửi lại mã.");
        }

        session()->forget(['reset_verified', 'reset_verified_at']);
        $user = User::where('role', 'customer')->where('email', $email)->first();
        if ($user) {
            $isLocked = ($user->role !== 'customer' && !$user->verified) || ($user->role === 'customer' && !$user->verified && !$user->otp_code);
            if (!$isLocked) {
                [$otp, $expiresAt] = $this->generateOtp();
                $user->update([
                    'otp_code'       => Hash::make($otp),
                    'otp_expires_at' => $expiresAt,
                ]);

                $this->scheduleOtpEmail($user, $otp, 'Đặt lại mật khẩu Royal Hotel');
                session(['last_otp_sent' => time()]);

                if ($request->expectsJson()) {
                    return response()->json(['success' => true, 'message' => 'Nếu email tồn tại, mã OTP mới đã được gửi.']);
                }

                return redirect()->route('password.verify-otp')->with('success', 'Nếu email tồn tại, mã OTP mới đã được gửi.');
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Nếu email tồn tại, mã OTP mới đã được gửi.']);
        }

        return redirect()->route('password.verify-otp')->with('success', 'Nếu email tồn tại, mã OTP mới đã được gửi.');
    }
}
