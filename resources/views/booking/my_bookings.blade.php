@extends('layouts.main')

@section('title', 'Kỳ nghỉ của tôi · Royal Hotel')

@section('content')
<section class="commerce-page account-bookings" aria-labelledby="bookings-title">
    <header class="commerce-heading" data-reveal>
        <p class="editorial-eyebrow">Your account</p>
        <h1 id="bookings-title">Your stays</h1>
        <p>Theo dõi thời gian lưu trú, thanh toán và trạng thái trong một nơi.</p>
        <nav class="account-switcher" aria-label="Khu vực tài khoản"><a class="is-active" href="{{ route('booking.mine') }}">Kỳ nghỉ</a><a href="{{ route('account.show') }}">Thông tin cá nhân</a></nav>
    </header>

    @if($bookings->isEmpty())
    <div class="account-empty" data-reveal><i class="bi bi-calendar2-heart" aria-hidden="true"></i><h2>No stays yet</h2><p>Khám phá một căn phòng vừa vặn cho hành trình tiếp theo.</p><a href="{{ route('rooms.index') }}" class="button">Khám phá phòng</a></div>
    @else
    <div class="booking-list">
        @foreach($bookings as $booking)
        @php
            $statusLabels = ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','checked_in'=>'Đang lưu trú','cancelled'=>'Đã hủy','rejected'=>'Từ chối','completed'=>'Hoàn thành'];
            $roomNames = $booking->rooms->map(fn($room) => 'Phòng '.$room->room_number)->join(', ');
        @endphp
        <article class="booking-list-card" data-reveal>
            <div class="booking-list-card__identity"><span>#{{ $booking->id }}</span><h2>{{ $roomNames ?: 'Royal Hotel' }}</h2><p>{{ $booking->rooms->first()?->roomType?->type_name ?? 'Hạng phòng Royal' }}</p></div>
            <div class="booking-list-card__dates"><div><small>Nhận phòng</small><strong>{{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}</strong><span>{{ \Carbon\Carbon::parse($booking->check_in)->format('H:i') }}</span></div><i class="bi bi-arrow-right" aria-hidden="true"></i><div><small>Trả phòng</small><strong>{{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</strong><span>{{ \Carbon\Carbon::parse($booking->check_out)->format('H:i') }}</span></div></div>
            <div class="booking-list-card__meta"><div><small>Số khách</small><strong>{{ $booking->adult_count }} NL · {{ $booking->child_count }} TE</strong></div><div><small>Tổng tiền</small><strong>{{ number_format($booking->total_price,0,',','.') }} ₫</strong></div></div>
            <div class="booking-list-card__status"><span class="status-pill status-pill--{{ $booking->status }}">{{ $booking->status === 'pending' && $booking->payment_status !== 'paid' ? 'Chờ thanh toán' : ($statusLabels[$booking->status] ?? 'Đang xử lý') }}</span></div>
            @if($booking->payment_status !== 'paid' && $booking->status !== 'cancelled')<a href="{{ route('payment.form',$booking->id) }}" class="booking-pay">Tiếp tục thanh toán <span aria-hidden="true">→</span></a>@endif
            @if(in_array($booking->status,['pending','confirmed']))<a href="{{ route('booking.cancel.show',$booking->id) }}" class="booking-cancel">Yêu cầu hủy <span aria-hidden="true">→</span></a>@endif
            @if($booking->status === 'completed' && $booking->rooms->first()?->room_type_id)<a href="{{ route('reviews.create',['booking_id'=>$booking->id,'room_type_id'=>$booking->rooms->first()->room_type_id]) }}" class="booking-pay">Đánh giá kỳ nghỉ <span aria-hidden="true">→</span></a>@endif
        </article>
        @endforeach
    </div>
    {{ $bookings->links('pagination.royal') }}
    @endif
</section>
@endsection
