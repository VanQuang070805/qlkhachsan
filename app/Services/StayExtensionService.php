<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PaymentLog;
use App\Models\PriceSetting;
use App\Models\Room;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StayExtensionService
{
    public function extend(int $bookingId, string $mode, int $amount): array
    {
        if (! in_array($mode, ['hours', 'days'], true) || $amount < 1 || $amount > ($mode === 'hours' ? 12 : 30)) {
            throw new DomainException('Thời lượng gia hạn không hợp lệ.');
        }

        return DB::transaction(function () use ($bookingId, $mode, $amount): array {
            $booking = Booking::query()->lockForUpdate()->findOrFail($bookingId);
            if ($booking->status !== 'checked_in') {
                throw new DomainException('Chỉ booking đã nhận phòng mới được gia hạn.');
            }

            $rooms = Room::with('roomType')->whereIn('id', $booking->rooms()->pluck('rooms.id'))
                ->orderBy('id')->lockForUpdate()->get();
            if ($rooms->isEmpty() || $rooms->contains(fn (Room $room) => ! in_array($room->status, ['occupied', 'overdue'], true))) {
                throw new DomainException('Phòng chưa ở trạng thái lưu trú để gia hạn.');
            }

            $checkoutDate = $booking->check_out->toDateString();
            $checkoutNoon = Carbon::parse($checkoutDate, 'Asia/Ho_Chi_Minh')->setTime(12, 0);
            $hourlyLogs = $booking->stayExtensionLogs()->lockForUpdate()->get()->filter(fn (PaymentLog $log) =>
                ($log->raw_response['mode'] ?? null) === 'hours'
                    && ($log->raw_response['checkout_date'] ?? null) === $checkoutDate
            );
            $currentDue = $hourlyLogs->last()
                ? Carbon::parse($hourlyLogs->last()->raw_response['due_at'], 'Asia/Ho_Chi_Minh')
                : $checkoutNoon;
            if (now('Asia/Ho_Chi_Minh')->gt($currentDue)) {
                throw new DomainException('Đã quá thời điểm trả phòng; vui lòng liên hệ lễ tân.');
            }

            $nextDay = $checkoutNoon->copy()->addDay()->toDateString();
            $newDue = $mode === 'hours' ? $currentDue->copy()->addHours($amount) : null;
            $convertToNight = $mode === 'hours' && $newDue->gt($checkoutNoon->copy()->setTime(18, 0));
            $newCheckout = $mode === 'days'
                ? $booking->check_out->copy()->addDays($amount)->toDateString()
                : ($convertToNight ? $nextDay : $checkoutDate);
            $requiresOvernight = $mode === 'days' || $convertToNight;
            $conflictEnd = $requiresOvernight
                ? Carbon::parse($newCheckout, 'Asia/Ho_Chi_Minh')->addDay()->toDateString()
                : $nextDay;

            $reserved = Booking::reservedRoomIds($checkoutDate, $conflictEnd, $booking->id);
            if ($reserved->intersect($rooms->pluck('id'))->isNotEmpty()) {
                throw new DomainException('Phòng đã có booking trong ngày gia hạn; vui lòng chọn thời gian khác.');
            }

            $previousHourlyCharge = round((float) $hourlyLogs->sum('amount'), 2);
            if ($mode === 'hours' && ! $convertToNight) {
                $hourlyRate = round($rooms->sum(fn (Room $room) => (float) $room->roomType->price * .1), 2);
                $addedAmount = round($hourlyRate * $amount, 2);
                $booking->update(['total_price' => round((float) $booking->total_price + $addedAmount, 2)]);

                PaymentLog::create([
                    'booking_id' => $booking->id,
                    'gateway' => in_array($booking->payment_method, ['cash', 'vietqr', 'momo', 'zalopay', 'vnpay'], true)
                        ? $booking->payment_method : 'cash',
                    'reference_code' => 'EXT'.$booking->id.Str::upper(Str::random(16)),
                    'amount' => $addedAmount,
                    'purpose' => 'stay_extension',
                    'status' => 'pending',
                    'raw_response' => [
                        'mode' => 'hours',
                        'checkout_date' => $checkoutDate,
                        'due_at' => $newDue->toIso8601String(),
                        'hours' => $amount,
                    ],
                ]);

                $message = 'Đã gia hạn đến '.$newDue->format('H:i d/m/Y').'.';
            } else {
                $nightPrice = round($rooms->sum(fn (Room $room) => PriceSetting::calculateTotalPrice(
                    (float) $room->roomType->price, $checkoutDate, $newCheckout
                )), 2);
                $addedAmount = max(0, round($nightPrice - $previousHourlyCharge, 2));
                $booking->update([
                    'check_out' => $newCheckout,
                    'total_price' => round((float) $booking->total_price + $addedAmount, 2),
                ]);
                $message = $mode === 'days'
                    ? 'Đã gia hạn lưu trú đến '.Carbon::parse($newCheckout)->format('d/m/Y').'.'
                    : 'Gia hạn vượt 18:00 được tính thành một đêm, trả phòng trước 12:00 ngày '.Carbon::parse($newCheckout)->format('d/m/Y').'.';
            }

            return [
                'message' => $message,
                'added_amount' => $addedAmount,
                'hourly_rate' => round($rooms->sum(fn (Room $room) => (float) $room->roomType->price * .1), 2),
                'booking' => $booking->fresh(),
            ];
        });
    }
}
