@extends('emails.layouts.rosaliza')

@section('title', 'Thông báo cập nhật tài khoản - Rosaliza Hotel')
@section('subtitle', 'Cập Nhật Hồ Sơ Tài Khoản')

@section('content')
    <p style="margin:0 0 12px 0; font-size:16px; color:#141413; font-weight:600;">
        Xin chào {{ $fullname }},
    </p>
    <p style="margin:0 0 18px 0; color:#44403C;">
        Thông tin tài khoản của bạn tại <strong>Rosaliza Hotel</strong> vừa được cập nhật trên hệ thống. Chi tiết hồ sơ hiện tại như sau:
    </p>

    <div style="background-color:#FAF9F6; border:1px solid #E6E1D6; border-radius:14px; padding:18px 20px; margin:0 0 18px 0;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse; font-size:13.5px;">
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #EFECE6;">Họ và tên</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #EFECE6;">{{ $fullname }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #EFECE6;">Tên đăng nhập</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #EFECE6;">{{ $username }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #EFECE6;">Email</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #EFECE6;">{{ $email }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E; border-bottom:1px solid #EFECE6;">Số điện thoại</td>
                <td style="padding:8px 0; text-align:right; font-weight:600; color:#141413; border-bottom:1px solid #EFECE6;">{{ $phone ?: 'Chưa cập nhật' }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#57534E;">Vai trò</td>
                <td style="padding:8px 0; text-align:right; font-weight:700; color:#785D32;">
                    @if ($role === 'admin')
                        Quản trị viên
                    @elseif ($role === 'receptionist')
                        Lễ tân
                    @elseif ($role === 'customer')
                        Khách hàng
                    @else
                        {{ $role }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div style="background-color:#FAF6EE; border-left:3px solid #B89C72; padding:12px 16px; border-radius:8px; margin:0 0 18px 0; font-size:13px; color:#44403C;">
        <strong>Lưu ý bảo mật:</strong> Nếu bạn không nhận biết thay đổi này, vui lòng liên hệ quản trị viên Rosaliza Hotel qua số 024 3828 9999 để được kiểm tra ngay.
    </div>

    <p style="margin:0; font-size:13.5px; color:#44403C;">
        Trân trọng,<br>
        <strong style="color:#141413;">Ban Quản trị Rosaliza Hotel</strong>
    </p>
@endsection
