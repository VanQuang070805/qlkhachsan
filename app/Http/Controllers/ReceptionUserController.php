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
        $userId = session('staff_user_id') ?: session('admin_user_id') ?: session('user_id');
        if (! $userId) {
            return null;
        }

        $user = User::find($userId);

        return ($user && in_array($user->role, ['receptionist', 'admin'], true)) ? $user : null;
    }

    public function profile()
    {
        $user = $this->getCurrentUser();
        if (! $user) {
            return redirect()->route('staff.login');
        }

        return view('receptionist.profile', compact('user'));
    }

    public function updateInfo(Request $request)
    {
        $user = $this->getCurrentUser();
        if (! $user) {
            return redirect()->route('staff.login');
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

        $updatedSession = [
            'id'       => $user->id,
            'fullname' => $request->fullname,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'role'     => $user->role,
            'verified' => $user->verified,
        ];

        session([
            'staff_user' => $updatedSession,
            'user'       => $updatedSession,
        ]);
        if ($user->isAdmin()) {
            session(['admin_user' => $updatedSession]);
        }

        return redirect()->route('staff.profile')
            ->with('success_info', 'Cập nhật thông tin thành công!');
    }

    public function updatePassword(Request $request)
    {
        $user = $this->getCurrentUser();
        if (! $user) {
            return redirect()->route('staff.login');
        }

        $request->validate([
            'current_password' => 'required',
            'new_password'     => ['required', 'confirmed', 'string', 'min:6'],
        ]);

        $valid = Hash::check($request->current_password, $user->password);

        if (! $valid) {
            return back()
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng!']);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);
        app(\App\Services\SessionRevocationService::class)->revoke($user, $request->session()->getId());

        return redirect()->route('staff.profile')
            ->with('success_password', 'Đổi mật khẩu thành công!');
    }
}
