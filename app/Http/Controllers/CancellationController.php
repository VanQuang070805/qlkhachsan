<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CancellationController extends Controller
{
    /**
     * Hiển thị trang xác nhận hủy phòng.
     * Tính toán và hiển thị điều kiện hoàn tiền cho khách.
     */
    public function show(int $id)
    {
        $booking = Booking::with('rooms.roomType')->findOrFail($id);

        // Chỉ cho hủy khi booking chưa completed/cancelled
        if ($booking->isCancelled() || $booking->isCompleted()) {
            return redirect()->route('booking.mine')
                ->with('error', 'Booking này không thể hủy.');
        }

        $isEligible       = $booking->isRefundEligible();
        $deadlineDays     = $booking->refundDeadlineDays();
        $daysLeft         = $booking->daysUntilRefundDeadline();
        $checkIn          = Carbon::parse($booking->check_in);
        $isWeekendOrHoliday = in_array($checkIn->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
            || \App\Models\Holiday::isHoliday($checkIn);

        return view('booking.cancel', compact(
            'booking',
            'isEligible',
            'deadlineDays',
            'daysLeft',
            'isWeekendOrHoliday',
        ));
    }

    /**
     * Thực hiện hủy booking.
     * Nếu đủ điều kiện → đổi refund_status = 'eligible' (lễ tân xử lý hoàn tiền thủ công)
     * hoặc gọi API hoàn tiền tự động nếu cổng thanh toán hỗ trợ.
     */
    public function cancel(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        if ($booking->isCancelled() || $booking->isCompleted()) {
            return redirect()->route('booking.mine')
                ->with('error', 'Booking này không thể hủy.');
        }

        $isEligible = $booking->isRefundEligible();

        $booking->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => $request->reason,
            'refund_status'       => $isEligible ? 'eligible' : 'none',
            'refund_amount'       => $isEligible ? $booking->deposit_amount : 0,
        ]);

        // Trả phòng về trạng thái available
        foreach ($booking->rooms as $room) {
            $room->update(['status' => Room::STATUS_AVAILABLE]);
        }

        $message = $isEligible
            ? 'Hủy phòng thành công. Yêu cầu hoàn tiền đã được ghi nhận, chúng tôi sẽ xử lý trong 3-5 ngày làm việc.'
            : 'Hủy phòng thành công. Booking này không đủ điều kiện hoàn tiền vì đã quá thời hạn.';

        return redirect()->route('booking.mine')->with('success', $message);
    }

    /**
     * Lễ tân/Admin xác nhận đã hoàn tiền thủ công.
     */
    public function processRefund(int $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->refund_status !== 'eligible') {
            return back()->with('error', 'Booking này không đủ điều kiện hoàn tiền.');
        }

        $booking->update([
            'refund_status'  => 'refunded',
            'payment_status' => 'refunded',
        ]);

        return back()->with('success', "Đã xác nhận hoàn tiền " . number_format($booking->refund_amount) . "đ cho booking #{$booking->id}.");
    }
}
