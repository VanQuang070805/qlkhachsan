<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Booking;
use App\Models\PaymentLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StaffPortalApiController extends ApiController
{
    public function me(Request $request)
    {
        $staff = $this->requireStaff($request);
        if (! $staff) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập cổng nhân viên.', 401);
        }

        return $this->ok([
            'id' => (int) $staff->id,
            'fullname' => (string) $staff->fullname,
            'email' => (string) $staff->email,
            'role' => (string) $staff->role,
            'permissions' => $staff->effectiveInternalPermissions(),
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function bookings(Request $request)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_view_schedule')) {
            return $resp;
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $statusFilter = $request->query('status');
        $query = Booking::with('rooms.roomType:id,type_name,price')->latest('id');

        if (is_string($statusFilter) && in_array($statusFilter, ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'], true)) {
            $query->where('status', $statusFilter);
        }

        return $this->paginated($query->paginate($perPage), fn (Booking $b) => $this->formatStaffBooking($b), [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function showBooking(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_view_schedule')) {
            return $resp;
        }

        $record = Booking::with('rooms.roomType:id,type_name,price')->find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }

        return $this->ok($this->formatStaffBooking($record), 200, ['Cache-Control' => 'private, no-store']);
    }

    public function storeBooking(Request $request)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_create_booking')) {
            return $resp;
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'room_ids' => ['required', 'array', 'min:1'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'adult_count' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu đặt phòng tại quầy chưa hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $booking = DB::transaction(function () use ($data) {
            $roomIds = array_map('intval', $data['room_ids']);
            $rooms = Room::with('roomType')->whereIn('id', $roomIds)->orderBy('id')->lockForUpdate()->get();
            $reserved = Booking::reservedRoomIds($data['check_in'], $data['check_out']);

            if ($rooms->count() !== count($roomIds) || $reserved->intersect($roomIds)->isNotEmpty()) {
                return null;
            }

            $total = (int) round($rooms->sum(fn (Room $r) => (float) ($r->roomType?->price ?? 0)));
            $record = Booking::create([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? 'walkin@rosaliza.local',
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'adult_count' => (int) $data['adult_count'],
                'child_count' => 0,
                'total_price' => $total,
                'deposit_amount' => (int) round($total / 2),
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'status' => 'confirmed',
            ]);
            $record->rooms()->attach($roomIds);

            return $record->load('rooms.roomType');
        });

        if (! $booking) {
            return $this->fail('BOOKING_ROOM_UNAVAILABLE', 'Một hoặc nhiều phòng đã có lịch đặt trùng.', 409);
        }

        return $this->ok($this->formatStaffBooking($booking), 201);
    }

    public function confirmBooking(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_create_booking')) {
            return $resp;
        }

        $record = Booking::find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if ($record->status !== 'pending') {
            return $this->fail('BOOKING_STATE_CONFLICT', 'Chỉ có thể xác nhận đơn đang ở trạng thái chờ.', 409);
        }

        $record->update(['status' => 'confirmed']);

        return $this->ok($this->formatStaffBooking($record->fresh('rooms.roomType')));
    }

    public function checkIn(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_checkin_checkout')) {
            return $resp;
        }

        $result = DB::transaction(function () use ($booking) {
            $record = Booking::with('rooms')->lockForUpdate()->find($booking);
            if (! $record) {
                return ['error' => 'NOT_FOUND'];
            }
            if ($record->status !== 'confirmed') {
                return ['error' => 'CONFLICT'];
            }

            $record->update([
                'status' => 'checked_in',
                'actual_check_in' => now(),
            ]);

            Room::whereIn('id', $record->rooms->pluck('id'))->update([
                'status' => Room::STATUS_OCCUPIED,
            ]);

            return ['booking' => $record->fresh('rooms.roomType')];
        });

        if (($result['error'] ?? null) === 'NOT_FOUND') {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if (($result['error'] ?? null) === 'CONFLICT') {
            return $this->fail('BOOKING_STATE_CONFLICT', 'Chỉ có thể nhận phòng cho đơn đã xác nhận.', 409);
        }

        return $this->ok($this->formatStaffBooking($result['booking']));
    }

    public function checkOut(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_checkin_checkout')) {
            return $resp;
        }

        $result = DB::transaction(function () use ($booking) {
            $record = Booking::with('rooms')->lockForUpdate()->find($booking);
            if (! $record) {
                return ['error' => 'NOT_FOUND'];
            }
            if ($record->status !== 'checked_in') {
                return ['error' => 'CONFLICT'];
            }

            $record->update([
                'status' => 'completed',
                'actual_check_out' => now(),
                'payment_status' => 'paid',
            ]);

            Room::whereIn('id', $record->rooms->pluck('id'))->update([
                'status' => Room::STATUS_CLEANING,
                'needs_cleaning' => true,
                'cleaning_requested_at' => now(),
            ]);

            return ['booking' => $record->fresh('rooms.roomType')];
        });

        if (($result['error'] ?? null) === 'NOT_FOUND') {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if (($result['error'] ?? null) === 'CONFLICT') {
            return $this->fail('BOOKING_STATE_CONFLICT', 'Chỉ có thể trả phòng cho đơn đang lưu trú.', 409);
        }

        return $this->ok($this->formatStaffBooking($result['booking']));
    }

    public function extendStay(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_extend_stay')) {
            return $resp;
        }

        $record = Booking::with('rooms.roomType')->find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }

        $validator = Validator::make($request->all(), [
            'new_check_out' => ['required', 'date_format:Y-m-d'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Ngày trả phòng mới không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $record->update(['check_out' => $request->input('new_check_out')]);

        return $this->ok($this->formatStaffBooking($record->fresh('rooms.roomType')));
    }

    public function checkoutPayment(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'tn_collect_payment')) {
            return $resp;
        }

        $record = Booking::with('rooms.roomType')->find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if ($record->status !== 'checked_in') {
            return $this->fail('BOOKING_STATE_CONFLICT', 'Chỉ có thể thu tiền trả phòng cho đơn đang lưu trú.', 409);
        }

        $outstanding = (int) round($record->outstandingAmount());
        if ($outstanding > 0) {
            PaymentLog::create([
                'booking_id' => $record->id,
                'gateway' => (string) $request->input('payment_method', 'cash'),
                'transaction_id' => 'staff-co-'.$record->id.'-'.Str::uuid(),
                'amount' => $outstanding,
                'purpose' => 'checkout',
                'status' => 'success',
            ]);
        }

        $record->update([
            'status' => 'completed',
            'payment_status' => 'paid',
            'actual_check_out' => now(),
        ]);

        return $this->ok($this->formatStaffBooking($record->fresh('rooms.roomType')));
    }

    public function refundBooking(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'tn_refund')) {
            return $resp;
        }

        $result = DB::transaction(function () use ($booking) {
            $record = Booking::lockForUpdate()->find($booking);
            if (! $record) {
                return ['error' => 'NOT_FOUND'];
            }
            if ($record->status !== 'cancelled' || $record->refund_status !== 'eligible') {
                return ['error' => 'CONFLICT'];
            }

            $record->update([
                'refund_status' => 'refunded',
                'refunded_at' => now(),
            ]);

            return ['booking' => $record->fresh('rooms.roomType')];
        });

        if (($result['error'] ?? null) === 'NOT_FOUND') {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if (($result['error'] ?? null) === 'CONFLICT') {
            return $this->fail('REFUND_NOT_ELIGIBLE', 'Đơn đặt phòng không đủ điều kiện hoàn tiền hoặc đã được hoàn tiền.', 409);
        }

        return $this->ok($this->formatStaffBooking($result['booking']));
    }

    public function rooms(Request $request)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_view_schedule')) {
            return $resp;
        }

        $rooms = Room::with('roomType:id,type_name,price')
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $r) => [
                'id' => (int) $r->id,
                'room_number' => (string) $r->room_number,
                'floor' => (int) $r->floor,
                'status' => (string) $r->status,
                'needs_cleaning' => (bool) $r->needs_cleaning,
                'room_type' => (string) ($r->roomType?->type_name ?? ''),
                'base_price_vnd' => (int) round((float) ($r->roomType?->price ?? 0)),
            ])->values()->all();

        return $this->ok($rooms);
    }

    public function currentBookingForRoom(Request $request, Room $room)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_view_schedule')) {
            return $resp;
        }

        $booking = $room->bookings()
            ->whereIn('bookings.status', ['confirmed', 'checked_in'])
            ->latest('bookings.id')
            ->first();

        return $this->ok($booking ? $this->formatStaffBooking($booking->load('rooms.roomType')) : null);
    }

    public function completeCleaning(Request $request, Room $room)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_clean_status')) {
            return $resp;
        }

        $room->update([
            'status' => Room::STATUS_AVAILABLE,
            'needs_cleaning' => false,
            'cleaning_requested_at' => null,
        ]);

        return $this->ok([
            'id' => (int) $room->id,
            'room_number' => (string) $room->room_number,
            'status' => (string) $room->status,
            'needs_cleaning' => (bool) $room->needs_cleaning,
        ]);
    }

    public function cleaningNotifications(Request $request)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_view_schedule')) {
            return $resp;
        }

        $rooms = Room::where('needs_cleaning', true)
            ->orderBy('room_number')
            ->get(['id', 'room_number', 'floor', 'cleaning_requested_at'])
            ->map(fn (Room $r) => [
                'id' => (int) $r->id,
                'room_number' => (string) $r->room_number,
                'floor' => (int) $r->floor,
                'cleaning_requested_at' => $r->cleaning_requested_at?->toIso8601String(),
            ])->values()->all();

        return $this->ok($rooms);
    }

    public function resolveCheckInScan(Request $request)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_checkin_checkout')) {
            return $resp;
        }

        $bookingId = (int) $request->input('booking_id', 0);
        $booking = Booking::with('rooms.roomType')->find($bookingId);
        if (! $booking) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng từ mã quét.', 404);
        }

        return $this->ok($this->formatStaffBooking($booking));
    }

    private function requireStaff(Request $request): ?User
    {
        $id = (int) ($request->session()->get('staff_user_id') ?: Auth::guard('staff')->id() ?: Auth::guard('admin')->id() ?: 0);
        if ($id <= 0) {
            return null;
        }

        $user = User::find($id);

        return ($user && in_array($user->role, ['receptionist', 'admin'], true)) ? $user : null;
    }

    private function authorizeStaffPermission(Request $request, string $permission)
    {
        $staff = $this->requireStaff($request);
        if (! $staff) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập cổng nhân viên.', 401);
        }

        if (! $staff->hasInternalPermission($permission)) {
            return $this->fail('FORBIDDEN_PERMISSION', "Tài khoản không có quyền thực hiện thao tác ({$permission}).", 403);
        }

        return null;
    }

    private function formatStaffBooking(Booking $booking): array
    {
        return [
            'id' => (int) $booking->id,
            'customer_name' => (string) $booking->customer_name,
            'customer_email' => (string) $booking->customer_email,
            'customer_phone' => (string) $booking->customer_phone,
            'check_in' => $booking->check_in?->toDateString(),
            'check_out' => $booking->check_out?->toDateString(),
            'status' => (string) $booking->status,
            'payment_status' => (string) $booking->payment_status,
            'payment_method' => $booking->payment_method,
            'refund_status' => (string) ($booking->refund_status ?? 'none'),
            'total_price_vnd' => (int) round((float) $booking->total_price),
            'deposit_amount_vnd' => (int) round((float) $booking->deposit_amount),
            'rooms' => $booking->relationLoaded('rooms') ? $booking->rooms->map(fn (Room $r) => [
                'id' => (int) $r->id,
                'room_number' => (string) $r->room_number,
                'room_type' => (string) ($r->roomType?->type_name ?? ''),
            ])->values()->all() : [],
        ];
    }
}
