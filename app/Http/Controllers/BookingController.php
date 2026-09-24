<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $roomIds   = (array) $request->input('room_ids', []);
        $checkIn   = $request->input('check_in');
        $checkOut  = $request->input('check_out');
        $adults    = (int) $request->input('adults', 1);
        $children  = (int) $request->input('children', 0);

        if (empty($roomIds) || !$checkIn || !$checkOut) {
            return redirect()->route('rooms.index')
                ->with('error', 'Vui lòng chọn phòng và ngày trước khi đặt.');
        }

        $rooms = Room::with('roomType')
            ->whereIn('id', $roomIds)
            ->where('status', Room::STATUS_AVAILABLE)
            ->get();

        if ($rooms->isEmpty()) {
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
        $validated = $request->validate([
            'room_ids'       => 'required|array|min:1',
            'room_ids.*'     => 'integer|exists:rooms,id',
            'check_in'       => 'required|date|after_or_equal:today',
            'check_out'      => 'required|date|after:check_in',
            'adult_count'    => 'required|integer|min:1',
            'child_count'    => 'required|integer|min:0',
            'customer_name'  => 'required|string|max:200',
            'customer_email' => 'required|email|max:200',
            'customer_phone' => 'required|string|max:20',
        ]);

        // Lấy phòng và kiểm tra còn trống (loại trừ phòng đã có booking chưa huỷ trong cùng khoảng ngày)
        $bookedRoomIds = \DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->where('bookings.payment_status', 'paid')
            ->where('bookings.status', '!=', 'cancelled')
            ->where('bookings.check_in', '<', $validated['check_out'])
            ->where('bookings.check_out', '>', $validated['check_in'])
            ->pluck('booking_rooms.room_id');

        $rooms = Room::whereIn('id', $validated['room_ids'])
            ->whereNotIn('id', $bookedRoomIds)
            ->get();

        if ($rooms->count() !== count($validated['room_ids'])) {
            return back()->withInput()
                ->with('error', 'Một số phòng vừa được đặt bởi người khác. Vui lòng chọn lại.');
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

        return view('booking.success', compact('booking'));
    }

    /**
     * Danh sách đặt phòng của khách hàng đang đăng nhập.
     */
    public function myBookings()
    {
        $bookings = Booking::with('rooms.roomType')
            ->where('user_id', Auth::id())
            ->where('payment_status', 'paid') // chỉ lấy đã thanh toán
            ->orderByDesc((new Booking)->getCreatedAtColumn())
            ->get();

        return view('booking.my_bookings', compact('bookings'));
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
        $booking = Booking::findOrFail($id);

        if (!$booking->isPending()) {
            return back()->with('error', 'Chỉ có thể xác nhận booking đang chờ.');
        }

        $booking->update(['status' => 'confirmed']);

        return back()->with('success', "Đã xác nhận booking #{$booking->id}.");
    }

    /**
     * Ghi nhận khách check-in (confirmed → occupied).
     */
    public function checkIn(int $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Booking phải ở trạng thái "đã xác nhận" mới có thể check-in.');
        }

        $booking->update([
            'status'           => 'checked_in',
            'actual_check_in'  => now(),
        ]);

        // Đánh dấu phòng là đang có khách
        foreach ($booking->rooms as $room) {
            $room->update(['status' => Room::STATUS_OCCUPIED]);
        }

        return back()->with('success', "Check-in thành công cho booking #{$booking->id}.");
    }

    /**
     * Ghi nhận khách check-out (occupied → completed).
     */
    public function checkOut(int $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Booking phải ở trạng thái "đang ở" mới có thể check-out.');
        }

        $booking->update([
            'status'            => 'completed',
            'actual_check_out'  => now(),
        ]);

        // Trả phòng về available (qua dọn dẹp)
        foreach ($booking->rooms as $room) {
            $room->update(['status' => Room::STATUS_CLEANING]);
        }

        return back()->with('success', "Check-out thành công cho booking #{$booking->id}. Phòng đã chuyển sang trạng thái dọn dẹp.");
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
            if ($room->status !== Room::STATUS_AVAILABLE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ phòng đang trống mới check-in khách vãng lai được.',
                ], 422);
            }

            $walkinType = $request->input('walkin_type', 'now');
            $isHold = $walkinType === 'hold';
            $checkOutDate = $request->input('check_out', now()->addDay()->format('Y-m-d'));
            $basePrice = (float) ($room->roomType?->price ?? 0);
            $totalPrice = PriceSetting::calculateTotalPrice($basePrice, now()->format('Y-m-d'), $checkOutDate);

            $conflict = Booking::whereHas('rooms', fn ($query) => $query->where('rooms.id', $room->id))
                ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                ->whereDate('check_in', '<', $checkOutDate)
                ->whereDate('check_out', '>', now()->toDateString())
                ->first();

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo booking vì phòng này đã có lịch trong khoảng ngày đã chọn.',
                ], 409);
            }

            // Lấy ID tài khoản người dùng đăng nhập hiện tại nếu có
            $userId = session('user_id');

            $booking = Booking::create([
                'user_id'         => $userId,
                'customer_name'   => $request->input('customer_name'),
                'customer_email'  => $request->input('customer_email'),
                'customer_phone'  => $request->input('customer_phone'),
                'check_in'        => now()->format('Y-m-d'),
                'check_out'       => $checkOutDate,
                'actual_check_in' => now(),
                'adult_count'     => (int) $request->input('adult_count', 1),
                'child_count'     => (int) $request->input('child_count', 0),
                'total_price'     => $totalPrice,
                'payment_status'  => 'pending',
                'status'          => $isHold ? 'confirmed' : 'checked_in',
            ]);

            $booking->rooms()->attach($room->id);
            if (!$isHold) {
                $room->update(['status' => Room::STATUS_OCCUPIED]);
            }

            $message = $isHold
                ? "Giữ chỗ phòng {$room->room_number} thành công."
                : "Check-in khách vãng lai thành công cho phòng {$room->room_number}.";

            return response()->json(['success' => true, 'message' => $message]);
        }

        $booking = Booking::whereHas('rooms', fn($q) => $q->where('rooms.id', $roomId))
            ->whereDate('check_in', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
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

        $booking->update([
            'status'          => 'checked_in',
            'actual_check_in' => now(),
        ]);

        $room->update(['status' => Room::STATUS_OCCUPIED]);

        return response()->json(['success' => true, 'message' => "Check-in phòng {$room->room_number} thành công."]);
    }

    /**
     * Lễ tân checkout phòng qua AJAX + ghi nhận thanh toán còn lại.
     */
    public function checkOutRoom(Request $request, int $bookingId)
    {
        $booking = Booking::with('rooms')->findOrFail($bookingId);

        if (!in_array($booking->status, ['occupied', 'checked_in'])) {
            return response()->json(['success' => false, 'message' => 'Booking không ở trạng thái occupied.']);
        }

        $booking->update([
            'status'           => 'completed',
            'actual_check_out' => now(),
            'payment_status'   => 'paid',
            'payment_method'   => $request->input('payment_method', 'cash'),
        ]);

        foreach ($booking->rooms as $room) {
            $room->update(['status' => Room::STATUS_CLEANING]);
        }

        return response()->json(['success' => true, 'message' => "Check-out booking #{$booking->id} thành công. Phòng đang dọn dẹp."]);
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

        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);

        if (!in_array($booking->status, ['checked_in', 'occupied'])) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể gia hạn booking đang ở.',
            ], 422);
        }

        $currentCheckOut = Carbon::parse($booking->check_out)->startOfDay();
        $newCheckOut = $currentCheckOut->copy()->addDays((int) $validated['days']);
        $roomIds = $booking->rooms->pluck('id');

        $conflict = Booking::query()
            ->with('rooms:id,room_number')
            ->where('id', '!=', $booking->id)
            ->whereHas('rooms', fn ($query) => $query->whereIn('rooms.id', $roomIds))
            ->whereIn('status', ['pending', 'confirmed', 'checked_in', 'occupied'])
            ->whereDate('check_in', '<', $newCheckOut->toDateString())
            ->whereDate('check_out', '>', $currentCheckOut->toDateString())
            ->first();

        if ($conflict) {
            $roomNumbers = $conflict->rooms->pluck('room_number')->join(', ');
            return response()->json([
                'success' => false,
                'message' => 'Không thể gia hạn vì phòng ' . $roomNumbers . ' đã có lịch từ '
                    . Carbon::parse($conflict->check_in)->format('d/m/Y') . ' đến '
                    . Carbon::parse($conflict->check_out)->format('d/m/Y') . '.',
            ], 409);
        }

        $extraTotal = 0;
        foreach ($booking->rooms as $room) {
            $basePrice = (float) ($room->roomType?->price ?? 0);
            $extraTotal += PriceSetting::calculateTotalPrice(
                $basePrice,
                $currentCheckOut->toDateString(),
                $newCheckOut->toDateString()
            );
        }
        $booking->update([
            'check_out' => $newCheckOut->toDateString(),
            'total_price' => (float) $booking->total_price + $extraTotal,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gia hạn lưu trú thành công đến ' . $newCheckOut->format('d/m/Y') . '.',
            'booking' => $booking->fresh('rooms.roomType'),
        ]);
    }

    /**
     * Cập nhật trạng thái phòng thủ công (available, cleaning, maintenance).
     */
    public function updateRoomStatus(Request $request, int $roomId)
    {
        $room = Room::findOrFail($roomId);
        $newStatus = $request->input('status');

        $allowed = ['available', 'cleaning', 'maintenance'];
        if (!in_array($newStatus, $allowed)) {
            return response()->json(['success' => false, 'message' => 'Trạng thái không hợp lệ.']);
        }

        if ($newStatus === Room::STATUS_AVAILABLE && $room->status !== Room::STATUS_CLEANING) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ phòng đang dọn mới được chuyển sang đang trống bằng nút Đã dọn xong.',
            ], 422);
        }

        $room->update(['status' => $newStatus]);

        $statusText = match($newStatus) {
            'available'   => 'Đang trống',
            'cleaning'    => 'Đang dọn',
            'maintenance' => 'Bảo trì',
        };

        return response()->json(['success' => true, 'message' => "Phòng {$room->room_number} → {$statusText}."]);
    }
}
