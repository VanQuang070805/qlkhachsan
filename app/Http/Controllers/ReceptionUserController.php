<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ReceptionUserController extends Controller
{
    private function getCurrentUser(): ?User
    {
        $userId = session('user_id');
        if (!$userId) return null;
        return User::find($userId);
    }

    public function profile()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect('/auth/login');
        }

        return view('receptionist.profile', compact('user'));
    }

    public function updateInfo(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect('/auth/login');
        }

        $request->merge(['email' => mb_strtolower(trim((string) $request->email))]);
        $request->validate([
            'fullname' => 'required|string|max:150',
            'email'    => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone'    => ['nullable', 'regex:/^[0-9]{7,15}$/'],
        ], [
            'phone.regex' => 'Số điện thoại chỉ được chứa 7–15 chữ số.',
        ]);

        $user->update([
            'fullname' => $request->fullname,
            'email'    => $request->email,
            'phone'    => $request->phone,
        ]);

        $userSession = session('user', []);
        $userSession['fullname'] = $request->fullname;
        $userSession['email']    = $request->email;
        $userSession['phone']    = $request->phone;
        session(['user' => $userSession]);

        return redirect()->route('receptionist.profile')
                         ->with('success_info', 'Cập nhật thông tin thành công!');
    }

    public function updatePassword(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect('/auth/login');
        }

        $request->validate([
            'current_password' => 'required',
            'new_password'     => ['required', 'confirmed', 'string', 'min:6'],
        ]);

        $valid = Hash::check($request->current_password, $user->password);

        if (!$valid) {
            return back()
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng!'])
                ;
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);
        app(\App\Services\SessionRevocationService::class)->revoke($user, $request->session()->getId());

        return redirect()->route('receptionist.profile')
                         ->with('success_password', 'Đổi mật khẩu thành công!');
    }
}
