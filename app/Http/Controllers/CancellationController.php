<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
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

        if ((int) $booking->user_id !== (int) auth()->id()) {
            abort(403);
        }

        // Chỉ cho hủy khi booking chưa completed/cancelled
        if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
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
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $isEligible = false;
        $cancelled = DB::transaction(function () use ($validated, $id, &$isEligible) {
            $booking = Booking::lockForUpdate()->findOrFail($id);
            abort_unless((int) $booking->user_id === (int) auth()->id(), 403);
            if (!in_array($booking->status, ['pending', 'confirmed'], true)) return false;
            $isEligible = $booking->isRefundEligible();
            $booking->update([
                'status' => 'cancelled', 'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'],
                'refund_status' => $isEligible ? 'eligible' : 'none',
                'refund_amount' => $isEligible ? $booking->deposit_amount : 0,
            ]);
            // Cancelling a future reservation must not change a room occupied by another guest.
            return true;
        });
        if (!$cancelled) return redirect()->route('booking.mine')->with('error', 'Booking này không thể hủy.');

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
        return DB::transaction(function () use ($id) {
            $booking = Booking::lockForUpdate()->findOrFail($id);
            if ($booking->status !== 'cancelled' || $booking->refund_status !== 'eligible' || $booking->refund_amount <= 0) {
                return back()->with('error', 'Booking này không đủ điều kiện hoàn tiền.');
            }
            $booking->update(['refund_status' => 'refunded', 'payment_status' => 'refunded']);
            return back()->with('success', 'Đã xác nhận hoàn tiền '.number_format($booking->refund_amount).'đ cho booking #'.$booking->id.'.');
        });
    }
}
