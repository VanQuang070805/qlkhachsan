<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\RefundTransaction;
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
        $refundAmount     = min((float) $booking->total_price, $booking->successfulPaymentAmount());
        $checkIn          = Carbon::parse($booking->check_in);
        $isWeekendOrHoliday = in_array($checkIn->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
            || \App\Models\Holiday::isHoliday($checkIn);

        return view('booking.cancel', compact(
            'booking',
            'isEligible',
            'deadlineDays',
            'daysLeft',
            'refundAmount',
            'isWeekendOrHoliday',
        ));
    }

    /**
     * Thực hiện hủy booking.
     * Nếu đủ điều kiện, ghi nhận số tiền đã thực thu cần hoàn để lễ tân xử lý qua cổng thanh toán.
     */
    public function cancel(Request $request, int $id)
    {
        $bookingPreview = Booking::findOrFail($id);
        abort_unless((int) $bookingPreview->user_id === (int) auth()->id(), 403);

        if (! in_array($bookingPreview->status, ['pending', 'confirmed'], true)) {
            return redirect()->route('booking.mine')->with('error', 'Booking này không thể hủy.');
        }

        $requiresRefundBank = $bookingPreview->isRefundEligible();
        $rules = [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
        if ($requiresRefundBank) {
            $rules['refund_bank_name'] = ['required', 'string', 'max:120'];
            $rules['refund_account_number'] = ['required', 'string', 'max:50', 'regex:/^[0-9A-Za-z\s\-]{5,40}$/'];
            $rules['refund_account_holder'] = ['required', 'string', 'max:150'];
        } else {
            $rules['refund_bank_name'] = ['nullable', 'string', 'max:120'];
            $rules['refund_account_number'] = ['nullable', 'string', 'max:50'];
            $rules['refund_account_holder'] = ['nullable', 'string', 'max:150'];
        }

        $validated = $request->validate($rules, [
            'refund_bank_name.required' => 'Vui lòng nhập tên ngân hàng nhận hoàn tiền.',
            'refund_account_number.required' => 'Vui lòng nhập số tài khoản nhận hoàn tiền.',
            'refund_account_number.regex' => 'Số tài khoản chỉ gồm chữ và số (từ 5 đến 40 ký tự).',
            'refund_account_holder.required' => 'Vui lòng nhập họ tên chủ tài khoản nhận hoàn tiền.',
        ]);

        $isEligible = false;
        $cancelled = DB::transaction(function () use ($validated, $id, &$isEligible) {
            $booking = Booking::lockForUpdate()->findOrFail($id);
            abort_unless((int) $booking->user_id === (int) auth()->id(), 403);
            if (!in_array($booking->status, ['pending', 'confirmed'], true)) return false;
            $isEligible = $booking->isRefundEligible();
            $refundAmount = $isEligible
                ? min((float) $booking->total_price, $booking->successfulPaymentAmount())
                : 0;

            $bankName = $isEligible ? trim((string) ($validated['refund_bank_name'] ?? '')) : null;
            $accountNumber = $isEligible
                ? preg_replace('/\s+/', '', trim((string) ($validated['refund_account_number'] ?? '')))
                : null;
            $accountHolder = $isEligible
                ? mb_strtoupper(trim((string) ($validated['refund_account_holder'] ?? '')), 'UTF-8')
                : null;

            $booking->update([
                'status' => 'cancelled', 'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'] ?? null,
                'refund_status' => $refundAmount > 0 ? 'eligible' : 'none',
                'refund_amount' => $refundAmount,
                'refund_bank_name' => $bankName ?: null,
                'refund_account_number' => $accountNumber ?: null,
                'refund_account_holder' => $accountHolder ?: null,
            ]);
            // Cancelling a future reservation must not change a room occupied by another guest.
            return true;
        });
        if (!$cancelled) return redirect()->route('booking.mine')->with('error', 'Booking này không thể hủy.');

        $message = $isEligible
            ? 'Hủy phòng thành công. Yêu cầu hoàn tiền và thông tin tài khoản nhận hoàn đã được gửi tới bộ phận lễ tân để giải ngân.'
            : 'Hủy phòng thành công. Booking này không đủ điều kiện hoàn tiền vì đã quá thời hạn.';

        return redirect()->route('booking.mine')->with('success', $message);
    }

    /**
     * Lễ tân/Admin xác nhận đã thực hiện hoàn tiền bên cổng thanh toán.
     */
    public function processRefund(Request $request, int $id)
    {
        $validated = $request->validate([
            'provider_reference' => ['nullable', 'string', 'max:120', 'unique:refund_transactions,provider_reference'],
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $booking = Booking::lockForUpdate()->findOrFail($id);
            if ($booking->status !== 'cancelled' || $booking->refund_status !== 'eligible' || $booking->refund_amount <= 0) {
                return back()->with('error', 'Booking này không đủ điều kiện hoàn tiền.');
            }

            $actorId = session('staff_user_id') ?: session('auth_user_id') ?: auth()->id();
            $reference = trim((string) ($validated['provider_reference'] ?? ''));
            if ($reference === '') {
                $reference = 'MANUAL-'.$booking->id.'-'.now()->format('YmdHisv');
            }

            RefundTransaction::create([
                'booking_id' => $booking->id,
                'processed_by' => $actorId ?: null,
                'amount' => $booking->refund_amount,
                'provider' => $booking->payment_method ?: 'manual',
                'provider_reference' => $reference,
                'status' => 'completed',
                'processed_at' => now(),
                'metadata' => array_filter([
                    'source' => 'staff_confirmation',
                    'refund_bank_name' => $booking->refund_bank_name,
                    'refund_account_number' => $booking->refund_account_number,
                    'refund_account_holder' => $booking->refund_account_holder,
                ], static fn ($val) => $val !== null && $val !== ''),
            ]);
            $booking->update(['refund_status' => 'refunded', 'payment_status' => 'refunded']);
            return back()->with('success', 'Đã xác nhận hoàn tiền '.number_format($booking->refund_amount).'đ cho booking #'.$booking->id.'.');
        });
    }
}
