@extends('emails.layouts.rosaliza')

@section('title', 'Cảm ơn quý khách đã lưu trú tại Rosaliza Hotel')
@section('subtitle', 'Lời Tri Ân Từ Ban Quản Lý')

@section('content')
    <p style="margin:0 0 12px 0; font-size:16px; color:#141413; font-weight:600;">
        Kính gửi quý khách {{ $booking->customer_name }},
    </p>

    <p style="margin:0 0 16px 0; color:#44403C;">
        Rosaliza Hotel xin gửi lời cảm ơn chân thành vì quý khách đã tin tưởng lựa chọn chúng tôi cho kỳ lưu trú vừa qua
        (từ ngày <strong>{{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}</strong> đến ngày <strong>{{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</strong>, mã đơn <strong>#{{ $booking->id }}</strong>).
    </p>

    <p style="margin:0 0 20px 0; color:#44403C;">
        Trải nghiệm thực tế của quý khách là thước đo quý giá nhất giúp đội ngũ Rosaliza Hotel hoàn thiện chất lượng phục vụ mỗi ngày. Kính mời quý khách dành ít phút chia sẻ đánh giá cho hạng phòng đã lưu trú:
    </p>

    @foreach ($roomTypes as $type)
        <div style="background-color:#FAF9F6; border:1px solid #E6E1D6; border-radius:14px; padding:20px; margin:0 0 14px 0; text-align:center;">
            <p style="margin:0 0 6px 0; font-family:'Playfair Display', Georgia, serif; font-size:17px; font-weight:700; color:#141413;">
                {{ $type->type_name ?? $type->name }}
            </p>
            <p style="margin:0 0 14px 0; font-size:13px; color:#57534E;">
                Đánh giá trực tiếp từ kỳ nghỉ đã hoàn tất #{{ $booking->id }}
            </p>
            <a href="{{ route('reviews.create', ['booking_id' => $booking->id, 'room_type_id' => $type->id]) }}"
               style="display:inline-block; background-color:#141413; color:#FAF9F6; padding:11px 24px; text-decoration:none; border-radius:999px; font-weight:600; font-size:13px; border:1px solid #B89C72;">
                Gửi đánh giá kỳ lưu trú
            </a>
        </div>
    @endforeach

    <p style="margin:20px 0 18px 0; font-size:13.5px; color:#57534E;">
        Kính chúc quý khách cùng gia đình nhiều sức khỏe và mong sớm được đón tiếp quý khách trở lại tại 5B Trần Quốc Toản, Hoàn Kiếm, Hà Nội.
    </p>

    <p style="margin:0; font-size:13.5px; color:#44403C;">
        Trân trọng tri ân,<br>
        <strong style="color:#141413;">Ban Quản lý Rosaliza Hotel</strong>
    </p>
@endsection
