<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

// ============================================================
// WEBHOOK — Nhận callback từ cổng thanh toán
// Không cần CSRF (đã exclude trong VerifyCsrfToken.php)
// ============================================================

// MoMo gọi về sau khi thanh toán xong
Route::post('/webhook/momo',    [PaymentController::class, 'webhookMomo'])->name('webhook.momo');

// ZaloPay gọi về sau khi thanh toán xong
Route::post('/webhook/zalopay', [PaymentController::class, 'webhookZalopay'])->name('webhook.zalopay');

// VNPay IPN (Instant Payment Notification)
Route::post('/webhook/vnpay',   [PaymentController::class, 'webhookVnpay'])->name('webhook.vnpay');

// VNPay return URL (người dùng được redirect về sau khi thanh toán)
Route::get('/webhook/vnpay/return', [PaymentController::class, 'vnpayReturn'])->name('webhook.vnpay.return');