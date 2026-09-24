<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cảm ơn quý khách và Đánh giá phòng tại Royal Hotel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #FAF8F3;
            color: #1A1A2E;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #E8E4D8;
            overflow: hidden;
        }
        .header {
            background-color: #1A1A2E;
            padding: 40px 20px;
            text-align: center;
            border-bottom: 3px solid #C9A84C;
        }
        .header h1 {
            color: #C9A84C;
            font-family: 'Georgia', serif;
            margin: 0;
            font-size: 26px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .header p {
            color: #FAF8F3;
            margin: 10px 0 0 0;
            font-size: 14px;
            letter-spacing: 1px;
        }
        .content {
            padding: 40px 30px;
            line-height: 1.6;
        }
        .content h2 {
            color: #1A1A2E;
            font-size: 20px;
            margin-top: 0;
        }
        .content p {
            font-size: 15px;
            color: #555555;
            margin-bottom: 30px;
        }
        .room-card {
            background-color: #FAF8F3;
            border: 1px solid #E8E4D8;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .room-name {
            font-weight: bold;
            font-size: 16px;
            color: #1A1A2E;
            margin-bottom: 15px;
        }
        .btn-review {
            display: inline-block;
            background-color: #1A1A2E;
            color: #ffffff !important;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            border: 1px solid #C9A84C;
            transition: all 0.2s ease;
        }
        .footer {
            background-color: #1A1A2E;
            color: #7A7A8A;
            padding: 25px;
            text-align: center;
            font-size: 12px;
            border-top: 1px solid #E8E4D8;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Royal Hotel</h1>
            <p>SANG TRỌNG - LỊCH LÃM - ĐẲNG CẤP</p>
        </div>
        <div class="content">
            <h2>Kính gửi quý khách {{ $booking->customer_name }},</h2>
            <p>
                Royal Hotel xin gửi lời cảm ơn chân thành nhất vì quý khách đã tin tưởng và lựa chọn dịch vụ của chúng tôi cho kỳ nghỉ vừa qua (từ ngày {{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }} đến ngày {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}).
            </p>
            <p>
                Để chúng tôi có thể liên tục cải thiện và mang đến chất lượng phục vụ hoàn hảo nhất, kính mong quý khách dành chút thời gian đánh giá chất lượng phòng đã sử dụng:
            </p>

            @foreach ($roomTypes as $type)
                <div class="room-card">
                    <div class="room-name">{{ $type->type_name }}</div>
                    <a href="{{ route('reviews.create', ['booking_id' => $booking->id, 'room_type_id' => $type->id]) }}" class="btn-review">
                        Đánh giá phòng này
                    </a>
                </div>
            @endforeach

            <p style="margin-top: 30px;">
                Ý kiến đóng góp của quý khách là niềm vinh hạnh lớn đối với chúng tôi. Kính chúc quý khách sức khỏe, hạnh phúc và hy vọng được đón tiếp quý khách lần sau tại Royal Hotel.
            </p>
            <p>
                Trân trọng,<br>
                <strong>Ban quản lý Royal Hotel</strong>
            </p>
        </div>
        <div class="footer">
            <p>Địa chỉ: Đường Cầu Giấy, Quận Cầu Giấy, Hà Nội</p>
            <p>Điện thoại: 090 000 0001 | Email: support@hotel.com</p>
            <p>&copy; {{ date('Y') }} Royal Hotel. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
