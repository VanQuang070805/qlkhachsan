@extends('emails.layouts.rosaliza')

@section('title', ($isLocked ? 'Thông báo tạm khóa tài khoản' : 'Thông báo mở khóa tài khoản') . ' - Rosaliza Hotel')
@section('subtitle', 'Thông Báo Trạng Thái Tài Khoản')

@section('content')
    <p style="margin:0 0 12px 0; font-size:16px; color:#141413; font-weight:600;">
        Xin chào {{ $fullname }},
    </p>

    @if ($isLocked)
        <p style="margin:0 0 18px 0; color:#44403C;">
            Tài khoản của bạn tại <strong>Rosaliza Hotel</strong> hiện đã được chuyển sang trạng thái <strong>tạm khóa</strong> bởi quản trị viên hệ thống. Các phiên đăng nhập hiện hành đã được thu hồi để đảm bảo an toàn.
        </p>

        <div style="background-color:#FEF2F2; border-left:3px solid #B91C1C; padding:14px 16px; border-radius:8px; margin:0 0 18px 0; font-size:13px; color:#7F1D1D;">
            <strong>Hướng dẫn hỗ trợ:</strong> Nếu bạn cần kiểm tra nguyên nhân hoặc yêu cầu kích hoạt lại tài khoản, vui lòng liên hệ bộ phận quản trị hoặc lễ tân Rosaliza Hotel qua hotline 024 3828 9999.
        </div>
    @else
        <p style="margin:0 0 18px 0; color:#44403C;">
            Tài khoản của bạn tại <strong>Rosaliza Hotel</strong> đã được quản trị viên <strong>mở khóa và kích hoạt trở lại</strong>. Bạn có thể đăng nhập và sử dụng dịch vụ bình thường.
        </p>

        <div style="background-color:#F0FDF4; border-left:3px solid #15803D; padding:14px 16px; border-radius:8px; margin:0 0 18px 0; font-size:13px; color:#14532D;">
            <strong>Trạng thái hiện tại:</strong> Tài khoản đang hoạt động hợp lệ.
        </div>
    @endif

    <p style="margin:0; font-size:13.5px; color:#44403C;">
        Trân trọng,<br>
        <strong style="color:#141413;">Ban Quản trị Rosaliza Hotel</strong>
    </p>
@endsection
