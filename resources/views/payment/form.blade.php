@extends('layouts.main')

@section('title', 'Thanh toán · Royal Hotel')

@section('content')
@php $nights = \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out); @endphp
<section class="commerce-page payment-page" aria-labelledby="payment-title">
    @if(!empty($isPreview))
        <div class="payment-preview-notice" role="status">Chế độ xem trước giao diện · Thanh toán thật đang được khóa</div>
    @endif
    <header class="commerce-heading" data-reveal>
        <p class="editorial-eyebrow">Đặt phòng #{{ $booking->id }}</p>
        <h1 id="payment-title">Complete your stay</h1>
        <p>Chọn phương thức phù hợp để xác nhận đặt phòng tại Royal Hotel.</p>
    </header>

    @include('client.partials.booking-steps', [
        'currentStep' => 3,
        'stepLinks' => [
            1 => route('rooms.detail', $booking->rooms->first()->room_type_id),
            2 => route('booking.create', ['room_ids'=>$booking->rooms->pluck('id')->all(),'check_in'=>$booking->check_in->format('Y-m-d'),'check_out'=>$booking->check_out->format('Y-m-d'),'adults'=>$booking->adult_count,'children'=>$booking->child_count]),
        ],
    ])

    <div class="payment-layout">
        <aside class="payment-summary">
            <div class="payment-summary__top"><span>Tóm tắt lưu trú</span><i class="bi bi-shield-check" aria-hidden="true"></i></div>
            <h2>{{ $booking->rooms->map(fn($room) => 'Phòng '.$room->room_number)->join(', ') }}</h2>
            <dl class="payment-summary__facts">
                <div><dt>Nhận phòng</dt><dd>{{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}</dd></div>
                <div><dt>Trả phòng</dt><dd>{{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</dd></div>
                <div><dt>Thời lượng</dt><dd>{{ $nights }} đêm</dd></div>
                <div><dt>Khách</dt><dd>{{ $booking->adult_count }} người lớn · {{ $booking->child_count }} trẻ em</dd></div>
            </dl>
            <div class="payment-summary__guest"><span>{{ $booking->customer_name }}</span><small>{{ $booking->customer_email }} · {{ $booking->customer_phone }}</small></div>
            <div class="payment-total"><span>Đặt cọc 50%</span><strong>{{ number_format($booking->deposit_amount, 0, ',', '.') }} ₫</strong><small>Tổng kỳ nghỉ {{ number_format($booking->total_price, 0, ',', '.') }} ₫</small></div>
        </aside>

        <div class="payment-method-panel" data-reveal data-window-frame data-window-title="Phương thức thanh toán">
            @include('client.partials.window-controls', ['label' => 'phương thức thanh toán'])
            <div class="payment-method-panel__heading"><p class="editorial-eyebrow">Secure payment</p><h2>Choose payment</h2><p>Một lựa chọn cuối cùng để xác nhận không gian của bạn.</p></div>
            <form action="{{ !empty($isPreview) ? '#' : route('payment.update', $booking->id) }}" method="POST" id="paymentForm" data-preview="{{ !empty($isPreview) ? 'true' : 'false' }}">
                @csrf @method('PATCH')
                @foreach([
                    ['cash','bi-cash-stack','Tiền mặt tại quầy','Giữ phòng và thanh toán khi nhận phòng'],
                    ['momo','bi-phone','Ví MoMo','Thanh toán nhanh qua ứng dụng MoMo'],
                    ['vietqr','bi-qr-code','Chuyển khoản VietQR','Quét mã bằng ứng dụng ngân hàng'],
                    ['zalopay','bi-wallet2','ZaloPay','Thanh toán trực tiếp qua ví ZaloPay'],
                    ['vnpay','bi-credit-card','VNPay','Hỗ trợ nhiều ngân hàng tại Việt Nam'],
                ] as [$value,$icon,$name,$description])
                <label class="payment-choice" for="method_{{ $value }}">
                    <input type="radio" name="payment_method" id="method_{{ $value }}" value="{{ $value }}">
                    <span class="payment-choice__icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    <span class="payment-choice__copy"><strong>{{ $name }}</strong><small>{{ $description }}</small></span>
                    <span class="payment-choice__check"><i class="bi bi-check2" aria-hidden="true"></i></span>
                </label>
                @endforeach
                <p id="methodError" class="payment-error" hidden>Vui lòng chọn một phương thức thanh toán.</p>
                <button type="submit" class="button payment-submit" id="payBtn" disabled>Chọn phương thức để tiếp tục</button>
                <p class="payment-secure"><i class="bi bi-lock" aria-hidden="true"></i> Thông tin thanh toán được mã hóa an toàn</p>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('paymentForm');
    const button = document.getElementById('payBtn');
    const error = document.getElementById('methodError');
    const labels = [...form.querySelectorAll('.payment-choice')];
    const names = { cash:'Xác nhận thanh toán tại quầy', momo:'Thanh toán MoMo', vietqr:'Tạo mã VietQR', zalopay:'Thanh toán ZaloPay', vnpay:'Thanh toán VNPay' };
    labels.forEach(label => label.querySelector('input').addEventListener('change', event => {
        labels.forEach(item => item.classList.toggle('is-selected', item === label));
        button.disabled = false;
        button.textContent = names[event.target.value] || 'Tiếp tục thanh toán';
        error.hidden = true;
    }));
    form.addEventListener('submit', event => {
        if (!form.querySelector('input[name="payment_method"]:checked')) {
            event.preventDefault();
            error.hidden = false;
            return;
        }
        if (form.dataset.preview === 'true') {
            event.preventDefault();
            error.hidden = false;
            error.textContent = 'Đây là chế độ xem trước. Giao dịch thật không được thực hiện.';
            return;
        }
        button.disabled = true;
        button.textContent = 'Đang xử lý…';
    });
});
</script>
@endpush
