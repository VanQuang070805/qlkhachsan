@extends('layouts.main')

@section('title', 'Hủy phòng · Rosaliza Hotel')

@section('content')
<section class="commerce-page booking-cancel-container" aria-labelledby="cancel-title" style="padding-top: 20px; padding-bottom: 80px;">
    <header class="commerce-heading" data-reveal>
        <p class="editorial-eyebrow">Yêu cầu hủy phòng</p>
        <h1 id="cancel-title">Hủy phòng</h1>
    </header>

    <div style="max-width: 660px; margin: 0 auto;">
        {{-- macOS Card Container --}}
        <div style="background: #ffffff; border-radius: 28px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 60px -10px rgba(15,23,42,0.08); overflow: hidden;" data-reveal>
            
            {{-- macOS Traffic Lights Header --}}
            <div class="macos-card-bar" style="padding: 14px 20px; background: #f8fafc; border-bottom: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; gap: 7px; align-items: center;">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <span class="macos-card-title" style="font-size: 12px; font-weight: 600; color: #475569;">Chi tiết đặt phòng &amp; Hủy phòng</span>
                <span class="badge rounded-pill bg-slate-100 text-slate-600 border border-slate-200" style="font-size: 10.5px; font-weight: 600;">Royal Luxury</span>
            </div>

            <div style="padding: 28px 28px 32px;">
                {{-- Thông tin phòng đặt tóm tắt --}}
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; margin-bottom: 24px;">
                    <div class="row g-3">
                        <div class="col-6">
                            <span class="text-slate-400 d-block" style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">Phòng đã chọn</span>
                            <strong class="text-slate-900 mt-1 d-block" style="font-size: 14.5px;">
                                {{ $booking->rooms->map(fn($r) => 'Phòng '.$r->room_number)->join(', ') ?: 'Chưa gán số phòng' }}
                            </strong>
                        </div>
                        <div class="col-6">
                            <span class="text-slate-400 d-block" style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">Tổng chi phí</span>
                            <strong class="text-primary mt-1 d-block" style="font-size: 16px; font-weight: 800;">
                                {{ number_format($booking->total_price, 0, ',', '.') }} đ
                            </strong>
                        </div>
                        <div class="col-6">
                            <span class="text-slate-400 d-block" style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">Ngày nhận phòng</span>
                            <div class="text-slate-800 mt-1 fw-semibold" style="font-size: 13.5px;">
                                <i class="bi bi-calendar-event text-slate-400 me-1"></i>
                                {{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}
                            </div>
                        </div>
                        <div class="col-6">
                            <span class="text-slate-400 d-block" style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">Ngày trả phòng</span>
                            <div class="text-slate-800 mt-1 fw-semibold" style="font-size: 13.5px;">
                                <i class="bi bi-calendar2-check text-slate-400 me-1"></i>
                                {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Chính sách hoàn tiền --}}
                @if($isEligible)
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 18px; padding: 16px 18px; margin-bottom: 24px; display: flex; gap: 14px; align-items: flex-start;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: grid; place-items: center; font-size: 18px; flex-shrink: 0;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <strong style="display: block; font-size: 13.5px; color: #166534; font-weight: 700;">Đủ điều kiện hoàn tiền</strong>
                            <p style="margin: 4px 0 0; font-size: 12.5px; line-height: 1.5; color: #15803d;">
                                Bạn còn <strong>{{ $daysLeft }} ngày</strong> trong thời hạn hủy miễn phí (trước {{ $deadlineDays }} ngày nhận phòng).
                                Số tiền đã thu đủ điều kiện hoàn là <strong>{{ number_format($refundAmount, 0, ',', '.') }} đ</strong>. Lễ tân sẽ xác nhận sau khi thực hiện hoàn tiền qua cổng thanh toán.
                            </p>
                        </div>
                    </div>
                @else
                    <div style="background: #fefce8; border: 1px solid #fef08a; border-radius: 18px; padding: 16px 18px; margin-bottom: 24px; display: flex; gap: 14px; align-items: flex-start;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #fef9c3; color: #ca8a04; display: grid; place-items: center; font-size: 18px; flex-shrink: 0;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <strong style="display: block; font-size: 13.5px; color: #854d0e; font-weight: 700;">Không đủ điều kiện hoàn tiền</strong>
                            <p style="margin: 4px 0 0; font-size: 12.5px; line-height: 1.5; color: #a16207;">
                                Đã quá thời hạn hủy miễn phí (trước {{ $deadlineDays }} ngày nhận phòng). Hủy lúc này sẽ không được hoàn tiền theo chính sách khách sạn.
                                @if($isWeekendOrHoliday)
                                    <br><em>* Lưu ý: Ngày nhận phòng là cuối tuần hoặc ngày lễ, chính sách hủy không hoàn cọc được áp dụng.</em>
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Form hủy đặt phòng --}}
                <form action="{{ route('booking.cancel', $booking->id) }}" method="POST" id="cancelForm">
                    @csrf
                    <div class="mb-4">
                        <label for="cancelReason" class="form-label fw-bold text-slate-800" style="font-size: 13px;">
                            Lý do hủy đặt phòng <span class="text-muted fw-normal">(không bắt buộc)</span>
                        </label>
                        <textarea id="cancelReason" name="reason" class="form-control @error('reason') is-invalid @enderror"
                            rows="4" placeholder="Bạn có thể chia sẻ lý do nếu muốn..."
                            maxlength="500" style="border-radius: 16px; border: 1px solid #cbd5e1; padding: 14px 16px; font-size: 13.5px; line-height: 1.5; resize: none; background: #fafafa;">{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-3 justify-content-end align-items-center pt-2">
                        <a href="{{ route('booking.mine') }}" class="btn rounded-pill px-4 py-2.5 text-decoration-none"
                           style="background: #f1f5f9; color: #334155; font-size: 13px; font-weight: 600; border: 1px solid #cbd5e1; transition: all 0.2s ease;">
                            ← Giữ đặt phòng
                        </a>
                        <button type="button" class="btn rounded-pill px-4 py-2.5 text-white shadow-xs" id="cancelTriggerBtn"
                                style="background: #e11d48; border: none; font-size: 13px; font-weight: 600; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.3); transition: all 0.2s ease;">
                            <i class="bi bi-x-circle me-1.5"></i> Xác nhận hủy phòng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- Custom Confirmation Modal --}}
