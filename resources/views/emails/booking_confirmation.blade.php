@extends('emails.layouts.rosaliza')

@section('title', 'Xác nhận đặt phòng #' . $booking->id . ' - ' . $hotel)
@section('subtitle', 'Xác Nhận Đặt Cọc & Mã Nhận Phòng QR')

@section('header_logo')
    @if ($hasLogo)
        <img src="cid:royal-hotel-logo" alt="Rosaliza Hotel Logo" width="96" style="display:block; margin:0 auto 12px auto; max-width:96px; height:auto;">
    @endif
@endsection

@section('content')
    <p style="margin:0 0 12px 0; font-size:16px; color:#141413; font-weight:600;">
        Kính gửi quý khách {{ $booking->customer_name }},
    </p>
    <p style="margin:0 0 22px 0; color:#44403C;">
        {{ $hotel }} trân trọng xác nhận đơn đặt phòng <strong>#{{ $booking->id }}</strong> của quý khách đã được ghi nhận thành công. Khoản đặt cọc trực tuyến 50% đã được thanh toán an toàn. Phần chi phí còn lại sẽ được thanh toán tại quầy lễ tân khi kết thúc kỳ lưu trú.
    </p>

    <div style="background-color:#FAF9F6; border:1px solid #E6E1D6; border-radius:14px; padding:22px 20px; text-align:center; margin:0 0 22px 0;">
        <span style="display:inline-block; background-color:#141413; color:#FAF9F6; font-size:11px; font-weight:700; padding:5px 14px; border-radius:999px; text-transform:uppercase; letter-spacing:1px;">
            Thủ Tục Nhận Phòng Nhanh
        </span>
        <h2 style="margin:10px 0 6px 0; font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size:18px; font-weight:600; color:#141413;">
            Mã QR Nhận Phòng Tại Lễ Tân
        </h2>
        @if ($hasQr)
            <div style="margin:14px 0 10px 0;">
                <img src="cid:checkin-qr" width="210" height="210" alt="Mã QR nhận phòng đơn #{{ $booking->id }}" style="display:inline-block; border-radius:12px; border:1px solid #D6D3CD; padding:10px; background-color:#FFFFFF;">
            </div>
        @endif
        <p style="margin:8px 0 0 0; font-size:13px; color:#57534E; line-height:1.55;">
            Quý khách vui lòng xuất trình mã QR này tại quầy lễ tân để đối chiếu và nhận phòng nhanh chóng.
        </p>
    </div>

    <div style="background-color:#FFFFFF; border:1px solid #E6E1D6; border-radius:14px; padding:18px 20px; margin:0 0 18px 0;">
        <h3 style="margin:0 0 12px 0; font-size:12px; font-weight:700; color:#785D32; text-transform:uppercase; letter-spacing:1.2px; border-bottom:1px solid #EFECE6; padding-bottom:8px;">
            Thông Tin Kỳ Lưu Trú
        </h3>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse; font-size:13.5px;">
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #F5F2EB;">Mã đặt phòng</td>
                <td style="padding:8px 0; text-align:right; font-weight:700; color:#141413; border-bottom:1px solid #F5F2EB;">#{{ $booking->id }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #F5F2EB;">Khách lưu trú</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #F5F2EB;">
                    {{ (int) $booking->adult_count }} người lớn{{ (int) $booking->child_count > 0 ? ', ' . (int) $booking->child_count . ' trẻ em' : '' }}
                </td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #F5F2EB;">Nhận phòng</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #F5F2EB;">{{ $checkInFormatted }} (14:00 - trước 18:00)</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #F5F2EB;">Trả phòng</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #F5F2EB;">{{ $checkOutFormatted }} (trước 12:00)</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #F5F2EB;">Cổng thanh toán cọc</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #F5F2EB;">{{ $methodLabel }}</td>
            </tr>
            @if ($transId !== '')
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #F5F2EB;">Mã giao dịch</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #F5F2EB;">{{ $transId }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding:8px 0; color:#57534E;">Trạng thái đơn</td>
                <td style="padding:8px 0; text-align:right; font-weight:700; color:#166534;">Đã xác nhận đặt cọc</td>
            </tr>
        </table>
    </div>

    <div style="background-color:#FFFFFF; border:1px solid #E6E1D6; border-radius:14px; padding:18px 20px; margin:0 0 18px 0;">
        <h3 style="margin:0 0 12px 0; font-size:12px; font-weight:700; color:#785D32; text-transform:uppercase; letter-spacing:1.2px; border-bottom:1px solid #EFECE6; padding-bottom:8px;">
            Danh Sách Phòng Đã Đặt
        </h3>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse; font-size:13.5px;">
            @foreach ($rooms as $room)
                <tr>
                    <td style="padding:8px 0; color:#1C1917; {{ !$loop->last ? 'border-bottom:1px solid #F5F2EB;' : '' }}">
                        Phòng <strong>{{ $room->room_number }}</strong> (Tầng {{ $room->floor }})
                    </td>
                    <td style="padding:8px 0; text-align:right; color:#57534E; {{ !$loop->last ? 'border-bottom:1px solid #F5F2EB;' : '' }}">
                        <strong style="color:#141413;">{{ $room->roomType?->type_name ?? 'Hạng phòng tiêu chuẩn' }}</strong>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <div style="background-color:#FFFFFF; border:1px solid #E6E1D6; border-radius:14px; padding:18px 20px; margin:0 0 18px 0;">
        <h3 style="margin:0 0 12px 0; font-size:12px; font-weight:700; color:#785D32; text-transform:uppercase; letter-spacing:1.2px; border-bottom:1px solid #EFECE6; padding-bottom:8px;">
            Chi Tiết Thanh Toán Đồng Bộ Hệ Thống
        </h3>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse; font-size:13.5px;">
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #F5F2EB;">Tổng tiền phòng ({{ $nights }} đêm)</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #F5F2EB;">{{ $totalFormatted }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #F5F2EB;">Tiền cọc trực tuyến đã thu</td>
                <td style="padding:8px 0; text-align:right; font-weight:700; color:#166534; border-bottom:1px solid #F5F2EB;">{{ $depositFormatted }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E;">Số dư còn lại thanh toán tại quầy</td>
                <td style="padding:8px 0; text-align:right; font-weight:700; color:#141413; font-size:14.5px;">{{ $remainingFormatted }}</td>
            </tr>
        </table>
    </div>

    <div style="background-color:#141413; color:#FAF9F6; padding:18px 20px; border-radius:14px; text-align:center; margin:0 0 18px 0; border:1px solid #292524;">
        <p style="margin:0; font-size:11px; color:#D4BC94; text-transform:uppercase; letter-spacing:1.4px; font-weight:600;">
            Số Tiền Đặt Cọc Đã Xác Nhận
        </p>
        <p style="margin:6px 0 0 0; font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size:26px; font-weight:500; color:#FAF9F6;">
            {{ $depositFormatted }}
        </p>
    </div>

    <div style="background-color:#FAF6EE; border-left:3px solid #B89C72; padding:14px 16px; border-radius:8px; margin:0 0 22px 0; font-size:13px; color:#44403C; line-height:1.6;">
        <strong>Lưu ý nhận phòng và hoàn hủy:</strong><br>
        &bull; Khung giờ nhận phòng tiêu chuẩn từ <strong>14:00 đến trước 18:00</strong> ngày {{ $checkInFormatted }}. Sau 18:00 nếu quý khách chưa làm thủ tục nhận phòng, hệ thống sẽ tự động hủy đơn.<br>
        &bull; Đơn đặt phòng này đủ điều kiện hoàn cọc khi hủy trước ngày nhận phòng tối thiểu <strong>{{ $refundDeadlineDays }} ngày</strong>.
    </div>

    <p style="margin:0; font-size:13.5px; color:#44403C;">
        Trân trọng đón tiếp quý khách,<br>
        <strong style="color:#141413;">Ban Quản lý {{ $hotel }}</strong>
    </p>
@endsection
