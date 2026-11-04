<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\DatabaseRole;
use App\Models\PriceSetting;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminPortalApiController extends ApiController
{
    public function me(Request $request)
    {
        $admin = $this->requireAdmin($request);
        if (! $admin) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        return $this->ok([
            'id' => (int) $admin->id,
            'fullname' => (string) $admin->fullname,
            'email' => (string) $admin->email,
            'role' => 'admin',
            'permissions' => $admin->effectiveInternalPermissions(),
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function dashboard(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        return $this->ok([
            'total_rooms' => Room::count(),
            'available_rooms' => Room::where('status', Room::STATUS_AVAILABLE)->count(),
            'occupied_rooms' => Room::where('status', Room::STATUS_OCCUPIED)->count(),
            'active_bookings' => Booking::whereIn('status', ['confirmed', 'checked_in'])->count(),
        ]);
    }

    public function reports(Request $request)
    {
        $staff = $this->requireStaffOrAdmin($request);
        if (! $staff) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập.', 401);
        }
        if (! $staff->hasInternalPermission('tk_view_dashboard')) {
            return $this->fail('FORBIDDEN_PERMISSION', 'Không có quyền xem báo cáo thống kê.', 403);
        }

        $completedBookings = Booking::where('status', 'completed')->count();
        $totalRevenueVnd = (int) round((float) Booking::where('payment_status', 'paid')->sum('total_price'));

        return $this->ok([
            'completed_bookings' => $completedBookings,
            'total_revenue_vnd' => $totalRevenueVnd,
        ]);
    }

    public function rooms(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $rooms = Room::with('roomType:id,type_name,price')->orderBy('floor')->orderBy('room_number')->get()->map(fn (Room $r) => [
            'id' => (int) $r->id,
            'room_number' => (string) $r->room_number,
            'room_type_id' => (int) $r->room_type_id,
            'room_type' => (string) ($r->roomType?->type_name ?? ''),
            'floor' => (int) $r->floor,
            'status' => (string) $r->status,
        ])->values()->all();

        return $this->ok($rooms);
    }

    public function storeRoom(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'room_number' => ['required', 'string', 'max:20', 'unique:rooms,room_number'],
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'floor' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['sometimes', 'in:available,occupied,cleaning,maintenance'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu phòng không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $room = Room::create(array_merge(['status' => Room::STATUS_AVAILABLE], $validator->validated()));

        return $this->ok([
            'id' => (int) $room->id,
            'room_number' => (string) $room->room_number,
            'room_type_id' => (int) $room->room_type_id,
            'floor' => (int) $room->floor,
            'status' => (string) $room->status,
        ], 201);
    }

    public function updateRoom(Request $request, Room $room)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'room_number' => ['sometimes', 'string', 'max:20', 'unique:rooms,room_number,'.$room->id],
            'room_type_id' => ['sometimes', 'integer', 'exists:room_types,id'],
            'floor' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'status' => ['sometimes', 'in:available,occupied,cleaning,maintenance'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu cập nhật phòng không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $room->update($validator->validated());

        return $this->ok([
            'id' => (int) $room->id,
            'room_number' => (string) $room->room_number,
            'room_type_id' => (int) $room->room_type_id,
            'floor' => (int) $room->floor,
            'status' => (string) $room->status,
        ]);
    }

    public function roomTypes(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $types = RoomType::orderBy('price')->get()->map(fn (RoomType $rt) => [
            'id' => (int) $rt->id,
            'type_name' => (string) $rt->type_name,
            'base_price_vnd' => (int) round((float) $rt->price),
            'max_adults' => (int) $rt->max_adults,
            'max_children' => (int) $rt->max_children,
            'max_guests' => (int) $rt->max_guests,
            'description' => (string) ($rt->description ?? ''),
        ])->values()->all();

        return $this->ok($types);
    }

    public function storeRoomType(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'type_name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'max_adults' => ['required', 'integer', 'min:1'],
            'max_children' => ['sometimes', 'integer', 'min:0'],
            'max_guests' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu hạng phòng không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $rt = RoomType::create($validator->validated());

        return $this->ok([
            'id' => (int) $rt->id,
            'type_name' => (string) $rt->type_name,
            'base_price_vnd' => (int) round((float) $rt->price),
            'max_guests' => (int) $rt->max_guests,
        ], 201);
    }

    public function updateRoomType(Request $request, RoomType $roomType)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'type_name' => ['sometimes', 'string', 'max:100'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'max_adults' => ['sometimes', 'integer', 'min:1'],
            'max_children' => ['sometimes', 'integer', 'min:0'],
            'max_guests' => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu cập nhật hạng phòng không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $roomType->update($validator->validated());

        return $this->ok([
            'id' => (int) $roomType->id,
            'type_name' => (string) $roomType->type_name,
            'base_price_vnd' => (int) round((float) $roomType->price),
            'max_guests' => (int) $roomType->max_guests,
        ]);
    }

    public function amenities(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        return $this->ok(Amenity::orderBy('name')->get(['id', 'name', 'icon'])->toArray());
    }

    public function storeAmenity(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu tiện ích không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $amenity = Amenity::create($validator->validated());

        return $this->ok(['id' => (int) $amenity->id, 'name' => (string) $amenity->name], 201);
    }

    public function updateAmenity(Request $request, Amenity $amenity)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu tiện ích không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $amenity->update($validator->validated());

        return $this->ok(['id' => (int) $amenity->id, 'name' => (string) $amenity->name]);
    }

    public function priceSettings(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $settings = PriceSetting::latest('id')->get()->map(fn (PriceSetting $s) => [
            'id' => (int) $s->id,
            'name' => (string) $s->name,
            'start_date' => $s->start_date,
            'end_date' => $s->end_date,
            'adjustment_type' => (string) $s->adjustment_type,
            'adjustment_value' => (float) $s->adjustment_value,
            'status' => (bool) $s->status,
            'is_active' => (bool) $s->status,
        ])->values()->all();

        return $this->ok($settings);
    }

    public function storePriceSetting(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'adjustment_type' => ['required', 'in:percent,fixed'],
            'adjustment_value' => ['required', 'numeric', 'min:0.01'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'boolean'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu cấu hình giá không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $data['status'] = (bool) ($data['status'] ?? $data['is_active'] ?? true);
        unset($data['is_active']);

        $lock = Cache::lock('price-settings-write', 10);
        if (! $lock->get()) {
            return $this->fail('PRICE_SETTING_BUSY', 'Một thay đổi giá khác đang được xử lý.', 409);
        }
        try {
            $setting = DB::transaction(function () use ($data) {
                PriceSetting::query()->where('status', true)->lockForUpdate()->get(['id']);
                if ($data['status'] && (new PriceSetting)->checkOverlap($data['start_date'], $data['end_date'])) {
                    return null;
                }

                return PriceSetting::create($data);
            });
        } finally {
            $lock->release();
        }
        if (! $setting) {
            return $this->fail('PRICE_SETTING_OVERLAP', 'Khoảng thời gian đã trùng với một cài đặt giá đang bật.', 422);
        }

        return $this->ok([
            'id' => (int) $setting->id,
            'name' => (string) $setting->name,
            'adjustment_type' => (string) $setting->adjustment_type,
            'adjustment_value' => (float) $setting->adjustment_value,
            'status' => (bool) $setting->status,
        ], 201);
    }

    public function updatePriceSetting(Request $request, PriceSetting $priceSetting)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:100'],
            'start_date' => ['sometimes', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'date_format:Y-m-d'],
            'adjustment_type' => ['sometimes', 'in:percent,fixed'],
            'adjustment_value' => ['sometimes', 'numeric', 'min:0.01'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'boolean'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu cấu hình giá không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $data['status'] = (bool) ($data['status'] ?? $data['is_active'] ?? $priceSetting->status);
        unset($data['is_active']);
        $startDate = (string) ($data['start_date'] ?? $priceSetting->start_date);
        $endDate = (string) ($data['end_date'] ?? $priceSetting->end_date);
        if ($endDate < $startDate) {
            return $this->fail('VALIDATION_ERROR', 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.', 422);
        }

        $lock = Cache::lock('price-settings-write', 10);
        if (! $lock->get()) {
            return $this->fail('PRICE_SETTING_BUSY', 'Một thay đổi giá khác đang được xử lý.', 409);
        }
        try {
            $updated = DB::transaction(function () use ($priceSetting, $data, $startDate, $endDate) {
                $setting = PriceSetting::query()->lockForUpdate()->findOrFail($priceSetting->id);
                PriceSetting::query()->where('status', true)->whereKeyNot($setting->id)->lockForUpdate()->get(['id']);
                if ($data['status'] && (new PriceSetting)->checkOverlap($startDate, $endDate, $setting->id)) {
                    return null;
                }
                $setting->update($data);

                return $setting->fresh();
            });
        } finally {
            $lock->release();
        }
        if (! $updated) {
            return $this->fail('PRICE_SETTING_OVERLAP', 'Khoảng thời gian đã trùng với một cài đặt giá đang bật.', 422);
        }
        $priceSetting = $updated;

        return $this->ok([
            'id' => (int) $priceSetting->id,
            'name' => (string) $priceSetting->name,
            'adjustment_type' => (string) $priceSetting->adjustment_type,
            'adjustment_value' => (float) $priceSetting->adjustment_value,
            'status' => (bool) $priceSetting->status,
        ]);
    }

    public function internalUsers(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $users = User::whereIn('role', ['admin', 'receptionist'])->orderBy('id')->get()->map(fn (User $u) => [
            'id' => (int) $u->id,
            'username' => (string) $u->username,
            'fullname' => (string) $u->fullname,
            'email' => (string) $u->email,
            'role' => (string) $u->role,
            'verified' => (bool) $u->verified,
        ])->values()->all();

        return $this->ok($users);
    }

    public function storeInternalUser(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'fullname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'role' => ['required', 'in:admin,receptionist'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu nhân sự không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $user = User::create([
            'username' => $data['username'],
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'verified' => true,
        ]);

        return $this->ok([
            'id' => (int) $user->id,
            'username' => (string) $user->username,
            'role' => (string) $user->role,
        ], 201);
    }

    public function updateInternalUser(Request $request, User $user)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'verified' => ['sometimes', 'boolean'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu cập nhật không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $user->update($validator->validated());

        return $this->ok([
            'id' => (int) $user->id,
            'fullname' => (string) $user->fullname,
            'role' => (string) $user->role,
        ]);
    }

    public function roles(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $roles = DatabaseRole::orderBy('id')->get()->map(fn (DatabaseRole $r) => [
            'id' => (int) $r->id,
            'code' => (string) $r->code,
            'name' => (string) $r->name,
            'permissions' => $r->permissions ?? [],
        ])->values()->all();

        return $this->ok($roles);
    }

    public function updateRolePermissions(Request $request, string $role)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Danh sách quyền không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $record = DatabaseRole::where('code', $role)->orWhere('id', $role)->first();
        if (! $record) {
            return $this->fail('ROLE_NOT_FOUND', 'Không tìm thấy nhóm quyền.', 404);
        }

        $record->update(['permissions' => array_values($validator->validated()['permissions'])]);

        return $this->ok([
            'code' => (string) $record->code,
            'permissions' => $record->permissions,
        ]);
    }

    public function updateUserPermissions(Request $request, User $user)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Danh sách quyền không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $user->update(['internal_permissions' => array_values($validator->validated()['permissions'])]);

        return $this->ok([
            'id' => (int) $user->id,
            'permissions' => $user->effectiveInternalPermissions(),
        ]);
    }

    private function requireAdmin(Request $request): ?User
    {
        $id = (int) ($request->session()->get('admin_user_id') ?: $request->session()->get('staff_user_id') ?: Auth::guard('admin')->id() ?: Auth::guard('staff')->id() ?: 0);
        if ($id <= 0) {
            return null;
        }

        $user = User::find($id);

        return ($user && $user->role === 'admin') ? $user : null;
    }

    private function requireStaffOrAdmin(Request $request): ?User
    {
        $id = (int) ($request->session()->get('staff_user_id') ?: $request->session()->get('admin_user_id') ?: Auth::guard('staff')->id() ?: Auth::guard('admin')->id() ?: 0);
        if ($id <= 0) {
            return null;
        }

        $user = User::find($id);

        return ($user && in_array($user->role, ['admin', 'receptionist'], true)) ? $user : null;
    }
}
