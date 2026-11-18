<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): SymfonyRedirectResponse|RedirectResponse
    {
        $host = strtolower($request->getHost());
        if (! app()->runningUnitTests() && in_array($host, ['localhost', '127.0.0.1'], true) && (int) $request->getPort() !== 10800) {
            return redirect()->to($request->getScheme().'://'.$host.':10800'.$request->getRequestUri());
        }

        $loggedInCustomerId = (int) $request->session()->get('customer_user_id', 0);
        $isLinking = $loggedInCustomerId > 0 && $request->query('intent') === 'link';
        $originRoute = $request->query('from') === 'register' ? 'register' : 'login';

        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            $fallbackRoute = $isLinking ? 'account.show' : $originRoute;

            return redirect()->route($fallbackRoute)->with('error', 'Đăng nhập Google chưa được cấu hình trên máy chủ (thiếu GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET).');
        }

        if ($loggedInCustomerId > 0) {
            $currentCustomer = User::find($loggedInCustomerId);
            if ($currentCustomer && $currentCustomer->isCustomer()) {
                if ($currentCustomer->google_id && ! $isLinking) {
                    return redirect()->route('home');
                }
                $request->session()->put('google_oauth_link_user_id', $currentCustomer->id);
            }
        } else {
            $request->session()->forget('google_oauth_link_user_id');
            $request->session()->put('google_oauth_origin', $originRoute);
        }

        config(['services.google.redirect' => $this->resolveCallbackUrl($request)]);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        config(['services.google.redirect' => $this->resolveCallbackUrl($request)]);

        $linkUserId = (int) $request->session()->pull('google_oauth_link_user_id', 0);
        $originRoute = $request->session()->pull('google_oauth_origin', 'login') === 'register' ? 'register' : 'login';
        $loggedInCustomerId = (int) $request->session()->get('customer_user_id', 0);
        $isAuthenticatedLink = $linkUserId > 0 && $linkUserId === $loggedInCustomerId;
        $errorRedirectRoute = $isAuthenticatedLink ? 'account.show' : $originRoute;

        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route($errorRedirectRoute)->with('error', 'Không thể xác thực Google. Vui lòng thử lại.');
        }

        if (! $google->getEmail()) {
            return redirect()->route($errorRedirectRoute)->with('error', 'Tài khoản Google chưa cung cấp email.');
        }

        $raw = $google->getRaw();
        if (($raw['email_verified'] ?? $raw['verified_email'] ?? false) !== true) {
            return redirect()->route($errorRedirectRoute)->with('error', 'Google chưa xác nhận email của tài khoản này.');
        }

        $googleId = (string) $google->getId();
        $email = mb_strtolower($google->getEmail());
        $userByGoogle = User::withTrashed()->where('google_id', $googleId)->first();

        if ($isAuthenticatedLink) {
            $currentCustomer = User::withTrashed()->find($linkUserId);
            if (! $currentCustomer || $currentCustomer->trashed() || ! $currentCustomer->isCustomer() || ! $currentCustomer->verified) {
                return redirect()->route('login')->with('error', 'Phiên đăng nhập không hợp lệ để liên kết Google.');
            }

            if ($userByGoogle && ! $userByGoogle->is($currentCustomer)) {
                return redirect()->route('account.show')->with('error', 'Tài khoản Google này đang được liên kết với một tài khoản khác.');
            }

            if (mb_strtolower($currentCustomer->email) !== $email) {
                return redirect()->route('account.show')->with('error', 'Vui lòng chọn tài khoản Google trùng với email hồ sơ ('.$currentCustomer->email.').');
            }

            $currentCustomer->forceFill([
                'google_id' => $googleId,
                'avatar_url' => $google->getAvatar() ?: $currentCustomer->avatar_url,
            ])->save();

            return redirect()->route('account.show')->with('success', 'Đã liên kết tài khoản Google thành công.');
        }

        $userByEmail = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($userByGoogle && $userByEmail && ! $userByGoogle->is($userByEmail)) {
            return redirect()->route('login')->with('error', 'Tài khoản Google này đang liên kết với một tài khoản khác.');
        }

        $user = $userByGoogle ?: $userByEmail;
        if ($user && $user->trashed()) {
            return redirect()->route('login')->with('error', 'Tài khoản này đã ngừng hoạt động. Vui lòng liên hệ quản trị viên.');
        }

        if ($user && ! $user->isCustomer()) {
            return redirect()->route('login')->with('error', 'Email này thuộc tài khoản nội bộ và không thể đăng nhập tại đây.');
        }

        if ($user && ! $user->verified && ! $user->otp_code) {
            return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        if ($user && $user->google_id && $user->google_id !== $googleId) {
            return redirect()->route('login')->with('error', 'Email này đã được liên kết với một tài khoản Google khác.');
        }

        if ($user && $user->verified && ! $user->google_id) {
            return redirect()->route('login')->with('error', 'Email này đã có tài khoản. Hãy đăng nhập bằng mật khẩu trước, sau đó liên kết Google trong mục Thông tin cá nhân.');
        }

        // Người dùng mới hoàn toàn hoặc tài khoản đang chờ xác thực OTP lần đầu -> gửi mã OTP về Gmail và chuyển tới /verify
        if (! $user) {
            $base = Str::slug(Str::before($email, '@'), '_') ?: 'guest';
            $username = $base;
            for ($suffix = 1; User::withTrashed()->where('username', $username)->exists(); $suffix++) {
                $username = $base.'_'.$suffix;
            }
            try {
                $user = User::create([
                    'username' => $username,
                    'fullname' => $google->getName() ?: $base,
                    'email' => $email,
                    'password' => Hash::make(Str::random(48)),
                    'role' => 'customer',
                    'verified' => false,
                    'google_id' => $googleId,
                    'avatar_url' => $google->getAvatar(),
                ]);
            } catch (\Illuminate\Database\QueryException) {
                return redirect()->route($errorRedirectRoute)->with('error', 'Không thể tạo tài khoản lúc này. Vui lòng thử lại.');
            }

            app(AuthController::class)->issueAccountVerificationOtp($user);

            return redirect()->route('verify')
                ->with('success', 'Mã OTP xác thực đã được gửi đến '.$user->email.'. Vui lòng nhập mã để kích hoạt tài khoản.');
        }

        if (! $user->verified) {
            $user->forceFill([
                'google_id' => $googleId,
                'avatar_url' => $google->getAvatar() ?: $user->avatar_url,
            ])->save();

            app(AuthController::class)->issueAccountVerificationOtp($user);

            return redirect()->route('verify')
                ->with('success', 'Tài khoản đang chờ kích hoạt. Mã OTP mới đã được gửi đến '.$user->email.'.');
        }

        $user->forceFill([
            'google_id' => $googleId,
            'avatar_url' => $google->getAvatar() ?: $user->avatar_url,
        ])->save();

        $preservedStaffId = session('staff_user_id');
        $preservedStaffData = session('staff_user');
        $preservedAdminId = session('admin_user_id');
        $preservedAdminData = session('admin_user');

        session()->regenerate();
        auth()->login($user, true);
        Auth::guard('customer')->login($user, true);
        $userData = [
            'id' => $user->id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'verified' => $user->verified,
        ];
        session([
            'customer_user_id' => $user->id,
            'customer_user' => $userData,
            'auth_user_id' => $user->id,
            'user_id' => $user->id,
            'user' => $userData,
        ]);
        if ($preservedStaffId) {
            session(['staff_user_id' => $preservedStaffId, 'staff_user' => $preservedStaffData]);
        }
        if ($preservedAdminId) {
            session(['admin_user_id' => $preservedAdminId, 'admin_user' => $preservedAdminData]);
        }

        return redirect()->intended(route('home'));
    }

    private function resolveCallbackUrl(Request $request): string
    {
        $configured = trim((string) config('services.google.redirect', ''));
        $host = strtolower($request->getHost());

        if (in_array($host, ['localhost', '127.0.0.1'], true) || $configured === '') {
            return $request->getSchemeAndHttpHost().'/auth/google/callback';
        }

        return $configured;
    }
}
