@extends('emails.layouts.rosaliza')

@section('title', 'Thông tin tài khoản nội bộ - Rosaliza Hotel')
@section('subtitle', 'Thông Báo Cấp Tài Khoản Vận Hành')

@section('content')
    <p style="margin:0 0 12px 0; font-size:16px; color:#141413; font-weight:600;">
        Xin chào {{ $fullname }},
    </p>
    <p style="margin:0 0 18px 0; color:#44403C;">
        Tài khoản vận hành nội bộ tại <strong>Rosaliza Hotel</strong> với vai trò <strong>{{ $roleLabel }}</strong> đã được khởi tạo trên hệ thống.
    </p>

    <div style="background-color:#FAF9F6; border:1px solid #E6E1D6; border-radius:14px; padding:18px 20px; margin:0 0 18px 0;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse; font-size:13.5px;">
            <tr>
                <td style="padding:7px 0; color:#57534E; border-bottom:1px solid #EFECE6;">Tên đăng nhập</td>
                <td style="padding:7px 0; text-align:right; font-weight:700; color:#141413; border-bottom:1px solid #EFECE6;">{{ $username }}</td>
            </tr>
            <tr>
                <td style="padding:7px 0; color:#57534E;">Vai trò được gán</td>
                <td style="padding:7px 0; text-align:right; font-weight:600; color:#785D32;">{{ $roleLabel }}</td>
            </tr>
        </table>
    </div>

    <p style="margin:0 0 18px 0; font-size:13px; color:#57534E;">
        Vì lý do bảo mật, mật khẩu không được đính kèm qua email. Vui lòng nhận mật khẩu ban đầu từ quản trị viên và chủ động đổi mật khẩu ngay sau lần đăng nhập đầu tiên.
    </p>

    <p style="margin:0; font-size:13.5px; color:#44403C;">
        Trân trọng,<br>
        <strong style="color:#141413;">Ban Quản trị Rosaliza Hotel</strong>
    </p>
@endsection
