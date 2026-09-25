<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        if (!config('services.google.client_id') || !config('services.google.client_secret')) {
            return redirect()->route('login')->with('error', 'Đăng nhập Google chưa được cấu hình trên máy chủ.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->with('error', 'Không thể xác thực Google. Vui lòng thử lại.');
        }

        if (!$google->getEmail()) {
            return redirect()->route('login')->with('error', 'Tài khoản Google chưa cung cấp email.');
        }

        $googleId = (string) $google->getId();
        $email = mb_strtolower($google->getEmail());
        $userByGoogle = User::where('google_id', $googleId)->first();
        $userByEmail = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($userByGoogle && $userByEmail && !$userByGoogle->is($userByEmail)) {
            return redirect()->route('login')->with('error', 'Tài khoản Google này đang liên kết với một tài khoản khác.');
        }

        $user = $userByGoogle ?: $userByEmail;
        if ($user && !$user->isCustomer()) {
            return redirect()->route('login')->with('error', 'Email này thuộc tài khoản nội bộ và không thể đăng nhập tại đây.');
        }

        if ($user && $user->google_id && $user->google_id !== $googleId) {
            return redirect()->route('login')->with('error', 'Email này đã được liên kết với một tài khoản Google khác.');
        }

        if (!$user) {
            $base = Str::slug(Str::before($email, '@'), '_') ?: 'guest';
            $username = $base;
            for ($suffix = 1; User::where('username', $username)->exists(); $suffix++) $username = $base.'_'.$suffix;
            $user = User::create([
                'username' => $username,
                'fullname' => $google->getName() ?: $base,
                'email' => $email,
                'password' => Hash::make(Str::random(48)),
                'role' => 'customer',
                'verified' => true,
                'google_id' => $googleId,
                'avatar_url' => $google->getAvatar(),
            ]);
        } else {
            $user->forceFill([
                'google_id' => $googleId,
                'avatar_url' => $google->getAvatar() ?: $user->avatar_url,
                'verified' => true,
                'otp_code' => null,
                'otp_expires_at' => null,
            ])->save();
        }

        session()->regenerate();
        auth()->login($user, true);
        $userData = ['id'=>$user->id, 'fullname'=>$user->fullname, 'email'=>$user->email, 'phone'=>$user->phone, 'role'=>$user->role, 'verified'=>$user->verified];
        session(['customer_user_id'=>$user->id, 'customer_user'=>$userData, 'auth_user_id'=>$user->id, 'user_id'=>$user->id, 'user'=>$userData]);

        return redirect()->intended(route('home'));
    }
}
