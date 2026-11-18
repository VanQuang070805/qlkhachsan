<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Booking;
use App\Models\PaymentLog;
use App\Models\PriceSetting;
use App\Models\RefundTransaction;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingTransitionService;
use App\Services\CheckInTokenService;
use App\Services\StayExtensionService;
use App\Support\StayDates;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
            'customer_phone' => ['required', 'regex:/^0[0-9]{9,10}$/'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'room_ids' => ['required', 'array', 'min:1', 'max:25'],
            'room_ids.*' => ['integer', 'distinct', 'exists:rooms,id'],
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'adult_count' => ['required', 'integer', 'min:1', 'max:100'],
            'child_count' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu đặt phòng tại quầy chưa hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        if ($data['check_in'] === now('Asia/Ho_Chi_Minh')->toDateString() && now('Asia/Ho_Chi_Minh')->hour >= 18) {
            return $this->fail('CHECKIN_WINDOW_CLOSED', 'Sau 18:00 không thể tạo đơn giữ phòng chờ nhận trong ngày.', 422);
        }
        try {
            StayDates::validate($data['check_in'], $data['check_out']);
        } catch (ValidationException $e) {
            return $this->fail('VALIDATION_ERROR', 'Khoảng ngày lưu trú không hợp lệ.', 422, $e->errors());
        }

        $adults = (int) $data['adult_count'];
        $children = (int) ($data['child_count'] ?? 0);

        $result = DB::transaction(function () use ($data, $adults, $children) {
            $roomIds = array_map('intval', $data['room_ids']);
            $rooms = Room::with('roomType')->whereIn('id', $roomIds)->orderBy('id')->lockForUpdate()->get();
            $reserved = Booking::reservedRoomIds($data['check_in'], $data['check_out']);

            if ($rooms->count() !== count($roomIds)
                || $rooms->contains(fn (Room $r) => $r->status !== Room::STATUS_AVAILABLE)
                || $reserved->intersect($roomIds)->isNotEmpty()) {
                return ['error' => 'UNAVAILABLE'];
            }

            if ($rooms->sum(fn (Room $r) => (int) ($r->roomType?->max_adults ?? 0)) < $adults
                || $rooms->sum(fn (Room $r) => (int) ($r->roomType?->max_children ?? 0)) < $children
                || $rooms->sum(fn (Room $r) => (int) ($r->roomType?->max_guests ?? 0)) < ($adults + $children)) {
                return ['error' => 'CAPACITY'];
            }

            $total = (int) round($rooms->sum(fn (Room $r) => PriceSetting::calculateTotalPrice(
                (float) ($r->roomType?->price ?? 0),
                $data['check_in'],
                $data['check_out'],
            )));
            $record = Booking::create([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? '',
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'adult_count' => $adults,
                'child_count' => $children,
                'total_price' => $total,
                'deposit_amount' => 0,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'status' => 'confirmed',
            ]);
            $record->rooms()->attach($roomIds);

            return ['booking' => $record->load('rooms.roomType')];
        });

        if (($result['error'] ?? null) === 'CAPACITY') {
            return $this->fail('CAPACITY_EXCEEDED', 'Các phòng đã chọn không đủ sức chứa cho số khách.', 422);
        }
        if (! isset($result['booking'])) {
            return $this->fail('BOOKING_ROOM_UNAVAILABLE', 'Một hoặc nhiều phòng đã có lịch đặt trùng hoặc chưa sẵn sàng.', 409);
        }

        return $this->ok($this->formatStaffBooking($result['booking']), 201);
    }

    public function confirmBooking(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_create_booking')) {
            return $resp;
        }

        $result = DB::transaction(function () use ($booking) {
            $record = Booking::lockForUpdate()->find($booking);
            if (! $record) {
                return ['error' => 'NOT_FOUND'];
            }
            if ($record->status !== 'pending') {
                return ['error' => 'CONFLICT'];
            }
            if ($record->payment_status === 'pending' && $record->paymentHoldExpired()) {
                return ['error' => 'EXPIRED'];
            }
            $record->update(['status' => 'confirmed']);

            return ['booking' => $record->fresh('rooms.roomType')];
        });

        if (($result['error'] ?? null) === 'NOT_FOUND') {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if (($result['error'] ?? null) === 'EXPIRED') {
            return $this->fail('BOOKING_HOLD_EXPIRED', 'Thời hạn giữ phòng đã hết. Khách cần đặt lại phòng.', 409);
        }
        if (($result['error'] ?? null) === 'CONFLICT') {
            return $this->fail('BOOKING_STATE_CONFLICT', 'Chỉ có thể xác nhận đơn đang ở trạng thái chờ.', 409);
        }

        return $this->ok($this->formatStaffBooking($result['booking']));
    }

    public function checkIn(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_checkin_checkout')) {
            return $resp;
        }

        if (! Booking::whereKey($booking)->exists()) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }

        try {
            $updated = app(BookingTransitionService::class)->checkIn($booking);
        } catch (DomainException $e) {
            return $this->fail('BOOKING_STATE_CONFLICT', $e->getMessage(), 409);
        }

        return $this->ok($this->formatStaffBooking($updated->load('rooms.roomType')));
    }

    public function checkOut(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_checkin_checkout')) {
            return $resp;
        }
        if ($resp = $this->authorizeStaffPermission($request, 'tn_collect_payment')) {
            return $resp;
        }

        $result = DB::transaction(function () use ($booking) {
            $record = Booking::with('rooms.roomType')->lockForUpdate()->find($booking);
            if (! $record) {
                return ['error' => 'NOT_FOUND'];
            }
            if ($record->status !== 'checked_in') {
                return ['error' => 'CONFLICT'];
            }
            if ($record->outstandingAmount() > 0) {
                return ['error' => 'UNPAID'];
            }

            $record->update([
                'status' => 'completed',
                'actual_check_out' => now(),
                'payment_status' => 'paid',
            ]);

            Room::whereIn('id', $record->rooms->pluck('id'))
                ->whereIn('status', [Room::STATUS_OCCUPIED, Room::STATUS_OVERDUE])
                ->update([
                    'status' => Room::STATUS_CLEANING,
                    'needs_cleaning' => true,
                    'cleaning_requested_at' => now(),
                ]);

            return ['booking' => $record->fresh('rooms.roomType')];
        });

        if (($result['error'] ?? null) === 'NOT_FOUND') {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if (($result['error'] ?? null) === 'UNPAID') {
            return $this->fail('BOOKING_UNPAID_BALANCE', 'Đơn lưu trú còn số dư chưa thanh toán. Vui lòng sử dụng cổng thu tiền trả phòng.', 409);
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
            'new_check_out' => ['sometimes', 'date_format:Y-m-d'],
            'mode' => ['sometimes', 'in:hours,days'],
            'amount' => ['sometimes', 'integer', 'min:1', 'max:30'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu gia hạn không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $mode = (string) $request->input('mode', 'days');
        if ($request->filled('new_check_out')) {
            $newCheckOut = (string) $request->input('new_check_out');
            if ($newCheckOut <= $record->check_out->toDateString()) {
                return $this->fail('INVALID_EXTENSION_DATE', 'Ngày trả phòng mới phải sau ngày trả phòng hiện tại.', 422);
            }
            $mode = 'days';
            $amount = (int) $record->check_out->diffInDays(Carbon::parse($newCheckOut));
        } else {
            $amount = (int) $request->input('amount', 0);
        }

        try {
            $result = app(StayExtensionService::class)->extend($record->id, $mode, $amount, true);
        } catch (DomainException $e) {
            return $this->fail('EXTENSION_DENIED', $e->getMessage(), 409);
        }

        return $this->ok($this->formatStaffBooking($result['booking']->load('rooms.roomType')));
    }

    public function checkoutPayment(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_checkin_checkout')) {
            return $resp;
        }
        if ($resp = $this->authorizeStaffPermission($request, 'tn_collect_payment')) {
            return $resp;
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => ['sometimes', 'in:cash,vietqr,momo,zalopay,vnpay'],
            'waive_late_fee' => ['sometimes', 'boolean'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Phương thức thanh toán trả phòng không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $staff = $this->requireStaff($request);
        $method = (string) $request->input('payment_method', 'cash');
        $waive = $request->boolean('waive_late_fee', false);

        $result = DB::transaction(function () use ($booking, $method, $waive, $staff) {
            $record = Booking::with('rooms.roomType')->lockForUpdate()->find($booking);
            if (! $record) {
                return ['error' => 'NOT_FOUND'];
            }
            if ($record->status !== 'checked_in') {
                return ['error' => 'CONFLICT'];
            }

            $record->waive_late_fee = $waive;
            $deadline = $record->scheduledCheckoutAt()->addHour();
            $inHouseRooms = $record->rooms->whereIn('status', [Room::STATUS_OCCUPIED, Room::STATUS_OVERDUE]);
            $billableRooms = $inHouseRooms->isNotEmpty() ? $inHouseRooms : $record->rooms;
            $lateFee = ($waive || now('Asia/Ho_Chi_Minh')->lte($deadline))
                ? 0.0
                : round($billableRooms->sum(fn (Room $r) => (float) ($r->roomType?->price ?? 0)) * 0.5);
            $record->late_checkout_fee = $lateFee;

            $outstanding = (int) round($record->outstandingAmount());
            if ($outstanding > 0) {
                PaymentLog::create([
                    'booking_id' => $record->id,
                    'gateway' => $method,
                    'transaction_id' => 'staff-co-'.$record->id.'-'.Str::uuid(),
                    'amount' => $outstanding,
                    'purpose' => 'checkout',
                    'status' => 'success',
                    'raw_response' => ['received_by' => $staff?->id],
                ]);
            }

            $record->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => $method,
                'late_checkout_fee' => $lateFee,
                'actual_check_out' => now(),
            ]);

            Room::whereIn('id', $record->rooms->pluck('id'))
                ->whereIn('status', [Room::STATUS_OCCUPIED, Room::STATUS_OVERDUE])
                ->update([
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
            return $this->fail('BOOKING_STATE_CONFLICT', 'Chỉ có thể thu tiền trả phòng cho đơn đang lưu trú.', 409);
        }

        return $this->ok($this->formatStaffBooking($result['booking']));
    }

    public function refundBooking(Request $request, int $booking)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'tn_refund')) {
            return $resp;
        }

        $validator = Validator::make($request->all(), [
            'provider_reference' => ['nullable', 'string', 'max:120', 'unique:refund_transactions,provider_reference'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Mã tham chiếu hoàn tiền không hợp lệ hoặc đã tồn tại.', 422, $validator->errors()->toArray());
        }

        $staff = $this->requireStaff($request);
        $referenceInput = trim((string) $request->input('provider_reference', ''));

        $result = DB::transaction(function () use ($booking, $staff, $referenceInput) {
            $record = Booking::lockForUpdate()->find($booking);
            if (! $record) {
                return ['error' => 'NOT_FOUND'];
            }
            if ($record->status !== 'cancelled' || $record->refund_status !== 'eligible' || (float) $record->refund_amount <= 0) {
                return ['error' => 'CONFLICT'];
            }

            $reference = $referenceInput !== ''
                ? $referenceInput
                : 'API-REFUND-'.$record->id.'-'.now()->format('YmdHisv');

            RefundTransaction::create([
                'booking_id' => $record->id,
                'processed_by' => $staff?->id,
                'amount' => $record->refund_amount,
                'provider' => $record->payment_method ?: 'manual',
                'provider_reference' => $reference,
                'status' => 'completed',
                'processed_at' => now(),
                'metadata' => ['source' => 'staff_api_v1'],
            ]);

            $record->update([
                'refund_status' => 'refunded',
                'payment_status' => 'refunded',
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

        $updated = DB::transaction(function () use ($room) {
            $locked = Room::lockForUpdate()->findOrFail($room->id);
            if ($locked->status === Room::STATUS_OCCUPIED && $locked->needs_cleaning) {
                $locked->update([
                    'needs_cleaning' => false,
                    'cleaning_requested_at' => null,
                ]);
                return $locked;
            }
            if ($locked->status !== Room::STATUS_CLEANING) {
                return null;
            }
            $locked->update([
                'status' => Room::STATUS_AVAILABLE,
                'needs_cleaning' => false,
                'cleaning_requested_at' => null,
            ]);
            return $locked;
        });

        if (! $updated) {
            return $this->fail('ROOM_STATE_CONFLICT', 'Phòng không ở trạng thái chờ dọn dẹp.', 409);
        }

        return $this->ok([
            'id' => (int) $updated->id,
            'room_number' => (string) $updated->room_number,
            'status' => (string) $updated->status,
            'needs_cleaning' => (bool) $updated->needs_cleaning,
        ]);
    }

    public function cleaningNotifications(Request $request)
    {
        if ($resp = $this->authorizeStaffPermission($request, 'bp_clean_status')) {
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

        $qrPayload = trim((string) $request->input('payload', $request->input('token', '')));
        if ($qrPayload === '') {
            return $this->fail('INVALID_CHECKIN_TOKEN', 'Vui lòng cung cấp mã QR nhận phòng hợp lệ.', 422);
        }

        try {
            $tokens = app(CheckInTokenService::class);
            $token = $tokens->tokenFromQrPayload($qrPayload);
            $bookingId = $tokens->bookingId($token);
        } catch (RuntimeException $e) {
            return $this->fail('INVALID_CHECKIN_TOKEN', $e->getMessage(), 422);
        }

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

        return ($user && in_array($user->role, ['receptionist', 'admin'], true) && $user->verified) ? $user : null;
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
