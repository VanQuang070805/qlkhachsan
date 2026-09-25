@extends('layouts.main')

@section('title', 'Đánh giá kỳ nghỉ · Royal Hotel')

@push('styles')
<style>
.review-page{width:min(100% - 32px,680px);margin:clamp(32px,7vw,78px) auto}.review-card{overflow:hidden;border:1px solid rgba(96,96,108,.12);border-radius:22px;background:#fff;box-shadow:0 24px 70px rgba(16,55,132,.09)}.review-head{padding:30px clamp(22px,5vw,38px);background:linear-gradient(145deg,#779bc1,#9abfda 58%,#cbdcec);color:#fff}.review-head h1{margin:0 0 8px;font-size:clamp(2rem,6vw,3.4rem);line-height:.98;letter-spacing:-.045em}.review-head p{max-width:46ch;margin:0;color:rgba(255,255,255,.84)}.review-body{padding:clamp(22px,5vw,38px)}.review-stay{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:28px}.review-stay div{padding:14px;border-radius:14px;background:#f5f7f9}.review-stay small{display:block;color:#818993;font-size:.7rem}.review-stay strong{display:block;margin-top:3px;color:#171719;font-size:.85rem}.review-label{display:block;margin:0 0 10px;color:#272a2f;font-size:.82rem;font-weight:650}.star-rating{display:flex;justify-content:center;gap:9px;margin:8px 0 28px}.star-item{border:0;padding:4px;background:transparent;color:#dce2e7;font-size:2.25rem;line-height:1;cursor:pointer;transition:color .16s,transform .16s}.star-item.is-selected{color:#2597d0}.star-item:hover,.star-item:focus-visible{transform:translateY(-2px);outline:0}.comment-textarea{width:100%;min-height:120px;max-height:320px;resize:vertical;padding:15px 16px;border:1px solid #dfe4e8;border-radius:14px;background:#fff;color:#171719;font:400 .9rem/1.55 Inter,sans-serif}.comment-textarea:focus{outline:0;border-color:#171719;box-shadow:0 0 0 4px rgba(215,230,245,.8)}.comment-textarea::-webkit-resizer{background:linear-gradient(135deg,transparent 48%,#89a8bf 50% 57%,transparent 59% 65%,#89a8bf 67% 74%,transparent 76%)}.review-help{display:block;margin-top:7px;color:#858d96;font-size:.7rem}.review-submit{width:100%;min-height:48px;margin-top:20px;border:0;border-radius:999px;background:#070709;color:#fff;font-weight:650;box-shadow:0 12px 26px rgba(7,7,9,.15)}@media(max-width:560px){.review-stay{grid-template-columns:1fr}.review-page{width:min(100% - 24px,680px)}}@media(prefers-reduced-motion:reduce){.star-item{transition:none}}
</style>
@endpush

@section('content')
<main class="review-page" data-reveal>
    <article class="review-card">
        <header class="review-head"><p>Guest note</p><h1>Share your stay</h1><p>Một ghi chú ngắn giúp Royal chăm chút tốt hơn cho những kỳ nghỉ tiếp theo.</p></header>
        <div class="review-body">
            <section class="review-stay" aria-label="Thông tin kỳ nghỉ">
                <div><small>Hạng phòng</small><strong>{{ $roomType->type_name }}</strong></div>
                <div><small>Thời gian</small><strong>{{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</strong></div>
                <div><small>Mã đặt phòng</small><strong>#{{ $booking->id }}</strong></div>
            </section>
            <form action="{{ route('reviews.store') }}" method="POST">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $booking->id }}"><input type="hidden" name="room_type_id" value="{{ $roomType->id }}"><input type="hidden" name="rating" id="ratingValue" value="{{ old('rating',5) }}">
                <span class="review-label" id="rating-label">Mức độ hài lòng <span aria-hidden="true">*</span></span>
                <div class="star-rating" role="radiogroup" aria-labelledby="rating-label">
                    @for($star=1;$star<=5;$star++)<button type="button" class="bi bi-star-fill star-item" data-value="{{ $star }}" role="radio" aria-checked="{{ (int)old('rating',5)===$star ? 'true':'false' }}" aria-label="{{ $star }} sao"></button>@endfor
                </div>
                <label class="review-label" for="comment">Chia sẻ thêm <span style="font-weight:400;color:#8b8b8b">(không bắt buộc)</span></label>
                <textarea name="comment" id="comment" class="comment-textarea @error('comment') is-invalid @enderror" maxlength="1000" aria-describedby="comment-help @error('comment') comment-error @enderror" placeholder="Điều gì khiến kỳ nghỉ của bạn đáng nhớ?">{{ old('comment') }}</textarea>
                <small class="review-help" id="comment-help">Kéo tay nắm ở góc dưới bên phải để thay đổi chiều cao.</small>
                @error('comment')<p class="field-error" id="comment-error" role="alert">{{ $message }}</p>@enderror
                <button type="submit" class="review-submit">Gửi đánh giá</button>
            </form>
        </div>
    </article>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{const input=document.getElementById('ratingValue');const stars=[...document.querySelectorAll('.star-item')];const select=value=>{input.value=value;stars.forEach(star=>{const selected=Number(star.dataset.value)<=value;star.classList.toggle('is-selected',selected);star.setAttribute('aria-checked',String(Number(star.dataset.value)===value));});};stars.forEach(star=>{star.addEventListener('click',()=>select(Number(star.dataset.value)));star.addEventListener('keydown',event=>{if(!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(event.key))return;event.preventDefault();const delta=['ArrowRight','ArrowUp'].includes(event.key)?1:-1;const next=Math.max(1,Math.min(5,Number(star.dataset.value)+delta));select(next);stars[next-1].focus();});});select(Number(input.value));});
</script>
@endpush
