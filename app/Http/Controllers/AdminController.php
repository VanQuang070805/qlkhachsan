<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PriceSetting;
use App\Models\Role;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // Dashboard
    // ──────────────────────────────────────────────────────────

    public function dashboard()
    {
        return redirect()->route('admin.reports');
    }

    // ──────────────────────────────────────────────────────────
    // Báo cáo thống kê
    // ──────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'room_type_id' => 'nullable|integer|exists:room_types,id',
            'status' => 'nullable|in:pending,confirmed,checked_in,completed,cancelled',
        ]);

        $filters = [
            'start_date'   => $request->input('start_date', ''),
            'end_date'     => $request->input('end_date', ''),
            'room_type_id' => $request->input('room_type_id', ''),
            'status'       => $request->input('status', ''),
        ];

        $bookingModel = new Booking();
        $userModel    = new User();

        $stats                     = $bookingModel->getReportStats($filters);
        $stats['total_customers']  = $userModel->countCustomers();
        $roomCount                 = max(1, (int) DB::table('rooms')->count());
        $firstStay                 = $filters['start_date'] ?: DB::table('bookings')->min('check_in') ?: now()->toDateString();
        $lastStay                  = $filters['end_date'] ?: DB::table('bookings')->max('check_out') ?: now()->toDateString();
        $periodDays                = max(1, \Carbon\Carbon::parse($firstStay)->diffInDays(\Carbon\Carbon::parse($lastStay)) + 1);
        $soldNights                = max(0, (int) ($stats['total_nights'] ?? 0));
        $stats['adr']              = $soldNights > 0 ? $stats['total_revenue'] / $soldNights : 0;
        $stats['revpar']           = $stats['total_revenue'] / ($roomCount * $periodDays);
        $stats['occupancy_rate']   = min(100, ($soldNights / ($roomCount * $periodDays)) * 100);
        $trendRows                 = $bookingModel->getReportChartData($filters);
        $trendEnd                  = $filters['end_date']
            ? \Carbon\Carbon::parse($filters['end_date'])->startOfDay()
            : (count($trendRows) ? \Carbon\Carbon::parse(end($trendRows)->date)->startOfDay() : now()->startOfDay());
        $trendStart                 = $filters['start_date']
            ? \Carbon\Carbon::parse($filters['start_date'])->startOfDay()
            : (count($trendRows) ? \Carbon\Carbon::parse($trendRows[0]->date)->startOfDay() : $trendEnd->copy()->subDays(29));
        if ($trendStart->greaterThan($trendEnd)) {
            [$trendStart, $trendEnd] = [$trendEnd->copy(), $trendStart->copy()];
        }
        if ($trendStart->diffInDays($trendEnd) > 364) {
            $trendStart = $trendEnd->copy()->subDays(364);
        }
        $trendsByDate = collect($trendRows)->keyBy(fn ($row) => (string) $row->date);
        $chartData = [];
        for ($date = $trendStart->copy(); $date->lte($trendEnd); $date->addDay()) {
            $row = $trendsByDate->get($date->toDateString());
            $chartData[] = [
                'date' => $date->toDateString(),
                'revenue' => (float) ($row->revenue ?? 0),
                'bookings' => (int) ($row->bookings ?? 0),
            ];
        }
        $typeChartData             = $bookingModel->getReportRoomTypeData($filters);
        $statusChartData = [
            ['Chờ xác nhận', (int) ($stats['pending_count'] ?? 0)],
            ['Đã xác nhận', (int) ($stats['confirmed_count'] ?? 0)],
            ['Đang lưu trú', (int) ($stats['checked_in_count'] ?? 0)],
            ['Hoàn thành', (int) ($stats['completed_count'] ?? 0)],
            ['Đã hủy', (int) ($stats['cancelled_count'] ?? 0)],
        ];
        $statusTrendData = $bookingModel->getReportStatusTrendData($filters);
        $monthlyTrendData = collect($chartData)
            ->groupBy(fn (array $row) => substr($row['date'], 0, 7))
            ->map(fn ($rows, string $period) => [
                'period' => $period,
                'revenue' => (float) $rows->sum('revenue'),
                'bookings' => (int) $rows->sum('bookings'),
            ])->values()->all();
        $weekdayRows = collect($bookingModel->getReportWeekdayData($filters))->keyBy('weekday');
        $weekdayLabels = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 0 => 'Chủ nhật'];
        $weekdayChartData = collect($weekdayLabels)->map(fn (string $label, int $weekday) => [
            'weekday' => $weekday,
            'label' => $label,
            'bookings' => (int) ($weekdayRows->get($weekday)->bookings ?? 0),
        ])->values()->all();
        $reportViewerId = session('staff_user_id') ?: session('auth_user_id');
        $canViewReportBookings = User::query()->find($reportViewerId)?->isAdmin() ?? false;
        $bookings = $canViewReportBookings ? $bookingModel->getFilteredBookings($filters) : collect();
        $roomTypes                 = DB::select('SELECT id, type_name FROM room_types');
        $roomInventory             = DB::table('rooms')->selectRaw('room_type_id, COUNT(*) AS total')->groupBy('room_type_id')->pluck('total', 'room_type_id');

        return view('admin.reports', [
            'stats'     => $stats,
            'chartData' => $chartData,
            'typeChartData' => $typeChartData,
            'statusChartData' => $statusChartData,
            'statusTrendData' => $statusTrendData,
            'monthlyTrendData' => $monthlyTrendData,
            'weekdayChartData' => $weekdayChartData,
            'bookings'  => $bookings,
            'filters'   => $filters,
            'roomTypes' => $roomTypes,
            'roomInventory' => $roomInventory,
            'canViewReportBookings' => $canViewReportBookings,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // Cài đặt điều chỉnh giá
    // ──────────────────────────────────────────────────────────

    public function priceSettings()
    {
        $settings = PriceSetting::all();

        return view('admin.price_settings.index', compact('settings'));
    }

    public function updateRoomTypePrice(Request $request, RoomType $roomType)
    {
        $validated = $request->validate([
            'room_type_id' => ['required', 'integer', 'in:'.$roomType->id],
            'price' => ['required', 'numeric', 'regex:/^(?:\d+(?:\.\d{0,2})?|\.\d{1,2})$/', 'min:1', 'max:9999999999.99'],
        ], [
            'price.regex' => 'Giá phòng chỉ được nhập chữ số và tối đa 2 chữ số thập phân.',
            'price.required' => 'Vui lòng nhập giá phòng.',
            'price.numeric' => 'Giá phòng phải là một số hợp lệ.',
            'price.min' => 'Giá phòng phải lớn hơn 0.',
            'price.max' => 'Giá phòng vượt quá giới hạn lưu trữ.',
        ]);

        DB::transaction(function () use ($roomType, $validated): void {
            RoomType::query()->lockForUpdate()->findOrFail($roomType->id)->update([
                'price' => round((float) $validated['price'], 2),
            ]);
        });

        return back()->with('success', 'Đã cập nhật giá nền cho hạng phòng '.$roomType->type_name.'.');
    }

    public function priceSettingsCreate()
    {
        return view('admin.price_settings.create');
    }

    public function priceSettingsStore(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|gte:start_date',
            'adjustment_type'  => 'required|in:percent,fixed',
            'adjustment_value' => ['required', 'numeric', 'regex:/^(?:\d+(?:\.\d{0,2})?|\.\d{1,2})$/', 'min:0.01'],
        ], [
            'end_date.gte'            => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'adjustment_value.min'    => 'Giá trị điều chỉnh phải lớn hơn 0.',
            'adjustment_value.regex'  => 'Giá trị điều chỉnh chỉ được nhập chữ số và tối đa 2 chữ số thập phân.',
        ]);

        $lock = \Illuminate\Support\Facades\Cache::lock('price-settings-write', 10);
        abort_unless($lock->get(), 409, 'Một thay đổi giá khác đang được xử lý.');
        try {
            if ($request->has('status') && (new PriceSetting)->checkOverlap($request->start_date, $request->end_date)) {
                return back()->withInput()->with('error', 'Khoảng thời gian này đã bị trùng lặp với một cài đặt giá đang bật. Vui lòng chọn ngày khác.');
            }
            PriceSetting::create([
                'name' => $request->name, 'start_date' => $request->start_date, 'end_date' => $request->end_date,
                'adjustment_type' => $request->adjustment_type, 'adjustment_value' => $request->adjustment_value,
                'status' => $request->has('status') ? 1 : 0,
            ]);
        } finally {
            $lock->release();
        }

        return redirect()->route('admin.price-settings.index')
            ->with('success', 'Thêm dịp điều chỉnh giá thành công.');
    }

    public function priceSettingsEdit(int $id)
    {
        $setting = PriceSetting::findOrFail($id);
        return view('admin.price_settings.edit', compact('setting'));
    }

    public function priceSettingsUpdate(Request $request, int $id)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|gte:start_date',
            'adjustment_type'  => 'required|in:percent,fixed',
            'adjustment_value' => ['required', 'numeric', 'regex:/^(?:\d+(?:\.\d{0,2})?|\.\d{1,2})$/', 'min:0.01'],
        ], [
            'end_date.gte'         => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'adjustment_value.min' => 'Giá trị điều chỉnh phải lớn hơn 0.',
            'adjustment_value.regex' => 'Giá trị điều chỉnh chỉ được nhập chữ số và tối đa 2 chữ số thập phân.',
        ]);

        $lock = \Illuminate\Support\Facades\Cache::lock('price-settings-write', 10);
        abort_unless($lock->get(), 409, 'Một thay đổi giá khác đang được xử lý.');
        try {
            if ($request->has('status') && (new PriceSetting)->checkOverlap($request->start_date, $request->end_date, $id)) {
                return back()->withInput()->with('error', 'Khoảng thời gian này đã bị trùng lặp với một cài đặt giá đang bật. Vui lòng chọn ngày khác.');
            }
            PriceSetting::findOrFail($id)->update([
                'name' => $request->name, 'start_date' => $request->start_date, 'end_date' => $request->end_date,
                'adjustment_type' => $request->adjustment_type, 'adjustment_value' => $request->adjustment_value,
                'status' => $request->has('status') ? 1 : 0,
            ]);
        } finally {
            $lock->release();
        }

        return redirect()->route('admin.price-settings.index')
            ->with('success', 'Cập nhật thành công.');
    }

    public function priceSettingsDelete(int $id)
    {
        PriceSetting::findOrFail($id)->delete();

        return redirect()->route('admin.price-settings.index')
            ->with('success', 'Đã xóa cài đặt giá.');
    }

    /**
     * Giao diện phân quyền tài khoản (RBAC) - Chuẩn Cupertino Luxury Minimalist
     */
    public function roles(Request $request)
    {
        $accounts = User::query()
            ->where('role', '<>', 'customer')
            ->with('assignedRole')
            ->orderBy('role')
            ->orderBy('username')
            ->get(['id', 'username', 'fullname', 'role', 'role_id']);

        $roles = Role::query()->where('slug', '<>', 'customer')->orderBy('name')->get();
        $selected = $accounts->firstWhere('id', (int) $request->query('user_id'))
            ?? $accounts->first(fn (User $user) => ! $user->isAdmin())
            ?? $accounts->first();
        $selectedRole = $selected?->assignedRole;

        $permissionGroups = collect(User::INTERNAL_PERMISSION_CATALOG)->groupBy('group', true);
        $selectedRolePermissions = $selectedRole
            ? array_values(array_intersect($selectedRole->permissionKeys(), array_keys(User::INTERNAL_PERMISSION_CATALOG)))
            : ($selected && (! $selected->role_id || ! Schema::hasTable('role_permissions'))
                ? array_keys(User::INTERNAL_PERMISSION_CATALOG)
                : []);
        $selectedUserPermissionRows = $selected && Schema::hasTable('user_permissions')
            ? DB::table('user_permissions')->where('user_id', $selected->id)->pluck('permission_key')
            : collect();
        $hasUserPermissionOverride = $selectedUserPermissionRows->contains(User::PERMISSIONS_CONFIGURED_MARKER);
        $directPermissions = $selectedUserPermissionRows
            ->intersect(array_keys(User::INTERNAL_PERMISSION_CATALOG))
            ->values()
            ->all();
        $selectedPermissions = $hasUserPermissionOverride
            ? $directPermissions
            : array_values(array_unique(array_merge($selectedRolePermissions, $directPermissions)));

        return view('admin.roles.index', compact(
            'accounts', 'roles', 'selected', 'selectedRole', 'permissionGroups',
            'selectedRolePermissions', 'selectedPermissions', 'hasUserPermissionOverride',
        ));
    }

    public function rolePermissions(Request $request)
    {
        $roles = Role::query()
            ->where('slug', '<>', 'customer')
            ->withCount('users')
            ->orderBy('name')
            ->get();
        $permissionGroups = collect(User::INTERNAL_PERMISSION_CATALOG)->groupBy('group', true);
        $rolePermissions = $roles->mapWithKeys(fn (Role $role) => [
            $role->id => $role->slug === 'admin'
                ? array_keys(User::INTERNAL_PERMISSION_CATALOG)
                : array_values(array_intersect($role->permissionKeys(), array_keys(User::INTERNAL_PERMISSION_CATALOG))),
        ]);
        $backUserId = User::query()
            ->where('role', '<>', 'customer')
            ->whereKey($request->query('user_id'))
            ->value('id');
        $focusRoleId = $roles->contains('id', (int) $request->query('role_id'))
            ? (int) $request->query('role_id')
            : $roles->first()?->id;
        $selectedRole = $roles->firstWhere('id', $focusRoleId);

        return view('admin.roles.permissions', compact('roles', 'permissionGroups', 'rolePermissions', 'backUserId', 'focusRoleId', 'selectedRole'));
    }

    public function storeRoleProfile(Request $request)
    {
        $validated = $request->validateWithBag('createRoleProfile', [
            'name' => ['required', 'string', 'min:2', 'max:60', 'regex:/[\pL\pN]/u'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(User::INTERNAL_PERMISSION_CATALOG))],
        ]);

        $name = trim($validated['name']);
        $slug = Str::slug($name);
        if ($slug === '' || Role::query()->where('slug', $slug)->orWhere('name', $name)->exists()) {
            throw ValidationException::withMessages(['name' => 'Tên vai trò này đã tồn tại hoặc không hợp lệ.'])
                ->errorBag('createRoleProfile');
        }

        $role = DB::transaction(function () use ($name, $slug, $validated): Role {
            $role = Role::create(['slug' => $slug, 'name' => $name, 'is_system' => false]);
            $now = now();
            $keys = array_values(array_unique($validated['permissions'] ?? []));
            if ($keys !== []) {
                DB::table('role_permissions')->insert(array_map(fn (string $key) => [
                    'role_id' => $role->id,
                    'permission_key' => $key,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $keys));
            }

            return $role;
        });

        $routeParameters = ['role_id' => $role->id];
        if (! empty($validated['user_id'])) {
            $routeParameters['user_id'] = $validated['user_id'];
        }

        return redirect()->route('admin.roles.permissions.index', $routeParameters)->with('success', 'Đã tạo vai trò '.$role->name.'.');
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        abort_if(in_array($role->slug, ['admin', 'customer'], true), 404);

        $validated = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(User::INTERNAL_PERMISSION_CATALOG))],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        DB::transaction(function () use ($role, $validated): void {
            $lockedRole = Role::query()->lockForUpdate()->findOrFail($role->id);
            DB::table('role_permissions')->where('role_id', $lockedRole->id)->delete();
            $keys = array_values(array_unique($validated['permissions'] ?? []));
            $now = now();
            if ($keys !== []) {
                DB::table('role_permissions')->insert(array_map(fn (string $key) => [
                    'role_id' => $lockedRole->id,
                    'permission_key' => $key,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $keys));
            }

        });

        $routeParameters = ['role_id' => $role->id];
        if (! empty($validated['user_id']) && User::query()->where('role', '<>', 'customer')->whereKey($validated['user_id'])->exists()) {
            $routeParameters['user_id'] = $validated['user_id'];
        }

        return redirect()->route('admin.roles.permissions.index', $routeParameters)
            ->with('success', 'Đã lưu quyền cho vai trò '.$role->name.'.');
    }

    public function assignRoleProfile(Request $request, User $user)
    {
        abort_if($user->role === 'customer', 404);
        if ($user->isAdmin()) {
            return back()->with('error', 'Không thể thay đổi vai trò của tài khoản quản trị viên tại đây.');
        }

        $assignableRoleSlugs = Role::query()
            ->whereNotIn('slug', ['customer', 'admin'])
            ->pluck('slug')
            ->all();
        $validated = $request->validate([
            'profile' => ['required', 'string', Rule::in($assignableRoleSlugs)],
        ]);

        $role = Role::where('slug', $validated['profile'])->firstOrFail();
        DB::transaction(function () use ($user, $role): void {
            $user->update(['role_id' => $role->id, 'role' => 'receptionist']);
            DB::table('user_permissions')->where('user_id', $user->id)->delete();
        });

        return redirect()->route('admin.roles.index', ['user_id' => $user->id])
            ->with('success', 'Đã gán vai trò '.$role->name.' cho '.$user->username.'.');
    }

    public function updateUserPermissions(Request $request, User $user)
    {
        abort_if($user->role === 'customer', 404);
        if ($user->isAdmin()) {
            return back()->with('error', 'Tài khoản quản trị viên luôn có toàn quyền, không thể đặt quyền riêng tại đây.');
        }
        if (! Schema::hasTable('user_permissions')) {
            return back()->with('error', 'Chưa có bảng lưu quyền tài khoản trong cơ sở dữ liệu.');
        }

        $validated = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(User::INTERNAL_PERMISSION_CATALOG))],
        ]);
        $keys = array_values(array_unique($validated['permissions'] ?? []));

        DB::transaction(function () use ($user, $keys): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_if($lockedUser->role === 'customer' || $lockedUser->isAdmin(), 404);

            DB::table('user_permissions')->where('user_id', $lockedUser->id)->delete();
            $now = now();
            $rows = array_map(fn (string $permissionKey) => [
                'user_id' => $lockedUser->id,
                'permission_key' => $permissionKey,
                'created_at' => $now,
                'updated_at' => $now,
            ], $keys);
            array_unshift($rows, [
                'user_id' => $lockedUser->id,
                'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('user_permissions')->insert($rows);
        });

        return redirect()->route('admin.roles.index', ['user_id' => $user->id])
            ->with('success', 'Đã lưu bộ quyền riêng cho '.$user->username.'.');
    }

}
