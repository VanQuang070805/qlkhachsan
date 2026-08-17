<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông báo cập nhật tài khoản</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; margin:0;">
    <div style="max-width:600px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">

        <h2 style="color:#1e3a8a; text-align:center; margin-top:0;">
            Thông báo cập nhật tài khoản
        </h2>

        <p style="color:#374151;">Xin chào <strong>{{ $fullname }}</strong>,</p>
        <p style="color:#374151;">
            Thông tin tài khoản của bạn vừa được quản trị viên cập nhật. Dưới đây là thông tin hiện tại:
        </p>

        <div style="background:#eff6ff; padding:20px; border-radius:8px; margin:20px 0; border:1px dashed #1d4ed8;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="padding:8px 0; color:#6b7280; width:40%;">Họ và tên</td>
                    <td style="padding:8px 0; color:#1e3a8a; font-weight:bold;">{{ $fullname }}</td>
                </tr>
                <tr style="border-top:1px solid #e5e7eb;">
                    <td style="padding:8px 0; color:#6b7280;">Tên đăng nhập</td>
                    <td style="padding:8px 0; color:#1e3a8a; font-weight:bold;">{{ $username }}</td>
                </tr>
                <tr style="border-top:1px solid #e5e7eb;">
                    <td style="padding:8px 0; color:#6b7280;">Email</td>
                    <td style="padding:8px 0; color:#1e3a8a; font-weight:bold;">{{ $email }}</td>
                </tr>
                <tr style="border-top:1px solid #e5e7eb;">
                    <td style="padding:8px 0; color:#6b7280;">Số điện thoại</td>
                    <td style="padding:8px 0; color:#1e3a8a; font-weight:bold;">{{ $phone ?? '—' }}</td>
                </tr>
                <tr style="border-top:1px solid #e5e7eb;">
                    <td style="padding:8px 0; color:#6b7280;">Vai trò</td>
                    <td style="padding:8px 0; color:#1e3a8a; font-weight:bold;">
                        @if ($role === 'admin') Quản trị viên
                        @elseif ($role === 'receptionist') Lễ tân
                        @else Khách hàng
                        @endif
                    </td>
                </tr>
                <tr style="border-top:1px solid #e5e7eb;">
                    <td style="padding:8px 0; color:#6b7280;">Mật khẩu</td>
                    <td style="padding:8px 0; color:#1e3a8a; font-weight:bold;">
                        {{ isset($plainPassword) ? $plainPassword : '(không thay đổi)' }}
                    </td>
                </tr>
            </table>
        </div>

        <div style="background:#fff7ed; padding:16px; border-radius:8px; border:1px dashed #f97316; margin:20px 0;">
            <p style="margin:0; color:#c2410c;">
                <strong>⚠️ Lưu ý:</strong> Nếu bạn không yêu cầu thay đổi này, vui lòng liên hệ quản trị viên ngay lập tức.
            </p>
        </div>

        <hr style="border:none; border-top:1px solid #e5e7eb; margin:20px 0;">
        <p style="color:#9ca3af; font-size:12px; text-align:center; margin-bottom:0;">
            Trân trọng,<br>
            <strong>{{ config('app.name') }}</strong>
        </p>
    </div>
</body>
</html>