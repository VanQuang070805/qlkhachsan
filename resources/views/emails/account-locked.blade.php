<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>{{ $isLocked ? 'Tài khoản bị khóa' : 'Tài khoản được mở khóa' }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; margin:0;">
    <div style="max-width:600px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">

        @if ($isLocked)
            <div style="text-align:center; margin-bottom:20px;">
                <span style="font-size:48px;">🔒</span>
            </div>
            <h2 style="color:#dc2626; text-align:center; margin-top:0;">
                Tài khoản đã bị khóa
            </h2>
        @else
            <div style="text-align:center; margin-bottom:20px;">
                <span style="font-size:48px;">🔓</span>
            </div>
            <h2 style="color:#16a34a; text-align:center; margin-top:0;">
                Tài khoản đã được mở khóa
            </h2>
        @endif

        <p style="color:#374151;">Xin chào <strong>{{ $fullname }}</strong>,</p>

        @if ($isLocked)
            <p style="color:#374151;">
                Tài khoản của bạn tại <strong>{{ config('app.name') }}</strong>
                đã bị quản trị viên <strong>khóa</strong>.
                Bạn sẽ không thể đăng nhập cho đến khi tài khoản được mở khóa trở lại.
            </p>

            <div style="background:#fef2f2; padding:16px; border-radius:8px; border:1px dashed #dc2626; margin:20px 0;">
                <p style="margin:0; color:#dc2626;">
                    <strong>⚠️ Lưu ý:</strong> Nếu bạn cho rằng đây là nhầm lẫn,
                    vui lòng liên hệ quản trị viên để được hỗ trợ.
                </p>
            </div>
        @else
            <p style="color:#374151;">
                Tài khoản của bạn tại <strong>{{ config('app.name') }}</strong>
                đã được quản trị viên <strong>mở khóa</strong>.
                Bạn có thể đăng nhập và sử dụng dịch vụ bình thường.
            </p>

            <div style="background:#f0fdf4; padding:16px; border-radius:8px; border:1px dashed #16a34a; margin:20px 0;">
                <p style="margin:0; color:#16a34a;">
                    <strong>✅ Thông báo:</strong> Tài khoản của bạn hiện đang hoạt động bình thường.
                </p>
            </div>
        @endif

        <hr style="border:none; border-top:1px solid #e5e7eb; margin:20px 0;">
        <p style="color:#9ca3af; font-size:12px; text-align:center; margin-bottom:0;">
            Trân trọng,<br>
            <strong>{{ config('app.name') }}</strong>
        </p>
    </div>
</body>
</html>