<div class="cancel-modal-backdrop" id="cancelModal" aria-hidden="true" role="dialog" aria-labelledby="cancelModalTitle" aria-modal="true">
    <div class="cancel-modal-card">
        <div class="cancel-modal-icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h3 id="cancelModalTitle" class="cancel-modal-title">Xác nhận hủy đặt phòng</h3>
        <p class="cancel-modal-message">Bạn chắc chắn muốn hủy đặt phòng này? Thao tác này sẽ giải phóng phòng và không thể hoàn tác.</p>
        <div class="cancel-modal-actions">
            <button type="button" class="cancel-modal-btn cancel-modal-btn--keep" id="cancelModalKeep">Giữ lại đặt phòng</button>
            <button type="button" class="cancel-modal-btn cancel-modal-btn--confirm" id="cancelModalConfirm">Đồng ý hủy</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.cancel-modal-backdrop {
    position: fixed; inset: 0; z-index: 9999;
    display: none; align-items: center; justify-content: center;
    background: rgba(15, 23, 42, 0.45);
    -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);
    opacity: 0;
    transition: opacity .25s ease;
}
.cancel-modal-backdrop.is-active { display: flex; opacity: 1; }
.cancel-modal-card {
    width: min(420px, calc(100% - 32px));
    padding: 32px 28px 26px;
    background: #ffffff; border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,.25), 0 0 0 1px rgba(0,0,0,.06);
    text-align: center;
    transform: scale(.95) translateY(8px);
    transition: transform .22s cubic-bezier(.16,1,.3,1), opacity .2s ease;
}
.cancel-modal-backdrop.is-active .cancel-modal-card { transform: scale(1) translateY(0); }
.cancel-modal-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 54px; height: 54px; margin: 0 auto 16px;
    border-radius: 50%;
    background: #fee2e2;
    color: #ef4444; font-size: 24px;
}
.cancel-modal-title { margin: 0 0 8px; font-size: 18px; font-weight: 700; color: #0f172a; }
.cancel-modal-message { margin: 0 0 24px; font-size: 13.5px; line-height: 1.55; color: #64748b; }
.cancel-modal-actions { display: flex; gap: 12px; justify-content: center; }
.cancel-modal-btn {
    padding: 10px 22px; border-radius: 999px;
    font-size: 13px; font-weight: 600;
    cursor: pointer; border: none;
    transition: all .2s ease;
}
.cancel-modal-btn:active { transform: scale(.97); }
.cancel-modal-btn--keep {
    background: #f1f5f9; color: #334155;
    border: 1px solid #cbd5e1;
}
.cancel-modal-btn--keep:hover { background: #e2e8f0; color: #0f172a; }
.cancel-modal-btn--confirm {
    background: #e11d48; color: #ffffff;
    box-shadow: 0 4px 14px rgba(225, 29, 72, .3);
}
.cancel-modal-btn--confirm:hover { background: #be123c; box-shadow: 0 6px 18px rgba(225, 29, 72, .4); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('cancelForm');
    const trigger = document.getElementById('cancelTriggerBtn');
    const modal = document.getElementById('cancelModal');
    const keepBtn = document.getElementById('cancelModalKeep');
    const confirmBtn = document.getElementById('cancelModalConfirm');

    function openModal() { modal.classList.add('is-active'); modal.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; }
    function closeModal() { modal.classList.remove('is-active'); modal.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }

    trigger.addEventListener('click', () => {
        if (!form.checkValidity()) { form.reportValidity(); return; }
        openModal();
    });
    keepBtn.addEventListener('click', closeModal);
    confirmBtn.addEventListener('click', () => { closeModal(); form.submit(); });
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('is-active')) closeModal(); });
});
</script>
@endpush
