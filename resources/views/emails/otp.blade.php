@extends('emails.layouts.rosaliza')

@section('title', $subject ?? 'Mã xác thực OTP - Rosaliza Hotel')
@section('subtitle', 'Xác Thực Bảo Mật Tài Khoản')

@section('content')
    <p style="margin:0 0 12px 0; font-size:16px; color:#141413; font-weight:600;">
        Kính chào quý khách{{ !empty($recipientName) ? ' ' . $recipientName : '' }},
    </p>
    <p style="margin:0 0 20px 0; color:#44403C;">
        Hệ thống Rosaliza Hotel vừa nhận được yêu cầu xác thực cho địa chỉ email này. Vui lòng nhập mã OTP gồm 6 chữ số dưới đây để tiếp tục:
    </p>

    <div style="background-color:#FAF9F6; border:1px solid #D6CFC2; border-radius:14px; padding:24px 20px; text-align:center; margin:0 0 20px 0;">
        <p style="margin:0 0 8px 0; font-size:11.5px; text-transform:uppercase; letter-spacing:1.6px; color:#785D32; font-weight:700;">
            Mã Xác Thực Một Lần (OTP)
        </p>
        <p style="margin:0; font-family:'Courier New', Courier, monospace; font-size:32px; font-weight:700; letter-spacing:8px; color:#141413;">
            {{ $otp }}
        </p>
        <p style="margin:10px 0 0 0; font-size:12.5px; color:#57534E;">
            Mã có hiệu lực trong vòng <strong>10 phút</strong> kể từ thời điểm gửi.
        </p>
    </div>

    <p style="margin:0 0 18px 0; font-size:13px; color:#57534E;">
        Vì lý do an toàn, vui lòng không chia sẻ mã xác thực này cho bất kỳ ai, kể cả nhân viên khách sạn. Nếu quý khách không thực hiện yêu cầu này, xin vui lòng bỏ qua email.
    </p>

    <p style="margin:0; font-size:13.5px; color:#44403C;">
        Trân trọng,<br>
        <strong style="color:#141413;">Rosaliza Hotel Hà Nội</strong>
    </p>
@endsection
