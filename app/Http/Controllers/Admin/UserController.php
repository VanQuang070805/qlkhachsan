<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountLockedMail;
use App\Mail\AccountUpdatedMail;
use App\Mail\ReceptionistAccountMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleId = Role::where('slug', $request->role)->value('id');
            $query->where(function ($roleQuery) use ($request, $roleId) {
                $roleQuery->where('role_id', $roleId ?? 0);
                if (in_array($request->role, ['admin', 'receptionist', 'customer'], true)) {
                    $roleQuery->orWhere(fn ($legacy) => $legacy->whereNull('role_id')->where('role', $request->role));
                }
            });
        }

        if ($request->filled('verified')) {
            $query->where('verified', $request->verified);
        }

        $users = $query->with('assignedRole')
            ->select(['id', 'username', 'fullname', 'email', 'phone', 'role', 'role_id', 'verified', 'created_at'])
            ->orderBy('id', 'desc')->paginate(10)->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        return view('admin.users.create', ['roles' => Role::where('slug', '<>', 'customer')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $roles = Role::where('slug', '<>', 'customer')->get()->keyBy('slug');
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'password' => ['required', 'confirmed', 'string', 'min:6'],
            'fullname' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => ['nullable', 'regex:/^[0-9]{7,15}$/'],
            'role' => ['required', 'string', Rule::in($roles->keys()->all())],
        ], [
            'phone.regex' => 'Số điện thoại chỉ được chứa 7–15 chữ số.',
        ]);

        $assignedRole = $roles->get($validated['role']);
        $accessRole = $assignedRole->slug === 'admin' ? 'admin' : 'receptionist';
        $user = DB::transaction(function () use ($validated, $assignedRole, $accessRole): User {
            return User::create([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'fullname' => $validated['fullname'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'role' => $accessRole,
                'role_id' => $assignedRole->id,
                'verified' => 1,
            ]);
        });

        if ($accessRole === 'receptionist') {
            try {
                Mail::to($user->email)->send(new ReceptionistAccountMail($user->fullname, $user->username, $assignedRole->name));
            } catch (\Throwable $e) {
                Log::warning('Internal account notification failed', ['user_id' => $user->id, 'exception' => get_class($e)]);
            }
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Tạo tài khoản thành công!');
    }

    public function edit(User $user)
    {
        if ($user->isCustomer()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Quản trị viên không có quyền chỉnh sửa thông tin hoặc mật khẩu của khách hàng.');
        }

        $roles = Role::where('slug', '<>', 'customer')->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->isCustomer()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quản trị viên không có quyền chỉnh sửa thông tin hoặc mật khẩu của khách hàng.',
                ], 403);
            }

            return redirect()->route('admin.users.index')
                ->with('error', 'Quản trị viên không có quyền chỉnh sửa thông tin hoặc mật khẩu của khách hàng.');
        }

        $request->merge(['email' => mb_strtolower(trim((string) $request->email))]);
        $allowedRoles = Role::where('slug', '<>', 'customer')->pluck('slug')->all();

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'string', 'min:6'],
            'fullname' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^[0-9]{7,15}$/'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
        ], [
            'phone.regex' => 'Số điện thoại chỉ được chứa 7–15 chữ số.',
        ]);

        $assignedRole = Role::where('slug', $validated['role'])->firstOrFail();
        $accessRole = $assignedRole->slug === 'admin' ? 'admin' : 'receptionist';

        $data = [
            'username' => $request->username,
            'fullname' => $request->fullname,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $accessRole,
            'role_id' => $assignedRole->id,
        ];

        if ($user->isAdmin() && $user->verified && $assignedRole->slug !== 'admin'
            && User::where('role', 'admin')->where('verified', true)->count() <= 1) {
            return back()->withInput()->with('error', 'Không thể hạ quyền quản trị viên hoạt động cuối cùng.');
        }

        $credentialsChanged = ! empty($validated['password']) || (int) $user->role_id !== (int) $assignedRole->id;
        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        DB::transaction(function () use ($user, $data, $credentialsChanged): void {
            $user->update($data);
            if ($credentialsChanged) {
                DB::table('user_permissions')->where('user_id', $user->id)->delete();
            }
        });
        if ($credentialsChanged) {
            app(\App\Services\SessionRevocationService::class)->revoke($user);
        }

        try {
            Mail::to($user->email)->send(new AccountUpdatedMail(
                $user->fullname,
                $user->username,
                $user->email,
                $user->phone,
                $assignedRole->name
            ));
        } catch (\Throwable $e) {
            Log::warning('Account update notification failed', ['user_id' => $user->id, 'exception' => get_class($e)]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Cập nhật tài khoản thành công!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Không thể xóa tài khoản đang đăng nhập!');
        }

        if ($user->role === 'admin' && $user->verified && User::where('role', 'admin')->where('verified', true)->count() <= 1) {
            return back()->with('error', 'Không thể xóa quản trị viên hoạt động cuối cùng.');
        }

        $hasActiveBookings = \App\Models\Booking::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->exists();

        if ($hasActiveBookings) {
            return back()->with('error', 'Không thể xóa tài khoản này vì khách hàng vẫn còn lịch đặt phòng chưa hoàn thành (chờ xác nhận, đã xác nhận, hoặc đang ở)!');
        }

        app(\App\Services\SessionRevocationService::class)->revoke($user);
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Đã ngừng hoạt động tài khoản và giữ lại lịch sử liên quan.');
    }

    public function toggleVerified(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khóa tài khoản đang đăng nhập!',
            ]);
        }

        if ($user->role === 'admin' && $user->verified && User::where('role', 'admin')->where('verified', true)->count() <= 1) {
            return response()->json(['success' => false, 'message' => 'Không thể khóa quản trị viên hoạt động cuối cùng.'], 409);
        }

        $user->update(['verified' => ! $user->verified]);
        if (! $user->verified) {
            app(\App\Services\SessionRevocationService::class)->revoke($user);
        }

        try {
            Mail::to($user->email)->send(new AccountLockedMail($user->fullname, ! $user->verified));
        } catch (\Throwable $e) {
            Log::warning('Account status notification failed', ['user_id' => $user->id, 'exception' => get_class($e)]);
        }

        return response()->json([
            'success' => true,
            'verified' => $user->verified,
            'message' => $user->verified ? 'Đã mở khóa tài khoản!' : 'Đã khóa tài khoản!',
        ]);
    }
}
