<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin tài khoản lễ tân</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; margin:0;">
    <div style="max-width:600px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">

        <h2 style="color:#1e3a8a; text-align:center; margin-top:0;">
            Thông tin tài khoản lễ tân
        </h2>

        <p style="color:#374151;">Xin chào <strong>{{ $fullname }}</strong>,</p>
        <p style="color:#374151;">Tài khoản lễ tân của bạn đã được tạo. Dưới đây là thông tin đăng nhập:</p>

        <div style="background:#eff6ff; padding:20px; border-radius:8px; margin:20px 0; border:1px dashed #1d4ed8;">
            <p style="margin:8px 0; color:#1e3a8a;">
                <strong>Tên đăng nhập:</strong> {{ $username }}
            </p>
            <p style="margin:8px 0; color:#1e3a8a;">
                <strong>Mật khẩu:</strong> {{ $plainPassword }}
            </p>
        </div>

        <p style="color:#6b7280; font-size:13px;">
            Vui lòng đăng nhập và đổi mật khẩu sau khi nhận được email này.
        </p>

        <hr style="border:none; border-top:1px solid #e5e7eb; margin:20px 0;">
        <p style="color:#9ca3af; font-size:12px; text-align:center; margin-bottom:0;">
            Trân trọng,<br>
            <strong>{{ config('app.name') }}</strong>
        </p>
    </div>
</body>
</html>