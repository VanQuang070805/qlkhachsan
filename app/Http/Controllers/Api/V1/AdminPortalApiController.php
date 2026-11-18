<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\PriceSetting;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\SessionRevocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        if (! $staff->hasInternalPermission('rp_view_reports')) {
            return $this->fail('FORBIDDEN_PERMISSION', 'Không có quyền xem báo cáo thống kê.', 403);
        }

        $filters = [
            'start_date' => (string) $request->query('start_date', ''),
            'end_date' => (string) $request->query('end_date', ''),
            'room_type_id' => (string) $request->query('room_type_id', ''),
            'status' => (string) $request->query('status', ''),
        ];
        $stats = (new Booking())->getReportStats($filters);

        return $this->ok([
            'completed_bookings' => (int) ($stats['completed_count'] ?? 0),
            'total_bookings' => (int) ($stats['total_bookings'] ?? 0),
            'total_nights' => (int) ($stats['total_nights'] ?? 0),
            'total_revenue_vnd' => (int) round((float) ($stats['total_revenue'] ?? 0)),
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
            'floor' => ['required', 'integer', 'min:1', 'max:10'],
            'status' => ['sometimes', 'in:available,cleaning,maintenance'],
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
            'floor' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'status' => ['sometimes', 'in:available,cleaning,maintenance'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu cập nhật phòng không hợp lệ.', 422, $validator->errors()->toArray());
        }

        if (in_array($room->status, [Room::STATUS_OCCUPIED, Room::STATUS_OVERDUE], true) && $request->has('status')) {
            return $this->fail('ROOM_OCCUPIED', 'Không thể đổi trạng thái phòng đang có khách lưu trú.', 409);
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
            'type_name' => ['required', 'string', 'max:100', 'unique:room_types,type_name'],
            'price' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'max_adults' => ['required', 'integer', 'min:1', 'max:100'],
            'max_children' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'max_guests' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu hạng phòng không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $children = (int) ($data['max_children'] ?? 0);
        if ((int) $data['max_guests'] < (int) $data['max_adults'] + $children) {
            return $this->fail('VALIDATION_ERROR', 'Tổng sức chứa phải lớn hơn hoặc bằng sức chứa người lớn và trẻ em.', 422, [
                'max_guests' => ['Tổng sức chứa phải lớn hơn hoặc bằng sức chứa người lớn và trẻ em.'],
            ]);
        }

        $rt = RoomType::create($data);

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
            'type_name' => ['sometimes', 'string', 'max:100', Rule::unique('room_types', 'type_name')->ignore($roomType->id)],
            'price' => ['sometimes', 'numeric', 'min:1', 'max:9999999999.99'],
            'max_adults' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'max_children' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'max_guests' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu cập nhật hạng phòng không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $maxAdults = (int) ($data['max_adults'] ?? $roomType->max_adults);
        $maxChildren = (int) ($data['max_children'] ?? $roomType->max_children);
        $maxGuests = (int) ($data['max_guests'] ?? $roomType->max_guests);
        if ($maxGuests < $maxAdults + $maxChildren) {
            return $this->fail('VALIDATION_ERROR', 'Tổng sức chứa phải lớn hơn hoặc bằng sức chứa người lớn và trẻ em.', 422, [
                'max_guests' => ['Tổng sức chứa phải lớn hơn hoặc bằng sức chứa người lớn và trẻ em.'],
            ]);
        }

        $roomType->update($data);

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

        $items = Amenity::orderBy('amenity_name')->get(['id', 'amenity_name', 'icon'])->map(fn (Amenity $a) => [
            'id' => (int) $a->id,
            'name' => (string) $a->amenity_name,
            'amenity_name' => (string) $a->amenity_name,
            'icon' => (string) ($a->icon ?? ''),
        ])->values()->all();

        return $this->ok($items);
    }

    public function storeAmenity(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $nameInput = $request->input('amenity_name', $request->input('name'));
        $validator = Validator::make(['amenity_name' => $nameInput, 'icon' => $request->input('icon')], [
            'amenity_name' => ['required', 'string', 'max:100', 'unique:amenities,amenity_name'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu tiện ích không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $amenity = Amenity::create($validator->validated());

        return $this->ok(['id' => (int) $amenity->id, 'name' => (string) $amenity->amenity_name, 'amenity_name' => (string) $amenity->amenity_name], 201);
    }

    public function updateAmenity(Request $request, Amenity $amenity)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $payload = [];
        if ($request->has('amenity_name') || $request->has('name')) {
            $payload['amenity_name'] = $request->input('amenity_name', $request->input('name'));
        }
        if ($request->has('icon')) {
            $payload['icon'] = $request->input('icon');
        }
        $validator = Validator::make($payload, [
            'amenity_name' => ['sometimes', 'string', 'max:100', Rule::unique('amenities', 'amenity_name')->ignore($amenity->id)],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu tiện ích không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $amenity->update($validator->validated());

        return $this->ok(['id' => (int) $amenity->id, 'name' => (string) $amenity->amenity_name, 'amenity_name' => (string) $amenity->amenity_name]);
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

        $maxVal = $request->input('adjustment_type') === 'percent' ? 500 : 9999999999.99;
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'adjustment_type' => ['required', 'in:percent,fixed'],
            'adjustment_value' => ['required', 'numeric', 'min:0.01', 'max:'.$maxVal],
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

        $adjType = (string) $request->input('adjustment_type', $priceSetting->adjustment_type);
        $maxVal = $adjType === 'percent' ? 500 : 9999999999.99;
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:100'],
            'start_date' => ['sometimes', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'date_format:Y-m-d'],
            'adjustment_type' => ['sometimes', 'in:percent,fixed'],
            'adjustment_value' => ['sometimes', 'numeric', 'min:0.01', 'max:'.$maxVal],
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
            'phone' => ['required', 'regex:/^[0-9]{7,15}$/'],
            'role' => ['required', 'in:admin,receptionist'],
            'password' => ['required', 'string', 'min:6'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu nhân sự không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $user = User::create([
            'username' => $data['username'],
            'fullname' => $data['fullname'],
            'email' => mb_strtolower($data['email']),
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
        $admin = $this->requireAdmin($request);
        if (! $admin) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }
        if ($user->isCustomer()) {
            return $this->fail('FORBIDDEN_TARGET_USER', 'Không thể cập nhật khách hàng qua cổng nhân sự nội bộ.', 403);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'regex:/^[0-9]{7,15}$/'],
            'verified' => ['sometimes', 'boolean'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu cập nhật không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        if (array_key_exists('verified', $data) && $data['verified'] === false) {
            if ((int) $user->id === (int) $admin->id) {
                return $this->fail('CANNOT_LOCK_SELF', 'Không thể tự khóa tài khoản đang đăng nhập.', 409);
            }
            if ($user->isAdmin() && $user->verified && User::where('role', 'admin')->where('verified', true)->count() <= 1) {
                return $this->fail('LAST_ACTIVE_ADMIN', 'Không thể khóa quản trị viên hoạt động cuối cùng.', 409);
            }
        }

        $user->update($data);
        if (array_key_exists('verified', $data) && $data['verified'] === false) {
            app(SessionRevocationService::class)->revoke($user);
        }

        return $this->ok([
            'id' => (int) $user->id,
            'fullname' => (string) $user->fullname,
            'role' => (string) $user->role,
            'verified' => (bool) $user->verified,
        ]);
    }

    public function roles(Request $request)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }

        $roles = Role::where('slug', '<>', 'customer')->orderBy('id')->get()->map(fn (Role $r) => [
            'id' => (int) $r->id,
            'code' => (string) $r->slug,
            'slug' => (string) $r->slug,
            'name' => (string) $r->name,
            'permissions' => $r->slug === 'admin'
                ? array_keys(User::INTERNAL_PERMISSION_CATALOG)
                : $r->permissionKeys(),
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
            'permissions.*' => ['string', Rule::in(array_keys(User::INTERNAL_PERMISSION_CATALOG))],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Danh sách quyền không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $record = Role::where('slug', $role)->when(ctype_digit($role), fn ($q) => $q->orWhere('id', (int) $role))->first();
        if (! $record || in_array($record->slug, ['admin', 'customer'], true)) {
            return $this->fail('ROLE_NOT_FOUND', 'Không tìm thấy nhóm quyền hợp lệ để chỉnh sửa.', 404);
        }

        $keys = array_values(array_unique($validator->validated()['permissions']));
        DB::transaction(function () use ($record, $keys): void {
            DB::table('role_permissions')->where('role_id', $record->id)->delete();
            $now = now();
            if ($keys !== []) {
                DB::table('role_permissions')->insert(array_map(fn (string $key) => [
                    'role_id' => $record->id,
                    'permission_key' => $key,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $keys));
            }
        });

        return $this->ok([
            'code' => (string) $record->slug,
            'slug' => (string) $record->slug,
            'permissions' => $record->permissionKeys(),
        ]);
    }

    public function updateUserPermissions(Request $request, User $user)
    {
        if (! $this->requireAdmin($request)) {
            return $this->fail('FORBIDDEN_ADMIN_ONLY', 'Chỉ Quản trị viên mới có quyền truy cập.', 403);
        }
        if ($user->isCustomer() || $user->isAdmin()) {
            return $this->fail('FORBIDDEN_TARGET_USER', 'Chỉ áp dụng bộ quyền riêng cho nhân viên vận hành.', 422);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(User::INTERNAL_PERMISSION_CATALOG))],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Danh sách quyền không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $keys = array_values(array_unique($validator->validated()['permissions']));
        DB::transaction(function () use ($user, $keys): void {
            DB::table('user_permissions')->where('user_id', $user->id)->delete();
            $now = now();
            $rows = array_map(fn (string $key) => [
                'user_id' => $user->id,
                'permission_key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ], $keys);
            array_unshift($rows, [
                'user_id' => $user->id,
                'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('user_permissions')->insert($rows);
        });

        return $this->ok([
            'id' => (int) $user->id,
            'permissions' => $user->fresh()->effectiveInternalPermissions(),
        ]);
    }

    private function requireAdmin(Request $request): ?User
    {
        $id = (int) ($request->session()->get('admin_user_id') ?: $request->session()->get('staff_user_id') ?: Auth::guard('admin')->id() ?: Auth::guard('staff')->id() ?: 0);
        if ($id <= 0) {
            return null;
        }

        $user = User::find($id);

        return ($user && $user->role === 'admin' && $user->verified) ? $user : null;
    }

    private function requireStaffOrAdmin(Request $request): ?User
    {
        $id = (int) ($request->session()->get('staff_user_id') ?: $request->session()->get('admin_user_id') ?: Auth::guard('staff')->id() ?: Auth::guard('admin')->id() ?: 0);
        if ($id <= 0) {
            return null;
        }

        $user = User::find($id);

        return ($user && in_array($user->role, ['admin', 'receptionist'], true) && $user->verified) ? $user : null;
    }
}
