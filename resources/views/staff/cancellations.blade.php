@extends('layouts.dashboard')

@section('content')
<div class="p-4">
    <h5 class="fw-bold mb-4">
        <i class="fa-solid fa-ban text-danger me-2"></i>Quản lý huỷ phòng
    </h5>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Khách hàng</th>
                        <th>Phòng</th>
                        <th>Check-in → Check-out</th>
                        <th>Tổng tiền</th>
                        <th>Lý do huỷ</th>
                        <th>Hoàn tiền</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cancellations as $c)
                    <tr>
                        <td class="fw-bold">#{{ $c->id }}</td>
                        <td>
                            {{ $c->customer_name }}<br>
                            <small class="text-muted">{{ $c->customer_phone }}</small>
                        </td>
                        <td><strong>{{ $c->room_numbers }}</strong></td>
                        <td>
                            {{ \Carbon\Carbon::parse($c->check_in)->format('d/m/Y') }}
                            → {{ \Carbon\Carbon::parse($c->check_out)->format('d/m/Y') }}
                        </td>
                        <td>{{ number_format($c->total_price) }}đ</td>
                        <td><small>{{ $c->cancellation_reason ?? '—' }}</small></td>
                        <td>
                            @if($c->refund_status === 'none')
                                <span class="badge bg-secondary">Không hoàn tiền</span>
                            @elseif($c->refund_status === 'eligible')
                                <span class="badge bg-warning text-dark">
                                    Cần hoàn: {{ number_format($c->refund_amount) }}đ
                                </span>
                            @elseif($c->refund_status === 'refunded')
                                <span class="badge bg-success">Đã hoàn tiền</span>
                            @endif
                        </td>
                        <td>
                            @if($c->refund_status === 'eligible')
                            <form method="POST" action="{{ route('staff.bookings.refund', $c->id) }}"
                                  onsubmit="return confirm('Xác nhận đã hoàn {{ number_format($c->refund_amount) }}đ cho {{ $c->customer_name }}?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-warning btn-sm fw-bold">
                                    ✓ Xác nhận đã hoàn
                                </button>
                            </form>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Chưa có booking nào bị huỷ</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection