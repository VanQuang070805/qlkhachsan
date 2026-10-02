@php
    $logoPath = public_path('aura-logo-white.png');
    $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cảm ơn quý khách và Đánh giá phòng tại Posh Boutique</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f6f5f2;
            color: #1e293b;
            margin: 0;
            padding: 24px 12px;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #e7e5df;
        }
        .email-header {
            background: linear-gradient(180deg, #0b0c10 0%, #141722 100%);
            color: #ffffff;
            padding: 34px 24px 30px;
            text-align: center;
            border-bottom: 2px solid #d4af37;
        }
        .header-logo {
            display: block;
            margin: 0 auto 10px auto;
            max-width: 105px;
            height: auto;
        }
        .hotel-name {
            margin: 0;
            font-family: 'Georgia', serif;
            font-size: 22px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #ffffff;
            font-weight: 600;
        }
        .header-sub {
            margin: 8px 0 0;
            font-size: 11px;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #e2e8f0;
            font-weight: 500;
        }
        .email-content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 12px;
            color: #0f172a;
            font-weight: 600;
        }
        .intro-text {
            color: #475569;
            font-size: 14px;
            line-height: 1.65;
            margin-bottom: 18px;
        }
        .stay-summary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 18px;
            margin-bottom: 22px;
            font-size: 13.5px;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .room-review-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 14px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }
        .room-type-name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .stars-row {
            color: #f59e0b;
            font-size: 16px;
            margin-bottom: 12px;
            letter-spacing: 3px;
        }
        .btn-review {
            display: inline-block;
            background-color: #0071e3;
            color: #ffffff !important;
            padding: 9px 22px;
            text-decoration: none;
            border-radius: 999px;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(0, 113, 227, 0.22);
            transition: all 0.2s ease;
        }
        .closing-text {
            font-size: 13.5px;
            color: #64748b;
            line-height: 1.6;
            margin-top: 24px;
        }
        .signoff {
            margin-top: 16px;
            font-size: 13.5px;
            color: #334155;
            line-height: 1.5;
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 22px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            line-height: 1.6;
        }
        .email-footer p {
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            @if($logoBase64)
                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Posh Boutique Logo" class="header-logo" width="105">
            @endif
            <h1 class="hotel-name">POSH BOUTIQUE</h1>
            <p class="header-sub">Lời Tri Ân Từ Ban Quản Lý</p>
        </div>
        <div class="email-content">
            <div class="greeting">Kính gửi quý khách <strong>{{ $booking->customer_name }}</strong>,</div>

            <p class="intro-text">
                Posh Boutique xin gửi lời cảm ơn chân thành và sâu sắc nhất vì quý khách đã tin tưởng lựa chọn chúng tôi cho kỳ nghỉ vừa qua
                (từ ngày <strong>{{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}</strong> đến <strong>{{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</strong>).
            </p>
            <p class="intro-text">
                Được đồng hành và mang đến cho quý khách những phút giây thư thái, riêng tư và trọn vẹn là niềm vinh hạnh lớn của toàn thể đội ngũ chúng tôi. Kính mong quý khách dành chút thời gian chia sẻ cảm nhận về trải nghiệm phòng nghỉ của mình:
            </p>

            @foreach ($roomTypes as $type)
                <div class="room-review-card">
                    <div class="room-type-name">{{ $type->type_name ?? $type->name }}</div>
                    <div class="stars-row">★★★★★</div>
                    <a href="{{ route('reviews.create', ['booking_id' => $booking->id, 'room_type_id' => $type->id]) }}" class="btn-review">
                        Chia sẻ cảm nhận &amp; Đánh giá phòng →
                    </a>
                </div>
            @endforeach

            <p class="closing-text">
                Mỗi ý kiến đóng góp của quý khách là nguồn động lực quý báu giúp Posh Boutique trau chuốt và nâng tầm dịch vụ từng ngày. Kính chúc quý khách và gia đình luôn dồi dào sức khỏe, thành công và hy vọng sớm được đón tiếp quý khách trở lại.
            </p>

            <div class="signoff">
                Trân trọng tri ân,<br>
                <strong style="color: #0f172a;">Ban Quản lý Posh Boutique</strong>
            </div>
        </div>
        <div class="email-footer">
            <p><strong>Posh Boutique</strong> — Đường Cầu Giấy, Quận Cầu Giấy, Hà Nội</p>
            <p>Hotline: 024 3828 9999 | Email: contact@poshboutique.vn</p>
            <p style="font-size: 11px; color: #94a3b8; margin-top: 8px;">&copy; {{ date('Y') }} Posh Boutique. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
