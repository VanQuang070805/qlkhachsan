@extends('layouts.dashboard')
@section('title', 'Hoàn tiền · Royal Hotel')
@section('content')
@php
    $refunds = collect($cancellations);
    $pending = $refunds->where('refund_status', 'eligible');
@endphp
<section class="refund-workspace internal-main">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: #0f172a; letter-spacing: -0.03em;">Xử lý hoàn tiền</h1>
            <p class="text-muted small mb-0" style="color: #64748b;">Theo dõi yêu cầu và xác nhận các khoản đã hoàn trả cho khách hàng.</p>
        </div>
        <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.76rem; padding: 6px 12px; border-radius: 999px;">
            <i class="bi bi-shield-check me-1 text-primary"></i> {{ $refunds->count() }} yêu cầu
        </span>
    </div>

    @if(session('success'))<div class="alert alert-success border-0 mb-3" role="status" style="border-radius: 12px;">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger border-0 mb-3" role="alert" style="border-radius: 12px;">{{ session('error') }}</div>@endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 p-3 h-100" style="border-radius: 16px; border: 1px solid rgba(0,0,0,0.06) !important; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.01);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold" style="font-size: 0.75rem;">Chờ xử lý</span>
                    <i class="bi bi-clock-history text-warning" style="font-size: 1rem;"></i>
                </div>
                <div class="fs-4 fw-bold text-dark" style="letter-spacing: -0.02em;">{{ $pending->count() }}</div>
                <small class="text-muted" style="font-size: 0.72rem;">Yêu cầu đủ điều kiện</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 p-3 h-100" style="border-radius: 16px; border: 1px solid rgba(0,0,0,0.06) !important; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.01);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold" style="font-size: 0.75rem;">Số tiền cần hoàn</span>
                    <i class="bi bi-cash-stack text-danger" style="font-size: 1rem;"></i>
                </div>
                <div class="fs-4 fw-bold text-danger" style="letter-spacing: -0.02em;">{{ number_format($pending->sum('refund_amount'), 0, ',', '.') }} đ</div>
                <small class="text-muted" style="font-size: 0.72rem;">Chưa xác nhận chuyển khoản</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 p-3 h-100" style="border-radius: 16px; border: 1px solid rgba(0,0,0,0.06) !important; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.01);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold" style="font-size: 0.75rem;">Đã hoàn thành</span>
                    <i class="bi bi-check2-circle text-success" style="font-size: 1rem;"></i>
                </div>
                <div class="fs-4 fw-bold text-success" style="letter-spacing: -0.02em;">{{ number_format($refunds->where('refund_status', 'refunded')->sum('refund_amount'), 0, ',', '.') }} đ</div>
                <small class="text-muted" style="font-size: 0.72rem;">{{ $refunds->where('refund_status', 'refunded')->count() }} yêu cầu hoàn tất</small>
            </div>
        </div>
    </div>

    <form class="report-filter-bar mb-3" onsubmit="return false;">
        <div class="report-filter-inputs">
            <label class="report-filter-field position-relative" style="min-width: 260px; flex: 1;">
                <i class="bi bi-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; pointer-events: none;"></i>
                <input type="search" data-refund-search class="report-input-pill w-100 ps-4" aria-label="Tìm yêu cầu hoàn tiền" placeholder="Mã đặt phòng, tên khách, số điện thoại…" style="padding-left: 32px !important;">
            </label>
            <label class="report-filter-field">
                <span>Trạng thái</span>
                <select data-refund-status class="report-select-pill">
                    <option value="">Tất cả trạng thái</option>
                    <option value="eligible">Chờ hoàn tiền</option>
                    <option value="refunded">Đã hoàn tiền</option>
                    <option value="none">Không hoàn tiền</option>
                </select>
            </label>
        </div>
    </form>

    <div class="refund-list">
    @foreach($refunds as $c)
        <article class="card border-0 mb-3 p-3" data-refund-record data-status="{{ $c->refund_status }}" data-search="{{ $c->id }} {{ $c->customer_name }} {{ $c->customer_phone }} {{ $c->room_numbers }}" style="border-radius: 16px; border: 1px solid rgba(0,0,0,0.06) !important; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.01);">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom" style="border-color: rgba(0,0,0,0.05) !important;">
                <div class="d-flex align-items-center gap-3">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #f0f7ff; color: #0284c7; flex-shrink: 0;">
                        <i class="bi bi-arrow-return-left" style="font-size: 1rem;"></i>
                    </span>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="h6 fw-bold m-0" style="color: #0f172a;">{{ $c->customer_name }}</h2>
                            <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 0.7rem; font-weight: 600;">#{{ $c->id }}</span>
                        </div>
                        <span class="text-muted small" style="font-size: 0.75rem;">{{ $c->customer_phone }}</span>
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-muted small" style="font-size: 0.72rem;">Khoản hoàn</div>
                    <strong class="fs-5 fw-bold text-dark" style="letter-spacing: -0.02em;">{{ number_format($c->refund_amount, 0, ',', '.') }} đ</strong>
                    <div>
                        <span class="badge" style="background: {{ $c->refund_status === 'eligible' ? '#fffbeb' : ($c->refund_status === 'refunded' ? '#ecfdf5' : '#fef2f2') }}; color: {{ $c->refund_status === 'eligible' ? '#b45309' : ($c->refund_status === 'refunded' ? '#047857' : '#b91c1c') }}; font-size: 0.72rem; font-weight: 600; padding: 4px 8px; border-radius: 999px;">
                            {{ ['eligible'=>'Chờ hoàn tiền','refunded'=>'Đã hoàn tiền','none'=>'Không hoàn tiền'][$c->refund_status] ?? 'Chưa xác định' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-3">
                <div class="d-flex align-items-center gap-4 text-muted small" style="font-size: 0.78rem;">
                    <div>
                        <span class="text-muted">Phòng:</span>
                        <strong class="text-dark">{{ $c->room_numbers ?: '—' }}</strong>
                    </div>
                    <div>
                        <span class="text-muted">Kỳ nghỉ:</span>
                        <strong class="text-dark">{{ \Carbon\Carbon::parse($c->check_in)->format('d/m/Y') }} &rarr; {{ \Carbon\Carbon::parse($c->check_out)->format('d/m/Y') }}</strong>
                    </div>
                    <div class="d-none d-md-block">
                        <i class="bi bi-chat-left-text me-1 text-muted"></i>
                        <span>{{ $c->cancellation_reason ?: 'Chưa có lý do hủy' }}</span>
                    </div>
                </div>

                @if($c->refund_status === 'eligible')
                    <button type="button" class="btn btn-sm btn-dark d-inline-flex align-items-center gap-2 px-3 py-1.5 fw-medium" data-bs-toggle="modal" data-bs-target="#refundConfirm" data-refund-url="{{ route('staff.bookings.refund', $c->id) }}" data-refund-name="{{ $c->customer_name }}" data-refund-amount="{{ number_format($c->refund_amount, 0, ',', '.') }} đ" style="border-radius: 999px; background: #070709; font-size: 0.78rem; border: none;">
                        <i class="bi bi-check2-circle text-success"></i>
                        <span>Xác nhận đã hoàn</span>
                    </button>
                @endif
            </div>
        </article>
    @endforeach
    </div>
    <div class="card border-0 p-5 text-center my-4" data-refund-empty @if($refunds->isNotEmpty()) hidden @endif style="border-radius: 16px; background: #ffffff; border: 1px solid rgba(0,0,0,0.06) !important;">
        <i class="bi bi-inbox text-muted fs-1 mb-2"></i>
        <h2 class="h6 fw-bold text-dark mb-1">Không có yêu cầu phù hợp</h2>
        <p class="text-muted small m-0">Thử tìm tên khách khác hoặc chọn tất cả trạng thái.</p>
    </div>
</section>
<div class="modal fade" id="refundConfirm" tabindex="-1" aria-labelledby="refundConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; background: #ffffff;">
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div class="window-controls window-controls--modal d-flex align-items-center gap-1" style="pointer-events: none; margin: 0; padding: 0;" aria-hidden="true">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <h5 class="modal-title fw-bold text-dark m-0 fs-6 text-center flex-grow-1" id="refundConfirmTitle" style="color: #0f172a;">Xác nhận khoản đã hoàn</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng" style="font-size: 0.75rem;"></button>
            </div>
            <form method="POST" data-refund-form>
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid rgba(0,0,0,0.06); border-radius: 12px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="color: #64748b; font-size: 0.88rem;">Khách hàng</span>
                            <strong class="text-dark fw-bold" data-refund-customer style="color: #0f172a; font-size: 0.95rem;"></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                            <span style="color: #64748b; font-size: 0.88rem;">Số tiền hoàn</span>
                            <span class="refund-confirm-amount fw-bold text-success" data-refund-total style="font-size: 1.25rem; font-variant-numeric: tabular-nums;"></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2 p-3 rounded" style="background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; font-size: 0.82rem; line-height: 1.5;">
                        <i class="bi bi-info-circle-fill text-warning mt-0 flex-shrink-0"></i>
                        <span>Chỉ xác nhận sau khi bạn đã hoàn tiền cho khách. Thao tác này ghi nhận kết quả vào hệ thống, không tự động chuyển tiền.</span>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3 d-flex justify-content-end gap-2" style="background: #f8fafc; border-top: 1px solid rgba(0,0,0,0.06);">
                    <button type="button" class="btn btn-light px-4 fw-medium" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); color: #334155; font-size: 0.88rem;">Quay lại</button>
                    <button class="btn btn-primary px-4 fw-medium" type="submit" style="border-radius: 10px; font-size: 0.88rem;">Xác nhận đã hoàn tiền</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
