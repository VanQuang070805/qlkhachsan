<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PriceSetting;

class BookingController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // CUSTOMER
    // ──────────────────────────────────────────────────────────

    /**
     * Hiển thị form đặt phòng.
     * Nhận room_ids[], check_in, check_out, adults, children từ query string.
     */
    public function create(Request $request)
    {
        if (!$request->has('room_ids') && $request->filled('room_id')) {
            $request->merge(['room_ids' => [$request->input('room_id')]]);
        }
        $request->validate([
            'room_ids' => 'required|array|min:1|max:25',
            'room_ids.*' => 'required|integer|distinct|exists:rooms,id',
            'check_in' => 'required|date_format:Y-m-d|after_or_equal:today',
            'check_out' => 'required|date_format:Y-m-d|after:check_in',
            'adults' => 'nullable|integer|min:1|max:100',
            'children' => 'nullable|integer|min:0|max:100',
        ]);
        $roomIds   = (array) $request->input('room_ids', []);
        $checkIn   = $request->input('check_in');
        $checkOut  = $request->input('check_out');
        $adults    = (int) $request->input('adults', 1);
        $children  = (int) $request->input('children', 0);

        if (empty($roomIds) || !$checkIn || !$checkOut) {
            return redirect()->route('rooms.index')
                ->with('error', 'Vui lòng chọn phòng và ngày trước khi đặt.');
        }

        $reservedRoomIds = Booking::reservedRoomIds($checkIn, $checkOut);
        $rooms = Room::with('roomType')
            ->whereIn('id', $roomIds)
            ->where('status', Room::STATUS_AVAILABLE)
            ->whereNotIn('id', $reservedRoomIds)
            ->get();

        if ($rooms->count() !== count($roomIds)) {
            return redirect()->route('rooms.index')
                ->with('error', 'Phòng bạn chọn không còn trống.');
        }

        $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
        $total  = 0;
        foreach ($rooms as $room) {
            $basePrice = (float) ($room->roomType?->price ?? 0);
            $total += PriceSetting::calculateTotalPrice($basePrice, $checkIn, $checkOut);
        }

        $totalMaxGuests = $rooms->sum(function ($room) {
            return $room->roomType->max_guests ?? 0;
        });
        $totalMaxAdults = $rooms->sum(fn ($room) => (int) ($room->roomType?->max_adults ?? 0));
        $totalMaxChildren = $rooms->sum(fn ($room) => (int) ($room->roomType?->max_children ?? 0));
        if ($adults > $totalMaxAdults || $children > $totalMaxChildren || $adults + $children > $totalMaxGuests) {
            return redirect()->route('rooms.index')->with('error', 'Các phòng đã chọn không đủ sức chứa cho cơ cấu khách này.');
        }

        $user = Auth::user();

        return view('booking.create', compact(
            'roomIds','rooms', 'checkIn', 'checkOut',
            'adults', 'children', 'nights', 'total', 'user', 'totalMaxGuests'
        ));
    }

    /**
     * Lưu booking mới vào database.
     */
    public function store(Request $request)
    {
        $earliestCheckIn = now('Asia/Ho_Chi_Minh')->hour >= 16
            ? now('Asia/Ho_Chi_Minh')->addDay()->toDateString()
            : now('Asia/Ho_Chi_Minh')->toDateString();
        $validated = $request->validate([
            'room_ids'       => 'required|array|min:1|max:25',
            'room_ids.*'     => 'integer|distinct|exists:rooms,id',
            'check_in'       => 'required|date_format:Y-m-d|after_or_equal:today',
            'check_out'      => 'required|date_format:Y-m-d|after:check_in',
            'adult_count'    => 'required|integer|min:1',
            'child_count'    => 'required|integer|min:0',
            'customer_name'  => 'required|string|max:200',
            'customer_email' => 'required|email|max:200',
            'customer_phone' => ['required', 'regex:/^0[0-9]{9}$/'],
        ]);

        if ($validated['check_in'] < $earliestCheckIn) {
            return back()->withInput()->withErrors([
                'check_in' => 'Từ 16:00, ngày nhận phòng sớm nhất là ngày mai. Giờ nhận phòng từ 12:00 đến trước 16:00.',
            ]);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $lockedRooms = Room::query()
                ->whereIn('id', $validated['room_ids'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedRooms->count() !== count($validated['room_ids'])) {
                return back()->withInput()->with('error', 'Một số phòng không còn trong hệ thống. Vui lòng chọn lại.');
            }

            // Serialize booking prices with admin room-type edits and use the locked database values.
            $roomTypes = \App\Models\RoomType::query()
                ->whereIn('id', $lockedRooms->pluck('room_type_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $lockedRooms->each(fn (Room $room) => $room->setRelation('roomType', $roomTypes->get($room->room_type_id)));

            // Lấy phòng và kiểm tra còn trống (loại trừ phòng đã có booking chưa huỷ trong cùng khoảng ngày)
            $bookedRoomIds = Booking::reservedRoomIds($validated['check_in'], $validated['check_out']);
            $rooms = $lockedRooms->filter(fn (Room $room) =>
                $room->status === Room::STATUS_AVAILABLE && ! $bookedRoomIds->contains($room->id)
            )->values();

            if ($rooms->count() !== count($validated['room_ids'])) {
                return back()->withInput()
                    ->with('error', 'Một số phòng vừa được đặt bởi người khác. Vui lòng chọn lại.');
            }

            $totalCapacity = $rooms->sum(fn ($room) => (int) ($room->roomType?->max_guests ?? 0));
            $adultCapacity = $rooms->sum(fn ($room) => (int) ($room->roomType?->max_adults ?? 0));
            $childCapacity = $rooms->sum(fn ($room) => (int) ($room->roomType?->max_children ?? 0));
            if ($adultCapacity < $validated['adult_count'] || $childCapacity < $validated['child_count']
                || $totalCapacity < ($validated['adult_count'] + $validated['child_count'])) {
                return back()->withInput()->with('error', 'Các phòng đã chọn không đủ sức chứa cho số khách.');
            }

            $total = 0;
            foreach ($rooms as $room) {
                $basePrice = (float) ($room->roomType?->price ?? 0);
                $total += PriceSetting::calculateTotalPrice($basePrice, $validated['check_in'], $validated['check_out']);
            }

            $booking = Booking::create([
                'user_id'        => Auth::id(),
                'customer_name'  => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'check_in'       => $validated['check_in'],
                'check_out'      => $validated['check_out'],
                'adult_count'    => $validated['adult_count'],
                'child_count'    => $validated['child_count'],
                'total_price'    => $total,
                'deposit_amount' => round($total * 0.5, 2),
                'payment_method' => null,
                'payment_status' => 'pending',
                'status'         => 'pending',
            ]);

            // Gán phòng vào booking_rooms pivot (chưa đánh dấu booked, chờ thanh toán xong)
            $booking->rooms()->attach($rooms->pluck('id'));

            return redirect()->route('payment.form', $booking->id);
        });
    }

    /**
     * Trang xác nhận đặt phòng thành công.
     */
    public function success(int $id)
    {
        $booking = Booking::with('rooms.roomType')->findOrFail($id);

        // Chỉ cho phép chủ booking xem
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        $checkin_token = in_array($booking->status, ['confirmed', 'checked_in'], true)
            ? app(\App\Services\CheckInTokenService::class)->qrPayload($booking)
            : null;

        return view('booking.success', compact('booking', 'checkin_token'));
    }

    /**
     * Danh sách đặt phòng của khách hàng đang đăng nhập.
     */
    public function myBookings()
    {
        $noShows = app(\App\Services\ExpireNoShowBookings::class);
        Booking::query()->where('user_id', Auth::id())->where('status', 'confirmed')
            ->whereDate('check_in', '<=', now('Asia/Ho_Chi_Minh')->toDateString())
            ->pluck('id')->each(fn ($id) => $noShows->cancelIfDue((int) $id));

        $bookings = Booking::with(['rooms.roomType', 'stayExtensionLogs'])
            ->where('user_id', Auth::id())
            ->orderByDesc((new Booking)->getCreatedAtColumn())
            ->paginate(10);

        $bookingCounts = Booking::where('user_id', Auth::id())
            ->selectRaw(
                'COUNT(*) AS all_count,
                 COALESCE(SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END), 0) AS upcoming_count,
                 COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS completed_count,
                 COALESCE(SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END), 0) AS cancelled_count',
                ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'rejected']
            )->first();

        $checkinTokens = $bookings->getCollection()
            ->filter(fn (Booking $booking) => in_array($booking->status, ['confirmed', 'checked_in'], true))
            ->mapWithKeys(fn (Booking $booking) => [
                $booking->id => app(\App\Services\CheckInTokenService::class)->qrPayload($booking),
            ]);

        return view('booking.my_bookings', compact('bookings', 'bookingCounts', 'checkinTokens'));
    }

    public function extendCustomerStay(Request $request, Booking $booking)
    {
        if ((int) $booking->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'booking_id' => ['required', 'integer', 'in:'.$booking->id],
            'mode' => ['required', 'in:hours,days'],
            'amount' => ['required', 'integer', 'min:1'],
        ], [
            'mode.required' => 'Vui lòng chọn hình thức gia hạn.',
            'mode.in' => 'Hình thức gia hạn không hợp lệ.',
            'amount.required' => 'Vui lòng nhập thời lượng muốn gia hạn.',
            'amount.integer' => 'Thời lượng gia hạn phải là số nguyên.',
            'amount.min' => 'Thời lượng gia hạn tối thiểu là 1.',
        ]);
        $maxAmount = $validated['mode'] === 'hours' ? 12 : 30;
        if ((int) $validated['amount'] > $maxAmount) {
            return back()->withInput($validated)->withErrors([
                'amount' => 'Gia hạn theo '.($validated['mode'] === 'hours' ? 'giờ' : 'ngày')." tối đa {$maxAmount}.",
            ]);
        }

        try {
            $result = app(\App\Services\StayExtensionService::class)->extend(
                $booking->id, $validated['mode'], (int) $validated['amount']
            );
        } catch (\DomainException $e) {
            return back()->withInput($validated)->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', $result['message'].' Chi phí bổ sung: '.number_format($result['added_amount'], 0, ',', '.').'đ.');
    }

    /** An in-house customer may request room cleaning for a room in their active stay. */
    public function toggleCleaningRequest(Request $request, Booking $booking, Room $room)
    {
        $validated = $request->validate(['needs_cleaning' => ['required', 'boolean']]);

        $updated = DB::transaction(function () use ($booking, $room, $validated) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);

            abort_unless((int) $booking->user_id === (int) Auth::id(), 403);
            abort_unless(
                $booking->status === 'checked_in'
                    && $room->status === Room::STATUS_OCCUPIED
                    && $booking->rooms()->whereKey($room->id)->exists(),
                422,
                'Chỉ có thể yêu cầu dọn phòng đang lưu trú.'
            );

            $needsCleaning = (bool) $validated['needs_cleaning'];
            $room->update([
                'needs_cleaning' => $needsCleaning,
                'cleaning_requested_at' => $needsCleaning ? now() : null,
            ]);

            return $needsCleaning;
        });

        return response()->json([
            'success' => true,
            'needs_cleaning' => $updated,
            'message' => $updated ? 'Đã gửi yêu cầu dọn phòng.' : 'Đã hủy yêu cầu dọn phòng.',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // STAFF (receptionist / admin)
    // ──────────────────────────────────────────────────────────

    /**
     * Danh sách tất cả booking cho lễ tân / admin.
     */
    public function staffIndex(Request $request)
    {
        $status = $request->input('status');

        $query = Booking::with(['rooms.roomType', 'user'])
            ->orderByDesc((new Booking)->getCreatedAtColumn());

        if ($status) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(20);

        return view('staff.bookings', compact('bookings', 'status'));
    }

    /**
     * Lễ tân xác nhận booking (pending → confirmed).
     */
    public function confirm(int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($id);
            if (! $booking->isPending()) {
                return ['error' => 'Chỉ có thể xác nhận booking đang chờ.'];
            }
            if ($booking->payment_status === 'pending' && $booking->paymentHoldExpired()) {
                return ['error' => 'Thời hạn giữ phòng đã hết. Khách cần đặt lại phòng.'];
            }

            $booking->update(['status' => 'confirmed']);

            return ['booking' => $booking];
        });
        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }
        $booking = $result['booking'];

        return back()->with('success', "Đã xác nhận booking #{$booking->id}.");
    }

    /**
     * Ghi nhận khách check-in (confirmed → occupied).
     */
    public function checkIn(int $id)
    {
        try {
            $booking = app(\App\Services\BookingTransitionService::class)->checkIn($id);
            return back()->with('success', "Check-in thành công cho booking #{$booking->id}.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Ghi nhận khách check-out (occupied → completed).
     */
    public function checkOut(int $id)
    {
        return app(PaymentController::class)->staffCheckoutPayment(request(), $id);
    }
    /**
     * Lấy booking hiện tại đang occupied của 1 phòng (AJAX).
     */
    public function currentBooking(int $roomId)
    {
        $booking = Booking::with('rooms')
            ->whereHas('rooms', fn($q) => $q->where('rooms.id', $roomId))
            ->where('status', 'checked_in')
            ->latest('check_out')
            ->first();

        return response()->json(['booking' => $booking]);
    }

    /**
     * Lễ tân check-in phòng qua AJAX.
     * Tìm booking confirmed của phòng → chuyển sang occupied.
     */
    public function checkInRoom(Request $request, int $roomId)
    {
        $room = Room::findOrFail($roomId);

        if ($room->status === Room::STATUS_CLEANING) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng đang dọn, phải bấm "Đã dọn xong" trước khi check-in.',
            ], 422);
        }

        if ($room->status === Room::STATUS_MAINTENANCE) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng đang bảo trì, không thể check-in.',
            ], 422);
        }

        if ($room->status === Room::STATUS_OCCUPIED) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng đang có khách ở, không thể check-in thêm.',
            ], 422);
        }

        // Hỗ trợ check-in khách vãng lai
        if ($request->input('is_walkin')) {
            $request->merge(['room_ids' => [$roomId]]);
            return app(ReceptionController::class)->walkinCheckin($request);
        }

        $booking = Booking::whereHas('rooms', fn($q) => $q->where('rooms.id', $roomId))
            ->whereDate('check_in', now()->toDateString())
            ->where('status', 'confirmed')
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy booking confirmed cho phòng này.']);
        }

        if ($room->status !== Room::STATUS_AVAILABLE) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng chưa sẵn sàng để check-in booking này.',
            ], 422);
        }

        try {
            app(\App\Services\BookingTransitionService::class)->checkIn($booking->id);
            return response()->json(['success' => true, 'message' => "Check-in phòng {$room->room_number} thành công."]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Lễ tân checkout phòng qua AJAX + ghi nhận thanh toán còn lại.
     */
    public function checkOutRoom(Request $request, int $bookingId)
    {
        return app(PaymentController::class)->staffCheckoutPayment($request, $bookingId);
    }

    /**
     * Gia hạn lưu trú cho booking đang ở.
     * Kiểm tra các phòng thuộc booking có bị trùng lịch trong khoảng ngày gia hạn không.
     */
    public function extendStay(Request $request, int $bookingId)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:30',
        ]);

        try {
            $result = app(\App\Services\StayExtensionService::class)->extend($bookingId, 'days', (int) $validated['days']);
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'booking' => $result['booking']->load('rooms.roomType'),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }
    }

    /**
     * Cập nhật trạng thái phòng thủ công (available, cleaning, maintenance).
     */
    public function updateRoomStatus(Request $request, int $roomId)
    {
        $validated = $request->validate(['status' => ['required', 'in:available,cleaning,maintenance']]);
        $newStatus = $validated['status'];

        $allowed = ['available', 'cleaning', 'maintenance'];
        if (!in_array($newStatus, $allowed)) {
            return response()->json(['success' => false, 'message' => 'Trạng thái không hợp lệ.']);
        }

        $room = DB::transaction(function () use ($roomId, $newStatus) {
            $room = Room::query()->lockForUpdate()->findOrFail($roomId);
            if ($room->status === Room::STATUS_OCCUPIED) {
                abort(422, 'Vui lòng hoàn tất thanh toán trả phòng trước.');
            }
            if ($newStatus === Room::STATUS_AVAILABLE && $room->status !== Room::STATUS_CLEANING) {
                abort(422, 'Chỉ phòng đang dọn mới được chuyển sang đang trống.');
            }

            $room->update([
                'status' => $newStatus,
                'needs_cleaning' => $newStatus === Room::STATUS_CLEANING,
                'cleaning_requested_at' => $newStatus === Room::STATUS_CLEANING ? now() : null,
            ]);

            return $room;
        });

        $statusText = match($newStatus) {
            'available'   => 'Đang trống',
            'cleaning'    => 'Đang dọn',
            'maintenance' => 'Bảo trì',
        };

        return response()->json(['success' => true, 'message' => "Phòng {$room->room_number} → {$statusText}."]);
    }

}
