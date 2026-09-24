@extends('layouts.main')

@section('content')
<?php $pageTitle = 'Đặt Phòng Thành Công'; ?>

<div class="text-center py-5">
    <i class="bi bi-check-circle-fill text-success" style="font-size:5rem"></i>
    <h2 class="fw-bold mt-3">Đặt Phòng Thành Công!</h2>
    <p class="text-muted">Mã đặt phòng: <strong class="text-primary">#<?= $booking['id'] ?></strong></p>
</div>

<div class="card border-0 shadow-sm mx-auto" style="max-width:560px">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Chi Tiết Đặt Phòng</h5>
    </div>
    <div class="card-body p-4">
        <table class="table table-borderless mb-0">
            <tr>
                <td class="text-muted w-50">Khách hàng</td>
                <td class="fw-semibold"><?= htmlspecialchars($booking['customer_name']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Email</td>
                <td><?= htmlspecialchars($booking['customer_email']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Điện thoại</td>
                <td><?= htmlspecialchars($booking['customer_phone']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Phòng</td>
                <td class="fw-bold">
                    Phòng <?= htmlspecialchars($booking['room_number'] ?? '–') ?>
                    (<?= htmlspecialchars($booking['type_name'] ?? '') ?>)
                </td>
            </tr>
            <tr>
                <td class="text-muted">Tầng</td>
                <td><?= $booking['floor'] ?? '–' ?></td>
            </tr>
            <tr>
                <td class="text-muted">Nhận phòng</td>
                <td><?= $booking['check_in'] ?></td>
            </tr>
            <tr>
                <td class="text-muted">Trả phòng</td>
                <td><?= $booking['check_out'] ?></td>
            </tr>
            <tr>
                <td class="text-muted">Số khách</td>
                <td><?= $booking['adult_count'] ?> người lớn, <?= $booking['child_count'] ?> trẻ em</td>
            </tr>
            <tr>
                <td class="text-muted">Tổng tiền</td>
                <td class="fw-bold text-primary fs-5">
                    <?= number_format($booking['total_price'], 0, ',', '.') ?> VNĐ
                </td>
            </tr>
            <tr>
                <td class="text-muted">Trạng thái</td>
                <td>
                    <span class="badge bg-warning text-dark">
                        <?= $booking['payment_status'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán' ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
    <a href="{{ route('payment.show', $booking->id) }}" class="btn btn-success">
        <i class="bi bi-credit-card me-1"></i>Thanh Toán Ngay
    </a>
    <a href="{{ route('home') }}" class="btn btn-outline-secondary">
        Về Trang Chủ
    </a>
</div>
@endsection
