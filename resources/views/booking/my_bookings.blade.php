@extends('layouts.main')

@section('content')
<?php $pageTitle = 'Đặt Phòng Của Tôi'; ?>

<h2 class="fw-bold mb-4">Đặt Phòng Của Tôi</h2>

<?php if (empty($bookings)): ?>
<div class="text-center py-5">
    <i class="bi bi-calendar-x fs-1 text-muted d-block mb-2"></i>
    <p class="text-muted mb-3">Bạn chưa có đặt phòng nào.</p>
    <a href="{{ route('rooms.search') }}" class="btn btn-primary">
        Tìm Phòng Ngay
    </a>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-dark text-center">
            <tr>
                <th>#</th>
                <th style="min-width:100px">Phòng</th>
                <th>Nhận phòng</th>
                <th>Trả phòng</th>
                <th>Số khách</th>
                <th>Tổng tiền</th>
                <th>Thanh toán</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b):
                $badgeMap = [
                    'pending'   => 'warning',
                    'confirmed' => 'success',
                    'cancelled' => 'secondary',
                    'rejected'  => 'danger',
                    'completed' => 'info',
                ];
                $badge = $badgeMap[$b['status']] ?? 'secondary';
                $payBadge = $b['payment_status'] === 'paid' ? 'success' : 'warning';
            ?>
            <tr>
                <td class="text-center">#{{ $b->id }}</td>
                <td class="text-center">
                    @foreach($b->rooms as $room)
                        <div class="fw-semibold">Phòng {{ $room->room_number }}</div>
                        <div class="text-muted small">{{ $room->roomType->name ?? '' }}</div>
                    @endforeach
                </td>
                <td class="text-center">{{ \Carbon\Carbon::parse($b->check_in)->format('d/m/Y H:i') }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($b->check_out)->format('d/m/Y H:i') }}</td>
                <td class="text-center">{{ $b->adult_count }} NL, {{ $b->child_count }} TE</td>
                <td class="text-center fw-bold">{{ number_format($b->total_price, 0, ',', '.') }} VNĐ</td>
                <td class="text-center">
                    <span class="badge bg-{{ $payBadge }}">
                        {{ $b->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge bg-{{ $badge }}">{{ $b->status }}</span>
                </td>
                <td class="text-center">
                    @if(in_array($b->status, ['pending', 'confirmed']))
                        <a href="{{ route('booking.cancel.show', $b->id) }}"
                            class="btn btn-sm btn-outline-danger">Huỷ</a>
                    @endif
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
@endsection
