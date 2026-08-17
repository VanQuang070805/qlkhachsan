<?php

namespace App\Http\Controllers\Admin;
use App\Mail\AccountLockedMail;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Mail\ReceptionistAccountMail;
use App\Mail\AccountUpdatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminUser::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('verified')) {
            $query->where('verified', $request->verified);
        }

        $users = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'fullname' => 'required|string|max:150',
            'email'    => 'required|email|max:150|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'role'     => 'required|in:receptionist,admin',
        ]);

        $plainPassword = $request->password;

        $user = AdminUser::create([
            'username' => $request->username,
            'password' => Hash::make($plainPassword),
            'fullname' => $request->fullname,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'role'     => $request->role,
            'verified' => 1,
        ]);

        // Gửi mail thông tin cho lễ tân
        if ($user->role === 'receptionist') {
            Mail::to($user->email)->send(
                new ReceptionistAccountMail(
                    $user->fullname,
                    $user->username,
                    $plainPassword
                )
            );
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'Tạo tài khoản thành công!');
    }

    public function edit(AdminUser $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, AdminUser $user)
    {
        $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'fullname' => 'required|string|max:150',
            'email'    => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone'    => 'nullable|string|max:30',
            'role'     => 'required|in:receptionist,admin,customer',
        ]);

        $data = [
            'username' => $request->username,
            'fullname' => $request->fullname,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'role'     => $request->role,
        ];

        // Lưu password gốc nếu có đổi
        $plainPassword = null;
        if (!empty($request->password)) {
            $plainPassword    = $request->password;
            $data['password'] = Hash::make($plainPassword);
        }

        $user->update($data);

        // Gửi mail toàn bộ thông tin sau khi sửa
        Mail::to($user->email)->send(
            new AccountUpdatedMail(
                $user->fullname,
                $user->username,
                $user->email,
                $user->phone,
                $user->role,
                $plainPassword  // null nếu không đổi mật khẩu
            )
        );

        return redirect()->route('admin.users.index')
                         ->with('success', 'Cập nhật tài khoản thành công!');
    }

    public function destroy(AdminUser $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Không thể xóa tài khoản đang đăng nhập!');
        }

        // Kiểm tra xem khách hàng có lịch đặt phòng chưa hoàn thành không (pending, confirmed, checked_in)
        $hasActiveBookings = \App\Models\Booking::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->exists();

        if ($hasActiveBookings) {
            return back()->with('error', 'Không thể xóa tài khoản này vì khách hàng vẫn còn lịch đặt phòng chưa hoàn thành (chờ xác nhận, đã xác nhận, hoặc đang ở)!');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
                         ->with('success', 'Xóa tài khoản thành công!');
    }

    public function toggleVerified(AdminUser $user)
{
    if ($user->id === auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => 'Không thể khóa tài khoản đang đăng nhập!'
        ]);
    }

    // Toggle trạng thái
    $user->update(['verified' => !$user->verified]);

    // Gửi mail thông báo
    Mail::to($user->email)->send(
        new AccountLockedMail(
            $user->fullname,
            !$user->verified  // true = vừa bị khóa, false = vừa được mở
        )
    );

    return response()->json([
        'success'  => true,
        'verified' => $user->verified,
        'message'  => $user->verified ? 'Đã mở khóa tài khoản!' : 'Đã khóa tài khoản!',
    ]);
}
}