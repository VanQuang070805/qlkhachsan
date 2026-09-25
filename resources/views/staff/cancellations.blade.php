@extends('layouts.receiption')
@section('title', 'Hoàn tiền · Royal Hotel')
@section('content')
@php
    $refunds = collect($cancellations);
    $pending = $refunds->where('refund_status', 'eligible');
@endphp
<section class="refund-workspace internal-main">
    <header class="module-head"><div><h1>Hoàn tiền</h1><p>Theo dõi yêu cầu và xác nhận khoản đã hoàn cho khách.</p></div><span class="soft-tag"><i class="bi bi-shield-check"></i> {{ $refunds->count() }} yêu cầu</span></header>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    <div class="refund-metrics">
        <article><span>Chờ xử lý</span><strong>{{ $pending->count() }}</strong><small>Yêu cầu đủ điều kiện</small></article>
        <article><span>Số tiền cần hoàn</span><strong>{{ number_format($pending->sum('refund_amount'), 0, ',', '.') }} ₫</strong><small>Chưa xác nhận hoàn tiền</small></article>
        <article><span>Đã hoàn</span><strong>{{ number_format($refunds->where('refund_status', 'refunded')->sum('refund_amount'), 0, ',', '.') }} ₫</strong><small>{{ $refunds->where('refund_status', 'refunded')->count() }} yêu cầu hoàn tất</small></article>
    </div>
    <div class="refund-toolbar">
        <label class="refund-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" data-refund-search aria-label="Tìm yêu cầu hoàn tiền" placeholder="Mã đặt phòng, tên khách, số điện thoại…"></label>
        <label><span class="visually-hidden">Trạng thái hoàn tiền</span><select data-refund-status class="form-select"><option value="">Tất cả trạng thái</option value="eligible">Chờ hoàn tiền</option><option value="refunded">Đã hoàn tiền</option><option value="none">Không hoàn tiền</option></select></label>
    </div>
    <div class="refund-list">
    @foreach($refunds as $c)
        <article class="refund-record" data-refund-record data-status="{{ $c->refund_status }}" data-search="{{ $c->id }} {{ $c->customer_name }} {{ $c->customer_phone }} {{ $c->room_numbers }}">
            <div class="refund-record__guest"><span class="refund-avatar"><i class="bi bi-arrow-return-left"></i></span><div><small>Đặt phòng #{{ $c->id }}</small><h2>{{ $c->customer_name }}</h2><span>{{ $c->customer_phone }}</span></div></div>
            <dl><div><dt>Phòng</dt><dd>{{ $c->room_numbers ?: '—' }}</dd></div><div><dt>Kỳ nghỉ</dt><dd>{{ \Carbon\Carbon::parse($c->check_in)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($c->check_out)->format('d/m/Y') }}</dd></div></dl>
            <div class="refund-record__amount"><small>Khoản hoàn</small><strong>{{ number_format($c->refund_amount, 0, ',', '.') }} ₫</strong><span class="status-pill status-{{ $c->refund_status === 'eligible' ? 'pending' : ($c->refund_status === 'refunded' ? 'confirmed' : 'cancelled') }}">{{ ['eligible'=>'Chờ hoàn tiền','refunded'=>'Đã hoàn tiền','none'=>'Không hoàn tiền'][$c->refund_status] ?? 'Chưa xác định' }}</span></div>
            <footer><p><i class="bi bi-chat-left-text"></i> {{ $c->cancellation_reason ?: 'Chưa có lý do hủy' }}</p>
            @if($c->refund_status === 'eligible')
                <button type="button" class="primary-action" data-bs-toggle="modal" data-bs-target="#refundConfirm" data-refund-url="{{ route('staff.bookings.refund', $c->id) }}" data-refund-name="{{ $c->customer_name }}" data-refund-amount="{{ number_format($c->refund_amount, 0, ',', '.') }} ₫"><i class="bi bi-check2-circle"></i> Xác nhận đã hoàn</button>
            @endif
            </footer>
        </article>
    @endforeach
    </div>
    <div class="refund-empty" data-refund-empty @if($refunds->isNotEmpty()) hidden @endif><i class="bi bi-inbox"></i><h2>Không có yêu cầu phù hợp</h2><p>Thử tên khách khác hoặc chọn tất cả trạng thái.</p></div>
</section>
<div class="modal fade" id="refundConfirm" tabindex="-1" aria-labelledby="refundConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title fs-5" id="refundConfirmTitle">Xác nhận khoản đã hoàn</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
        <form method="POST" data-refund-form>@csrf @method('PATCH')
            <div class="modal-body"><p>Khách hàng <strong data-refund-customer></strong></p><div class="refund-confirm-amount" data-refund-total></div><p class="text-muted mt-3 mb-0">Chỉ xác nhận sau khi bạn đã hoàn tiền cho khách. Thao tác này ghi nhận kết quả, không tự chuyển tiền.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Quay lại</button><button class="btn btn-primary" type="submit">Xác nhận đã hoàn tiền</button></div>
        </form>
    </div></div>
</div>
@endsection
