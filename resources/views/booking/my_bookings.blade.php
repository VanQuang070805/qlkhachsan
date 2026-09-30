@extends('layouts.main')

@push('styles')
<style>
    .cleaning-switch-card {
        min-width: 210px;
        padding: .65rem .8rem;
        border: 1px solid #f3d38a;
        border-radius: .75rem;
        background: #fffaf0;
    }
    .cleaning-switch-card.requested {
        border-color: #f59e0b;
        background: #fff4d6;
    }
    .cleaning-switch-card .form-check-input { cursor: pointer; }
</style>
@endpush

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
                <th>Yêu cầu phòng</th>
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
                    @elseif($b->status === 'checked_in')
                        <div class="d-flex flex-column gap-2">
                            @foreach($b->rooms as $room)
                                <div class="cleaning-switch-card {{ $room->needs_cleaning ? 'requested' : '' }} text-start"
                                     id="cleaning-card-{{ $b->id }}-{{ $room->id }}">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input cleaning-request-toggle"
                                               type="checkbox"
                                               role="switch"
                                               id="cleaning-toggle-{{ $b->id }}-{{ $room->id }}"
                                               data-url="{{ route('booking.cleaning-request', [$b->id, $room->id]) }}"
                                               {{ $room->needs_cleaning ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="cleaning-toggle-{{ $b->id }}-{{ $room->id }}">
                                            Phòng {{ $room->room_number }} cần dọn dẹp
                                        </label>
                                    </div>
                                    <div class="small mt-1 cleaning-request-status {{ $room->needs_cleaning ? 'text-warning-emphasis' : 'text-muted' }}">
                                        {{ $room->needs_cleaning ? 'Đã báo lễ tân' : 'Chưa yêu cầu' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.cleaning-request-toggle').forEach(toggle => {
    toggle.addEventListener('change', async function () {
        const previousValue = !this.checked;
        const card = this.closest('.cleaning-switch-card');
        const status = card.querySelector('.cleaning-request-status');
        this.disabled = true;
        status.textContent = 'Đang cập nhật...';
        status.className = 'small mt-1 cleaning-request-status text-muted';

        try {
            const response = await fetch(this.dataset.url, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ needs_cleaning: this.checked })
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Không thể cập nhật yêu cầu.');

            card.classList.toggle('requested', data.needs_cleaning);
            status.textContent = data.needs_cleaning ? 'Đã báo lễ tân' : 'Chưa yêu cầu';
            status.className = 'small mt-1 cleaning-request-status ' + (data.needs_cleaning ? 'text-warning-emphasis' : 'text-muted');
        } catch (error) {
            this.checked = previousValue;
            card.classList.toggle('requested', previousValue);
            status.textContent = error.message;
            status.className = 'small mt-1 cleaning-request-status text-danger';
        } finally {
            this.disabled = false;
        }
    });
});
</script>
@endpush
