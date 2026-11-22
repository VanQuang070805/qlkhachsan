<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PriceSetting;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', 'in:pending,confirmed,checked_in,completed,cancelled'],
        ]);

        $from = $filters['from'] ?? now('Asia/Ho_Chi_Minh')->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now('Asia/Ho_Chi_Minh')->addMonths(2)->endOfMonth()->toDateString();
        $search = trim((string) ($filters['q'] ?? ''));

        $bookings = Booking::query()
            ->with(['rooms.roomType'])
            ->whereDate('check_in', '<=', $to)
            ->whereDate('check_out', '>=', $from)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    if (ctype_digit($search)) {
                        $searchQuery->orWhere('id', (int) $search);
                    }

                    $searchQuery
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhereHas('rooms', fn ($roomQuery) => $roomQuery->where('room_number', 'like', "%{$search}%"));
                });
            })
            ->orderBy('check_in')
            ->orderBy('id')
            ->paginate(24)
            ->withQueryString();

        $user = $request->user('staff') ?? $request->user('admin') ?? $request->user();
        $canCheckIn = $user?->hasInternalPermission('bp_checkin_checkout') ?? false;

        return view('staff.booking-calendar', compact('bookings', 'filters', 'from', 'to', 'search', 'canCheckIn'));
    }

    public function show(Request $request, Booking $booking): View
    {
        $booking->load(['rooms.roomType', 'paymentLogs']);
        $user = $request->user('staff') ?? $request->user('admin') ?? $request->user();
        $canCheckIn = $user?->hasInternalPermission('bp_checkin_checkout') ?? false;
        $canEdit = ($user?->hasInternalPermission('bp_create_booking') ?? false)
            && in_array($booking->status, ['pending', 'confirmed'], true);
        $allRooms = $canEdit && ! $booking->isPaid()
            ? Room::with('roomType')->orderByRaw('CAST(room_number AS UNSIGNED)')->orderBy('room_number')->get()
            : collect();

        return view('staff.booking-calendar-show', compact('booking', 'canCheckIn', 'canEdit', 'allRooms'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless(in_array($booking->status, ['pending', 'confirmed'], true), 409, 'Booking này không còn được phép sửa.');

        $rules = [
            'customer_name' => ['required', 'string', 'max:200'],
            'customer_email' => ['required', 'email', 'max:200'],
            'customer_phone' => ['required', 'regex:/^0[0-9]{9}$/'],
        ];

        if (! $booking->isPaid()) {
            $rules += [
                'room_ids' => ['required', 'array', 'min:1', 'max:25'],
                'room_ids.*' => ['integer', 'distinct', 'exists:rooms,id'],
                'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
                'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
                'adult_count' => ['required', 'integer', 'min:1'],
                'child_count' => ['required', 'integer', 'min:0'],
            ];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($booking, $validated): void {
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless(in_array($lockedBooking->status, ['pending', 'confirmed'], true), 409, 'Booking này vừa thay đổi trạng thái.');

            $lockedBooking->fill([
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
            ]);

            if (! $lockedBooking->isPaid()) {
                $rooms = Room::with('roomType')
                    ->whereIn('id', $validated['room_ids'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                if ($rooms->count() !== count($validated['room_ids'])) {
                    throw ValidationException::withMessages(['room_ids' => 'Một số phòng không còn tồn tại.']);
                }

                $reserved = Booking::reservedRoomIds($validated['check_in'], $validated['check_out'], $lockedBooking->id);
                $currentRoomIds = $lockedBooking->rooms()->pluck('rooms.id');
                $unavailable = $rooms->contains(fn (Room $room) => $reserved->contains($room->id)
                    || (! $currentRoomIds->contains($room->id) && $room->status !== Room::STATUS_AVAILABLE));
                if ($unavailable) {
                    throw ValidationException::withMessages(['room_ids' => 'Một hoặc nhiều phòng không còn trống trong khoảng ngày đã chọn.']);
                }

                $totalCapacity = $rooms->sum(fn (Room $room) => (int) ($room->roomType?->max_guests ?? 0));
                $adultCapacity = $rooms->sum(fn (Room $room) => (int) ($room->roomType?->max_adults ?? 0));
                $childCapacity = $rooms->sum(fn (Room $room) => (int) ($room->roomType?->max_children ?? 0));
                if ($adultCapacity < $validated['adult_count']
                    || $childCapacity < $validated['child_count']
                    || $totalCapacity < ($validated['adult_count'] + $validated['child_count'])) {
                    throw ValidationException::withMessages(['room_ids' => 'Các phòng đã chọn không đủ sức chứa cho số khách.']);
                }

                $total = round($rooms->sum(fn (Room $room) => PriceSetting::calculateTotalPrice(
                    (float) ($room->roomType?->price ?? 0),
                    $validated['check_in'],
                    $validated['check_out']
                )));

                $lockedBooking->fill([
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'adult_count' => $validated['adult_count'],
                    'child_count' => $validated['child_count'],
                    'total_price' => $total,
                    'deposit_amount' => round($total * 0.5),
                ]);
                $lockedBooking->rooms()->sync($rooms->pluck('id'));
            }

            $lockedBooking->save();
        });

        return back()->with('success', "Đã cập nhật booking #{$booking->id}.");
    }

    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;

        DB::transaction(function () use ($booking, $reason): void {
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless(in_array($lockedBooking->status, ['pending', 'confirmed'], true), 409, 'Booking này không thể hủy.');

            $refundAmount = $lockedBooking->isRefundEligible()
                ? min((float) $lockedBooking->total_price, $lockedBooking->successfulPaymentAmount())
                : 0;
            $lockedBooking->update([
                'status' => 'cancelled',
                'cancelled_at' => now('Asia/Ho_Chi_Minh'),
                'cancellation_reason' => $reason ?: 'Nhân viên hủy từ lịch đặt phòng.',
                'refund_status' => $refundAmount > 0 ? 'eligible' : 'none',
                'refund_amount' => $refundAmount,
            ]);
        });

        return redirect()->route('staff.booking-calendar')->with('success', "Đã hủy booking #{$booking->id}; lịch sử vẫn được lưu.");
    }
}
