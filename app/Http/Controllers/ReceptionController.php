<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\Booking;
use App\Models\PriceSetting;

class ReceptionController extends Controller
{
    /**
     * Sơ đồ phòng lễ tân.
     */
    public function index(Request $request)
    {
        app(\App\Services\ExpireNoShowBookings::class)->cancelDueBookings();
        $today = now('Asia/Ho_Chi_Minh')->toDateString();
        // Lấy toàn bộ phòng với trạng thái logic tính toán động
        $sql = "
            SELECT r.id, r.room_number, r.floor, r.status, r.needs_cleaning, r.cleaning_requested_at,
                   rt.type_name, rt.max_guests, rt.max_adults, rt.max_children, rt.price, rt.image, rt.id as room_type_id,
                   (SELECT COUNT(*) 
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                   ) as has_today_booking,
                   (SELECT COUNT(*)
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                   ) as has_active_booking,
                   (SELECT COUNT(*) 
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_out = ?
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                   ) as is_checkout_today,
                   (SELECT b.customer_name 
                    FROM booking_rooms br 
                    JOIN bookings b ON b.id = br.booking_id 
                    WHERE br.room_id = r.id 
                      AND (
                          (b.status = 'checked_in' AND r.status IN ('occupied', 'overdue'))
                          OR (DATE(b.check_in) = ? AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)))))
                      )
                    ORDER BY (CASE WHEN b.status = 'checked_in' THEN 1 ELSE 2 END) ASC, b.id DESC
                    LIMIT 1
                   ) as customer_name,
                   (SELECT b.customer_phone 
                    FROM booking_rooms br 
                    JOIN bookings b ON b.id = br.booking_id 
                    WHERE br.room_id = r.id 
                      AND (
                          (b.status = 'checked_in' AND r.status IN ('occupied', 'overdue'))
                          OR (DATE(b.check_in) = ? AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)))))
                      )
                    ORDER BY (CASE WHEN b.status = 'checked_in' THEN 1 ELSE 2 END) ASC, b.id DESC
                    LIMIT 1
                   ) as customer_phone,
                   (SELECT b.id
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_booking_id,
                   (SELECT b.customer_email
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_customer_email,
                   (SELECT b.check_in
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_check_in,
                   (SELECT b.check_out
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_check_out,
                   (SELECT b.adult_count
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_adult_count,
                   (SELECT b.child_count
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_child_count,
                   (SELECT b.total_price
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_total_price,
                   (SELECT COALESCE(SUM(pl.amount), CASE WHEN b.payment_status = 'paid' THEN b.deposit_amount ELSE 0 END)
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    LEFT JOIN payment_logs pl ON pl.booking_id = b.id AND pl.status = 'success'
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status IN ('occupied', 'overdue')
                    GROUP BY b.id, b.deposit_amount, b.payment_status
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_paid_amount,
                    (SELECT SUM(rt.price)
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    JOIN rooms r2 ON r2.id = br.room_id
                    JOIN room_types rt ON rt.id = r2.room_type_id
                    WHERE b.id = (
                        SELECT b2.id FROM booking_rooms br2
                        JOIN bookings b2 ON b2.id = br2.booking_id
                        WHERE br2.room_id = r.id
                          AND b2.status = 'checked_in'
                          AND r.status IN ('occupied', 'overdue')
                        ORDER BY b2.check_out DESC LIMIT 1
                    )
                   ) as active_room_price_per_night,
                   (SELECT COUNT(*)
                    FROM booking_rooms br2
                    WHERE br2.booking_id = (
                        SELECT b.id
                        FROM booking_rooms br
                        JOIN bookings b ON b.id = br.booking_id
                        WHERE br.room_id = r.id
                          AND b.status = 'checked_in'
                          AND r.status IN ('occupied', 'overdue')
                        LIMIT 1
                    )
                   ) as active_booking_room_count,
                   (SELECT COUNT(*)
                    FROM booking_rooms br2
                    JOIN rooms r2 ON r2.id = br2.room_id
                    WHERE r2.status IN ('occupied', 'overdue')
                      AND br2.booking_id = (
                        SELECT b.id
                        FROM booking_rooms br
                        JOIN bookings b ON b.id = br.booking_id
                        WHERE br.room_id = r.id
                          AND b.status = 'checked_in'
                          AND r.status IN ('occupied', 'overdue')
                        LIMIT 1
                    )
                   ) as active_booking_occupied_room_count,
                   (SELECT b.id
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_booking_id,
                   (SELECT b.customer_name
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_customer_name,
                   (SELECT b.customer_phone
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_customer_phone,
                   (SELECT b.customer_email
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_customer_email,
                   (SELECT b.check_in
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_check_in,
                   (SELECT b.check_out
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_check_out,
                   (SELECT b.adult_count
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_adult_count,
                   (SELECT b.child_count
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_child_count,
                   (SELECT b.total_price
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_total_price,
                   (SELECT b.deposit_amount
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND DATE(b.check_in) = ?
                      AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE))))
                    LIMIT 1
                   ) as today_deposit_amount
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            ORDER BY r.floor DESC, r.room_number ASC
        ";

        $sql = str_replace('b.updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)', $this->pendingHoldExpirySql(), $sql);
        $roomsRaw = DB::select($sql, array_fill(0, substr_count($sql, '?'), $today));

        $activeCheckoutDates = [];
        foreach ($roomsRaw as $roomRow) {
            if (! empty($roomRow->active_booking_id) && ! empty($roomRow->active_check_out)) {
                $activeCheckoutDates[(int) $roomRow->active_booking_id] = Carbon::parse($roomRow->active_check_out)->toDateString();
            }
        }
        $hourlyCheckoutAt = [];
        if ($activeCheckoutDates !== []) {
            $extensionLogs = DB::table('payment_logs')
                ->whereIn('booking_id', array_keys($activeCheckoutDates))
                ->where('purpose', 'stay_extension')
                ->where('status', 'pending')
                ->orderBy('id')
                ->get(['booking_id', 'raw_response']);

            foreach ($extensionLogs as $log) {
                $metadata = is_array($log->raw_response)
                    ? $log->raw_response
                    : json_decode($log->raw_response ?? '[]', true);
                $bookingId = (int) $log->booking_id;
                if (is_array($metadata)
                    && ($metadata['mode'] ?? null) === 'hours'
                    && ($metadata['checkout_date'] ?? null) === ($activeCheckoutDates[$bookingId] ?? null)
                    && ! empty($metadata['due_at'])) {
                    $hourlyCheckoutAt[$bookingId] = $metadata['due_at'];
                }
            }
        }

        // Phân nhóm phòng theo tầng
        $floors = [];
        foreach ($roomsRaw as $r) {
            $rArray = (array) $r;
            $activeBookingId = (int) ($rArray['active_booking_id'] ?? 0);
            if ($activeBookingId > 0) {
                $rArray['active_scheduled_checkout_at'] = $hourlyCheckoutAt[$activeBookingId]
                    ?? Carbon::parse($rArray['active_check_out'], 'Asia/Ho_Chi_Minh')->setTime(12, 0)->toIso8601String();
            }
            // Lấy danh sách tiện ích
            $amenitiesSql = "
                SELECT a.amenity_name 
                FROM amenities a
                JOIN room_type_amenities rta ON a.id = rta.amenity_id
                WHERE rta.room_type_id = ?
            ";
            $amenities = array_column(DB::select($amenitiesSql, [$rArray['room_type_id']]), 'amenity_name');
            $rArray['needs_cleaning'] = (bool) $rArray['needs_cleaning'];
            $rArray['amenities_list'] = implode(', ', $amenities);
            $roomImages = Storage::disk('public')->files('room-galleries/'.$rArray['id']);
            sort($roomImages, SORT_STRING);
            $storedImage = trim((string) ($rArray['image'] ?? ''));
            $rArray['image_url'] = $roomImages !== []
                ? Storage::disk('public')->url($roomImages[0])
                : (preg_match('/^https?:\/\//i', $storedImage)
                    ? $storedImage
                    : ($storedImage !== '' ? asset(ltrim($storedImage, '/')) : asset('images/rooms/default.jpg')));

            $floors[$rArray['floor']][] = $rArray;
        }

        // Build once for the board so each action can match its assigned capability.
        $permissions = $this->currentInternalPermissions();
        $pendingRefunds = $permissions['tn_refund']
            ? \App\Models\Booking::where('status', 'cancelled')->where('refund_status', 'eligible')->count()
            : 0;

        return view('staff.bookings', compact('floors', 'pendingRefunds', 'permissions'));
    }

    /**
     * Cập nhật trạng thái phòng (Check-in booking hoặc Checkout phòng/booking).
     */
    public function updateStatus(Request $request)
    {
        $roomId = filter_var($request->input('room_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($roomId === false) {
            return response()->json(['success' => false, 'message' => 'Phòng không hợp lệ.'], 400);
        }
        $status = $request->input('status');
        $checkoutScope = $request->input('checkout_scope');

        $validStatuses = ['available', 'occupied', 'cleaning'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['success' => false, 'message' => 'Trạng thái không hợp lệ: ' . $status], 400);
        }
        if (!in_array($checkoutScope, [null, 'room'], true)) {
            return response()->json(['success' => false, 'message' => 'Phạm vi trả phòng không hợp lệ.'], 400);
        }

        $isPartialCheckout = $status === 'cleaning' && $checkoutScope === 'room';

        if ($status === 'occupied') {
            if (! $this->currentInternalUserHasPermission('bp_checkin_checkout')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản chưa được cấp quyền nhận phòng.',
                ], 403);
            }
        } elseif ($isPartialCheckout) {
            if (! $this->currentInternalUserHasPermission('bp_checkin_checkout')
                || ! $this->currentInternalUserHasPermission('tn_collect_payment')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản chưa được cấp quyền trả phòng và xử lý thanh toán.',
                ], 403);
            }
        } elseif (! $this->currentInternalUserHasPermission('bp_clean_status')) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được cấp quyền cập nhật trạng thái phòng.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            if ($isPartialCheckout) {
                $booking = Booking::query()
                    ->where('status', 'checked_in')
                    ->whereHas('rooms', fn ($query) => $query->where('rooms.id', $roomId))
                    ->latest('check_out')
                    ->lockForUpdate()
                    ->first();

                if (! $booking) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Không tìm thấy booking đang lưu trú cho phòng này.'], 404);
                }

                $roomIds = $booking->rooms()->pluck('rooms.id');
                $bookingRooms = \App\Models\Room::query()
                    ->whereIn('id', $roomIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $currentRoom = $bookingRooms->firstWhere('id', $roomId);
                $occupiedCount = $bookingRooms->whereIn('status', [Room::STATUS_OCCUPIED, Room::STATUS_OVERDUE])->count();

                if (! $currentRoom || ! in_array($currentRoom->status, [Room::STATUS_OCCUPIED, Room::STATUS_OVERDUE], true)) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Phòng không còn ở trạng thái đang lưu trú. Vui lòng tải lại.'], 409);
                }
                if ($occupiedCount <= 1) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Phòng cuối cùng cần hoàn tất thanh toán và trả toàn bộ booking.'], 422);
                }

                $currentRoom->update([
                    'status' => Room::STATUS_CLEANING,
                    'needs_cleaning' => true,
                    'cleaning_requested_at' => now(),
                ]);
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Đã trả phòng {$currentRoom->room_number}; các phòng còn lại của booking vẫn đang lưu trú.",
                    'new_status' => Room::STATUS_CLEANING,
                ]);
            }

            $currentRoom = \App\Models\Room::lockForUpdate()->findOrFail($roomId);
            if (($status === 'available' && $currentRoom->status !== 'cleaning') || ($status === 'occupied' && $currentRoom->status !== 'available')) {
                DB::rollBack();
                return response()->json(['success'=>false, 'message'=>'Trạng thái phòng đã thay đổi. Vui lòng tải lại.'], 409);
            }
            if ($status === 'cleaning' && $currentRoom->status === 'occupied') {
                DB::rollBack();
                return response()->json(['success'=>false, 'message'=>'Vui lòng hoàn tất bước thanh toán trả phòng trước khi chuyển sang dọn dẹp.'], 422);
            }


            if ($status === 'occupied') {
                $booking = \App\Models\Booking::query()
                    ->whereHas('rooms', fn ($query) => $query->where('rooms.id', $roomId))
                    ->whereDate('check_in', now('Asia/Ho_Chi_Minh')->toDateString())
                    ->where('status', 'confirmed')
                    ->first(['id']);

                if (!$booking) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Không tìm thấy lịch đặt hôm nay để check-in phòng này.'], 404);
                }

                DB::rollBack();
                try {
                    app(\App\Services\BookingTransitionService::class)->checkIn((int) $booking->id);
                    return response()->json(['success' => true, 'message' => 'Nhận phòng thành công!', 'new_status' => 'occupied']);
                } catch (\DomainException $e) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
                }

            } elseif ($status === 'available') {
                // Đã dọn xong: Cập nhật trực tiếp từ cleaning sang available
                DB::update("UPDATE rooms SET status = 'available', needs_cleaning = 0, cleaning_requested_at = NULL WHERE id = ?", [$roomId]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật trạng thái phòng Đang trống thành công!',
                    'new_status' => 'available'
                ]);

            } elseif ($status === 'cleaning') {
                DB::update("UPDATE rooms SET status = 'cleaning', needs_cleaning = 1, cleaning_requested_at = CURRENT_TIMESTAMP WHERE id = ?", [$roomId]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Trả phòng thành công! Phòng đã chuyển sang trạng thái dọn dẹp.',
                    'new_status' => 'cleaning'
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Không thể xử lý lúc này. Vui lòng thử lại.'], 500);
        }
    }

    public function cleaningNotifications()
    {
        $rooms = Room::query()
            ->select(['id', 'room_number', 'floor', 'status', 'cleaning_requested_at'])
            ->where('needs_cleaning', true)
            ->orderByRaw('cleaning_requested_at IS NULL')
            ->orderBy('cleaning_requested_at')
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'source' => $room->status === Room::STATUS_OCCUPIED ? 'Khách yêu cầu' : 'Khách đã trả phòng',
                'requested_at' => optional($room->cleaning_requested_at)->toIso8601String(),
            ]);

        return response()->json(['count' => $rooms->count(), 'rooms' => $rooms])
            ->header('Cache-Control', 'private, no-store');
    }

    public function completeCleaningRequest(int $roomId)
    {
        $room = DB::transaction(function () use ($roomId) {
            $room = Room::query()->lockForUpdate()->findOrFail($roomId);
            abort_unless(
                $room->status === Room::STATUS_OCCUPIED && $room->needs_cleaning,
                409,
                'Yêu cầu dọn phòng đã được xử lý hoặc phòng không còn khách.'
            );

            $room->update(['needs_cleaning' => false, 'cleaning_requested_at' => null]);
            return $room;
        });

        return response()->json([
            'success' => true,
            'status' => $room->status,
            'message' => "Đã hoàn tất yêu cầu dọn phòng {$room->room_number}.",
        ]);
    }

    /**
     * Check-in khách vãng lai vào phòng đang trống.
     */
    public function walkinCheckin(Request $request)
    {
        if ($request->input('walkin_type') === 'now'
            && ! $this->currentInternalUserHasPermission('bp_checkin_checkout')) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được cấp quyền nhận phòng.',
            ], 403);
        }

        $data = $request->validate([
            'room_ids' => 'required|array|min:1|max:25',
            'room_ids.*' => 'required|integer|distinct|exists:rooms,id',
            'customer_name' => 'required|string|max:200',
            'customer_phone' => ['required', 'regex:/^0[0-9]{9}$/'],
            'customer_email' => 'nullable|email|max:200',
            'walkin_type' => 'required|in:now,hold',
            'adult_count' => 'required|integer|min:1|max:100',
            'child_count' => 'required|integer|min:0|max:100',
            'check_out' => 'required|date_format:Y-m-d|after:today',
        ]);
        return DB::transaction(function () use ($data) {
            $rooms = \App\Models\Room::with('roomType')->whereIn('id', $data['room_ids'])->orderBy('id')->lockForUpdate()->get();
            if ($rooms->count() !== count($data['room_ids']) || $rooms->contains(fn ($room) => $room->status !== 'available')) {
                return response()->json(['success'=>false, 'message'=>'Phòng chưa sẵn sàng. Vui lòng chọn phòng đang trống.'], 409);
            }
            if ($rooms->sum(fn ($room) => (int) $room->roomType->max_guests) < $data['adult_count'] + $data['child_count']) {
                return response()->json(['success'=>false, 'message'=>'Các phòng đã chọn không đủ sức chứa cho số khách.'], 422);
            }
            if ($rooms->sum(fn ($room) => (int) $room->roomType->max_adults) < $data['adult_count']
                || $rooms->sum(fn ($room) => (int) $room->roomType->max_children) < $data['child_count']) {
                return response()->json(['success'=>false, 'message'=>'Các phòng đã chọn không phù hợp với cơ cấu người lớn và trẻ em.'], 422);
            }
            $reserved = \App\Models\Booking::reservedRoomIds(now()->toDateString(), $data['check_out']);
            $conflict = $rooms->pluck('id')->intersect($reserved)->isNotEmpty();
            if ($conflict) return response()->json(['success'=>false, 'message'=>'Phòng đã có lịch đặt trong thời gian này. Vui lòng chọn lại.'], 409);
            $total = $rooms->sum(fn ($room) => PriceSetting::calculateTotalPrice((float) $room->roomType->price, now()->toDateString(), $data['check_out']));
            $isImmediateCheckin = $data['walkin_type'] === 'now';
            $booking = \App\Models\Booking::create([
                'user_id'=>null, 'customer_name'=>$data['customer_name'],
                'customer_phone'=>$data['customer_phone'], 'customer_email'=>$data['customer_email'] ?? '',
                'check_in'=>now()->toDateString(), 'check_out'=>$data['check_out'],
                'actual_check_in'=>$isImmediateCheckin ? now() : null,
                'adult_count'=>$data['adult_count'], 'child_count'=>$data['child_count'],
                'total_price'=>$total, 'deposit_amount'=>0, 'payment_method'=>'cash', 'payment_status'=>'pending',
                'status'=>$isImmediateCheckin ? 'checked_in' : 'confirmed',
            ]);
            $booking->rooms()->attach($rooms->pluck('id'));
            if ($isImmediateCheckin) {
                \App\Models\Room::whereIn('id', $rooms->pluck('id'))->update([
                    'status' => Room::STATUS_OCCUPIED,
                    'needs_cleaning' => false,
                    'cleaning_requested_at' => null,
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => $isImmediateCheckin ? 'Nhận phòng thành công.' : 'Đã giữ booking; phòng vẫn được hiển thị theo trạng thái vật lý hiện tại.',
            ]);
        });
    }

    /**
     * Gia hạn ngày trả phòng.
     */
    public function extendStay(Request $request)
    {
        $roomId = filter_var($request->input('room_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $mode = $request->input('mode', 'days');
        $amount = filter_var($request->input('amount', $request->input('days', 0)), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($roomId === false) {
            return response()->json(['success' => false, 'message' => 'Phòng không hợp lệ.'], 400);
        }

        $max = $mode === 'hours' ? 12 : 30;
        if (!in_array($mode, ['hours', 'days'], true) || $amount === false || $amount > $max) {
            return response()->json(['success' => false, 'message' => 'Thời lượng gia hạn không hợp lệ.'], 400);
        }

        $booking = Booking::query()
            ->whereHas('rooms', fn ($query) => $query->where('rooms.id', $roomId))
            ->where('status', 'checked_in')->latest('check_out')->first();
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Phòng chưa có booking đã nhận phòng.'], 404);
        }

        try {
            $result = app(\App\Services\StayExtensionService::class)->extend($booking->id, $mode, $amount);
            return response()->json([
                'success' => true,
                'message' => $result['message'].' Chi phí bổ sung: '.number_format($result['added_amount'], 0, ',', '.').'đ.',
                'booking_id' => $booking->id,
                'new_checkout' => $result['booking']->check_out->toDateString(),
                'hourly_rate' => $result['hourly_rate'],
                'added_amount' => $result['added_amount'],
                'total_price' => (float) $result['booking']->total_price,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }
    }

    /**
     * Lấy thông tin booking hôm nay của 1 phòng.
     */
    public function getTodayBooking(Request $request)
    {
        $roomId = (int) $request->query('room_id');
        try {
            $today = now('Asia/Ho_Chi_Minh')->toDateString();
            $holdExpiry = $this->pendingHoldExpirySql();
            $booking = DB::selectOne("
                SELECT b.id, b.customer_name, b.customer_phone, b.customer_email, 
                       b.check_in, b.check_out, b.adult_count, b.child_count, b.total_price, b.status
                FROM bookings b
                JOIN booking_rooms br ON b.id = br.booking_id
                WHERE br.room_id = ?
                  AND DATE(b.check_in) = ?
                  AND (b.status = 'confirmed' OR (b.status = 'pending' AND (b.payment_status = 'paid' OR {$holdExpiry})))
                LIMIT 1
            ", [$roomId, $today]);

            if ($booking) {
                return response()->json([
                    'success' => true,
                    'booking' => $booking
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Không tìm thấy lịch đặt trước hôm nay cho phòng này.'], 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Không thể xử lý lúc này. Vui lòng thử lại.'], 500);
        }
    }

    /**
     * Quét QR lấy thông tin booking.
     */
    public function getBookingByScan(Request $request)
    {
        $qrPayload = (string) $request->query('payload', $request->query('token', ''));
        try {
            $tokens = app(\App\Services\CheckInTokenService::class);
            $token = $tokens->tokenFromQrPayload($qrPayload);
            $bookingId = $tokens->bookingId($token);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        try {
            $booking = DB::selectOne("
                SELECT id, customer_name, customer_phone, customer_email, 
                       check_in, check_out, adult_count, child_count, total_price, status
                FROM bookings
                WHERE id = ?
                LIMIT 1
            ", [$bookingId]);

            if (!$booking) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy thông tin đơn đặt phòng #' . $bookingId], 404);
            }

            $rooms = DB::select("
                SELECT r.id, r.room_number, r.status as room_status, rt.type_name
                FROM booking_rooms br
                JOIN rooms r ON br.room_id = r.id
                JOIN room_types rt ON r.room_type_id = rt.id
                WHERE br.booking_id = ?
            ", [$bookingId]);

            return response()->json([
                'success' => true,
                'booking' => $booking,
                'rooms' => $rooms,
                'checkin_token' => $token,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Không thể xử lý lúc này. Vui lòng thử lại.'], 500);
        }
    }

    /**
     * Nhận phòng nhanh cho toàn bộ phòng thuộc booking (khi quét QR).
     */
    public function quickCheckinMultipleRooms(Request $request)
    {
        if (! $this->currentInternalUserHasPermission('bp_checkin_checkout')) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được cấp quyền nhận phòng.',
            ], 403);
        }

        $token = (string) $request->input('token', '');
        try {
            $tokens = app(\App\Services\CheckInTokenService::class);
            $bookingId = $tokens->bookingId($token);
            $booking = app(\App\Services\BookingTransitionService::class)->checkIn($bookingId);
            $tokens->consume($token);
            return response()->json([
                'success' => true,
                'message' => 'Nhận phòng nhanh thành công cho ' . $booking->rooms->count() . ' phòng!'
            ]);
        } catch (\RuntimeException|\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
    public function cancellations()
    {
        $cancellations = \App\Models\Booking::with('rooms')
            ->where('status', 'cancelled')->orderByDesc('cancelled_at')->get()
            ->each(fn ($booking) => $booking->setAttribute('room_numbers', $booking->rooms->sortBy('room_number')->pluck('room_number')->join(', ')));

        return view('staff.cancellations', compact('cancellations'));
    }

    private function currentInternalUserHasPermission(string $permissionKey): bool
    {
        $userId = session('staff_user_id') ?: session('auth_user_id');
        $user = $userId ? \App\Models\User::find($userId) : null;

        return $user?->hasInternalPermission($permissionKey) ?? false;
    }

    private function currentInternalPermissions(): array
    {
        $userId = session('staff_user_id') ?: session('auth_user_id');
        $user = $userId ? \App\Models\User::find($userId) : null;

        return collect(\App\Models\User::INTERNAL_PERMISSION_CATALOG)
            ->mapWithKeys(fn ($permission, $key) => [$key => $user?->hasInternalPermission($key) ?? false])
            ->all();
    }

    private function pendingHoldExpirySql(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "datetime(b.updated_at) > datetime('now', '-".Booking::PAYMENT_HOLD_MINUTES." minutes')"
            : 'b.updated_at > DATE_SUB(NOW(), INTERVAL '.Booking::PAYMENT_HOLD_MINUTES.' MINUTE)';
    }
    
}
