<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $today = date('Y-m-d');
        // Lấy toàn bộ phòng với trạng thái logic tính toán động
        $sql = "
            SELECT r.id, r.room_number, r.floor, r.status,
                   rt.type_name, rt.max_guests, rt.price, rt.id as room_type_id,
                   (SELECT COUNT(*) 
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                   ) as has_today_booking,
                   (SELECT COUNT(*)
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                   ) as has_active_booking,
                   (SELECT COUNT(*) 
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_out = ?
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                   ) as is_checkout_today,
                   (SELECT b.customer_name 
                    FROM booking_rooms br 
                    JOIN bookings b ON b.id = br.booking_id 
                    WHERE br.room_id = r.id 
                      AND (
                          (b.status = 'checked_in' AND r.status = 'occupied') 
                          OR (b.check_in = ? AND b.status IN ('confirmed', 'pending'))
                      )
                    ORDER BY (CASE WHEN b.status = 'checked_in' THEN 1 ELSE 2 END) ASC, b.id DESC
                    LIMIT 1
                   ) as customer_name,
                   (SELECT b.customer_phone 
                    FROM booking_rooms br 
                    JOIN bookings b ON b.id = br.booking_id 
                    WHERE br.room_id = r.id 
                      AND (
                          (b.status = 'checked_in' AND r.status = 'occupied') 
                          OR (b.check_in = ? AND b.status IN ('confirmed', 'pending'))
                      )
                    ORDER BY (CASE WHEN b.status = 'checked_in' THEN 1 ELSE 2 END) ASC, b.id DESC
                    LIMIT 1
                   ) as customer_phone,
                   (SELECT b.id
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_booking_id,
                   (SELECT b.customer_email
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_customer_email,
                   (SELECT b.check_in
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_check_in,
                   (SELECT b.check_out
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_check_out,
                   (SELECT b.adult_count
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_adult_count,
                   (SELECT b.child_count
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_child_count,
                   (SELECT b.total_price
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_total_price,
                   (SELECT b.deposit_amount
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.status = 'checked_in'
                      AND r.status = 'occupied'
                    ORDER BY b.check_out DESC
                    LIMIT 1
                   ) as active_deposit_amount,
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
                          AND r.status = 'occupied'
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
                          AND r.status = 'occupied'
                        LIMIT 1
                    )
                   ) as active_booking_room_count,
                   (SELECT COUNT(*)
                    FROM booking_rooms br2
                    JOIN rooms r2 ON r2.id = br2.room_id
                    WHERE r2.status = 'occupied'
                      AND br2.booking_id = (
                        SELECT b.id
                        FROM booking_rooms br
                        JOIN bookings b ON b.id = br.booking_id
                        WHERE br.room_id = r.id
                          AND b.status = 'checked_in'
                          AND r.status = 'occupied'
                        LIMIT 1
                    )
                   ) as active_booking_occupied_room_count,
                   (SELECT b.id
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_booking_id,
                   (SELECT b.customer_name
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_customer_name,
                   (SELECT b.customer_phone
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_customer_phone,
                   (SELECT b.customer_email
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_customer_email,
                   (SELECT b.check_in
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_check_in,
                   (SELECT b.check_out
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_check_out,
                   (SELECT b.adult_count
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_adult_count,
                   (SELECT b.child_count
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_child_count,
                   (SELECT b.total_price
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_total_price,
                   (SELECT b.deposit_amount
                    FROM booking_rooms br
                    JOIN bookings b ON b.id = br.booking_id
                    WHERE br.room_id = r.id
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                   ) as today_deposit_amount
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            ORDER BY r.floor DESC, r.room_number ASC
        ";

        $roomsRaw = DB::select($sql, array_fill(0, substr_count($sql, '?'), $today));

        // Phân nhóm phòng theo tầng
        $floors = [];
        $operationImages = config('operation_room_images', []);
        foreach ($roomsRaw as $index => $r) {
            $rArray = (array) $r;
            // Lấy danh sách tiện ích
            $amenitiesSql = "
                SELECT a.amenity_name 
                FROM amenities a
                JOIN room_type_amenities rta ON a.id = rta.amenity_id
                WHERE rta.room_type_id = ?
            ";
            $amenities = array_column(DB::select($amenitiesSql, [$rArray['room_type_id']]), 'amenity_name');
            $rArray['amenities_list'] = implode(', ', $amenities);
            $rArray['image_url'] = $operationImages[$index % max(1, count($operationImages))] ?? asset('images/rooms/default.jpg');

            $floors[$rArray['floor']][] = $rArray;
        }

        // Số booking bị huỷ cần xử lý hoàn tiền (hiển thị badge + toast cho lễ tân)
        $pendingRefunds = \App\Models\Booking::where('status', 'cancelled')
            ->where('refund_status', 'eligible')
            ->count();

        return view('staff.bookings', compact('floors', 'pendingRefunds'));
    }

    /**
     * Cập nhật trạng thái phòng (Check-in booking hoặc Checkout phòng/booking).
     */
    public function updateStatus(Request $request)
    {
        $roomId = (int) $request->input('room_id');
        $status = $request->input('status');
        $checkoutScope = $request->input('checkout_scope', 'booking');

        $validStatuses = ['available', 'occupied', 'cleaning'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['success' => false, 'message' => 'Trạng thái không hợp lệ: ' . $status], 400);
        }

        try {
            DB::beginTransaction();
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
                // Nhận phòng: Tìm booking hôm nay
                $today = date('Y-m-d');
                $booking = DB::selectOne("
                    SELECT b.id
                    FROM bookings b
                    JOIN booking_rooms br ON b.id = br.booking_id
                    WHERE br.room_id = ?
                      AND b.check_in = ?
                      AND b.status IN ('confirmed', 'pending')
                    LIMIT 1
                ", [$roomId, $today]);

                if (!$booking) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Không tìm thấy lịch đặt hôm nay để check-in phòng này.'], 404);
                }

                DB::update("UPDATE bookings SET status = 'checked_in', actual_check_in = NOW() WHERE id = ?", [$booking->id]);
                
                $rooms = DB::select("SELECT room_id FROM booking_rooms WHERE booking_id = ?", [$booking->id]);
                foreach ($rooms as $r) {
                    DB::update("UPDATE rooms SET status = 'occupied' WHERE id = ?", [$r->room_id]);
                }
                
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Nhận phòng thành công!',
                    'new_status' => 'occupied'
                ]);

            } elseif ($status === 'available') {
                // Đã dọn xong: Cập nhật trực tiếp từ cleaning sang available
                DB::update("UPDATE rooms SET status = 'available' WHERE id = ?", [$roomId]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật trạng thái phòng Đang trống thành công!',
                    'new_status' => 'available'
                ]);

            } elseif ($status === 'cleaning') {
                // Trả phòng: chuyển về trạng thái 'cleaning'
                $booking = DB::selectOne("
                    SELECT b.id
                    FROM bookings b
                    JOIN booking_rooms br ON b.id = br.booking_id
                    WHERE br.room_id = ?
                      AND b.status = 'checked_in'
                    LIMIT 1
                ", [$roomId]);

                if ($booking) {
                    if ($checkoutScope === 'room') {
                        // Chỉ trả phòng đơn lẻ
                        DB::update("UPDATE rooms SET status = 'cleaning' WHERE id = ?", [$roomId]);

                        // Kiểm tra xem các phòng còn lại trong booking có đang occupied không
                        $remaining = DB::selectOne("
                            SELECT COUNT(*) AS cnt
                            FROM booking_rooms br
                            JOIN rooms r ON r.id = br.room_id
                            WHERE br.booking_id = ?
                              AND r.status = 'occupied'
                        ", [$booking->id]);

                        if ((int) ($remaining->cnt ?? 0) === 0) {
                            DB::update("UPDATE bookings SET status = 'completed', actual_check_out = NOW(), payment_status = 'paid' WHERE id = ?", [$booking->id]);
                        }
                    } else {
                        // Trả toàn bộ booking
                        DB::update("UPDATE bookings SET status = 'completed', actual_check_out = NOW(), payment_status = 'paid' WHERE id = ?", [$booking->id]);
                        
                        $rooms = DB::select("SELECT room_id FROM booking_rooms WHERE booking_id = ?", [$booking->id]);
                        foreach ($rooms as $r) {
                            DB::update("UPDATE rooms SET status = 'cleaning' WHERE id = ?", [$r->room_id]);
                        }
                    }
                } else {
                    // Nếu không tìm thấy booking đang ở, chuyển trực tiếp phòng sang đang dọn
                    DB::update("UPDATE rooms SET status = 'cleaning' WHERE id = ?", [$roomId]);
                }

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

    /**
     * Check-in khách vãng lai hoặc Giữ chỗ phòng.
     */
    public function walkinCheckin(Request $request)
    {
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
            $reserved = \App\Models\Booking::reservedRoomIds(now()->toDateString(), $data['check_out']);
            $conflict = $rooms->pluck('id')->intersect($reserved)->isNotEmpty();
            if ($conflict) return response()->json(['success'=>false, 'message'=>'Phòng đã có lịch đặt trong thời gian này. Vui lòng chọn lại.'], 409);
            $total = $rooms->sum(fn ($room) => PriceSetting::calculateTotalPrice((float) $room->roomType->price, now()->toDateString(), $data['check_out']));
            $booking = \App\Models\Booking::create([
                'user_id'=>null, 'customer_name'=>$data['customer_name'],
                'customer_phone'=>$data['customer_phone'], 'customer_email'=>$data['customer_email'] ?? '',
                'check_in'=>now()->toDateString(), 'check_out'=>$data['check_out'],
                'actual_check_in'=>$data['walkin_type'] === 'now' ? now() : null,
                'adult_count'=>$data['adult_count'], 'child_count'=>$data['child_count'],
                'total_price'=>$total, 'deposit_amount'=>0, 'payment_method'=>'cash', 'payment_status'=>'pending',
                'status'=>$data['walkin_type'] === 'now' ? 'checked_in' : 'confirmed',
            ]);
            $booking->rooms()->attach($rooms->pluck('id'));
            if ($data['walkin_type'] === 'now') \App\Models\Room::whereIn('id', $rooms->pluck('id'))->update(['status'=>'occupied']);
            return response()->json(['success'=>true, 'message'=>$data['walkin_type'] === 'now' ? 'Nhận phòng thành công.' : 'Giữ chỗ phòng thành công.']);
        });
    }

    /**
     * Gia hạn ngày trả phòng.
     */
    public function extendStay(Request $request)
    {
        $roomId = (int) $request->input('room_id');
        $mode = $request->input('mode', 'days');
        $amount = (int) $request->input('amount', $request->input('days', 0));

        if ($roomId <= 0) {
            return response()->json(['success' => false, 'message' => 'Phòng không hợp lệ.'], 400);
        }

        $max = $mode === 'hours' ? 12 : 30;
        if (!in_array($mode, ['hours', 'days'], true) || $amount < 1 || $amount > $max) {
            return response()->json(['success' => false, 'message' => 'Thời lượng gia hạn không hợp lệ.'], 400);
        }

        try {
            DB::beginTransaction();

            $booking = DB::selectOne("
                SELECT b.id, b.check_out, b.total_price, b.status, b.customer_name
                FROM bookings b
                JOIN booking_rooms br ON br.booking_id = b.id
                WHERE br.room_id = ?
                  AND b.status = 'checked_in'
                ORDER BY b.check_out DESC
                LIMIT 1
            ", [$roomId]);

            if (!$booking) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Phòng đang ở trạng thái sử dụng nhưng chưa có booking checked-in liên kết.'], 404);
            }

            if ($mode === 'hours') {
                $addedAmount = $amount * 200000;
                $newTotal = (float) $booking->total_price + $addedAmount;
                DB::update('UPDATE bookings SET total_price = ? WHERE id = ? AND status = ?', [$newTotal, $booking->id, 'checked_in']);
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Gia hạn thành công '.$amount.' giờ · '.number_format($addedAmount, 0, ',', '.').'đ.', 'added_amount' => $addedAmount, 'total_price' => $newTotal]);
            }

            $oldCheckout = $booking->check_out;
            $newCheckout = date('Y-m-d', strtotime($oldCheckout . ' +' . $amount . ' day'));

            $rooms = DB::select("
                SELECT r.id, r.room_number, rt.price
                FROM booking_rooms br
                JOIN rooms r ON r.id = br.room_id
                JOIN room_types rt ON rt.id = r.room_type_id
                WHERE br.booking_id = ?
                  AND r.status = 'occupied'
            ", [$booking->id]);

            if (empty($rooms)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Booking hiện tại chưa có phòng liên kết.'], 400);
            }

            $roomIds = array_column($rooms, 'id');
            $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
            $conflictParams = array_merge($roomIds, [$booking->id, $newCheckout, $oldCheckout]);

            $conflicts = DB::select("
                SELECT DISTINCT b.id, b.customer_name, b.check_in, b.check_out,
                       GROUP_CONCAT(r.room_number ORDER BY r.room_number SEPARATOR ', ') AS room_numbers
                FROM bookings b
                JOIN booking_rooms br ON br.booking_id = b.id
                JOIN rooms r ON r.id = br.room_id
                WHERE br.room_id IN ($placeholders)
                  AND b.id <> ?
                  AND (
                      b.status IN ('pending', 'confirmed')
                      OR (b.status = 'checked_in' AND r.status = 'occupied')
                  )
                  AND b.check_in < ?
                  AND b.check_out > ?
                GROUP BY b.id, b.customer_name, b.check_in, b.check_out
                LIMIT 1
            ", $conflictParams);

            if (!empty($conflicts)) {
                $c = $conflicts[0];
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể gia hạn vì phòng ' . $c->room_numbers . ' đã có lịch đặt từ ' . date('d/m/Y', strtotime($c->check_in)) . ' đến ' . date('d/m/Y', strtotime($c->check_out)) . '.'
                ], 409);
            }

            $addedAmount = 0;
            foreach ($rooms as $r) {
                $addedAmount += PriceSetting::calculateTotalPrice((float) $r->price, $oldCheckout, $newCheckout);
            }

            $newTotal = (float) $booking->total_price + $addedAmount;

            DB::update("
                UPDATE bookings
                SET check_out = ?, total_price = ?
                WHERE id = ? AND status = 'checked_in'
            ", [$newCheckout, $newTotal, $booking->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gia hạn thành công ' . $amount . ' ngày. Ngày trả mới: ' . date('d/m/Y', strtotime($newCheckout)) . '.',
                'booking_id' => $booking->id,
                'old_checkout' => $oldCheckout,
                'new_checkout' => $newCheckout,
                'added_amount' => $addedAmount,
                'total_price' => $newTotal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Không thể xử lý lúc này. Vui lòng thử lại.'], 500);
        }
    }

    /**
     * Lấy thông tin booking hôm nay của 1 phòng.
     */
    public function getTodayBooking(Request $request)
    {
        $roomId = (int) $request->query('room_id');
        try {
            $today = date('Y-m-d');
            $booking = DB::selectOne("
                SELECT b.id, b.customer_name, b.customer_phone, b.customer_email, 
                       b.check_in, b.check_out, b.adult_count, b.child_count, b.total_price, b.status
                FROM bookings b
                JOIN booking_rooms br ON b.id = br.booking_id
                WHERE br.room_id = ?
                  AND b.check_in = ?
                  AND b.status IN ('confirmed', 'pending')
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
        $bookingId = (int) $request->query('booking_id');
        if ($bookingId <= 0) {
            return response()->json(['success' => false, 'message' => 'Mã đặt phòng không hợp lệ.'], 400);
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
                'rooms' => $rooms
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
        $bookingId = (int) $request->input('booking_id');
        if ($bookingId <= 0) {
            return response()->json(['success' => false, 'message' => 'Mã đặt phòng không hợp lệ.'], 400);
        }

        try {
            DB::beginTransaction();

            $booking = DB::selectOne("
                SELECT id, check_in, check_out, status FROM bookings WHERE id = ? LIMIT 1
            ", [$bookingId]);

            if (!$booking) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn đặt phòng.'], 404);
            }

            $today = date('Y-m-d');
            if ($booking->check_in !== $today) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Không thể nhận phòng. Ngày nhận phòng là ' . date('d/m/Y', strtotime($booking->check_in)) . ' (không phải hôm nay).'], 400);
            }

            if ($booking->status === 'checked_in') {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Đơn đặt phòng này đã được nhận phòng trước đó.'], 400);
            }

            if (in_array($booking->status, ['completed', 'checked_out'])) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Đơn đặt phòng này đã hoàn tất trả phòng.'], 400);
            }

            $rooms = DB::select("SELECT room_id FROM booking_rooms WHERE booking_id = ?", [$bookingId]);

            if (empty($rooms)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Đơn đặt phòng chưa có phòng liên kết.'], 400);
            }

            $roomIds = array_column($rooms, 'room_id');
            $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
            $conflictParams = array_merge($roomIds, [$bookingId, $booking->check_out, $booking->check_in]);

            $conflicts = DB::select("
                SELECT b.id, b.customer_name,
                       GROUP_CONCAT(r.room_number ORDER BY r.room_number SEPARATOR ', ') AS room_numbers
                FROM bookings b
                JOIN booking_rooms br ON br.booking_id = b.id
                JOIN rooms r ON r.id = br.room_id
                WHERE br.room_id IN ($placeholders)
                  AND b.id <> ?
                  AND (
                      b.status IN ('pending', 'confirmed')
                      OR (b.status = 'checked_in' AND r.status = 'occupied')
                  )
                  AND b.check_in < ?
                  AND b.check_out > ?
                GROUP BY b.id, b.customer_name
                LIMIT 1
            ", $conflictParams);

            if (!empty($conflicts)) {
                $c = $conflicts[0];
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Không thể check-in vì phòng ' . $c->room_numbers . ' đang có booking khác.'], 409);
            }

            DB::update("UPDATE bookings SET status = 'checked_in', actual_check_in = NOW() WHERE id = ?", [$bookingId]);

            foreach ($rooms as $r) {
                DB::update("UPDATE rooms SET status = 'occupied' WHERE id = ?", [$r->room_id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nhận phòng nhanh thành công cho ' . count($rooms) . ' phòng!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Không thể xử lý lúc này. Vui lòng thử lại.'], 500);
        }
    }
    public function cancellations()
    {
        $cancellations = \App\Models\Booking::with('rooms')
            ->where('status', 'cancelled')->orderByDesc('cancelled_at')->get()
            ->each(fn ($booking) => $booking->setAttribute('room_numbers', $booking->rooms->sortBy('room_number')->pluck('room_number')->join(', ')));

        return view('staff.cancellations', compact('cancellations'));
    }
    
}
