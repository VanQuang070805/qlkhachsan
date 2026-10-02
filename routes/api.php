<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\IotRoomController;

Route::middleware('throttle:30,1')->prefix('iot/rooms')->name('iot.rooms.')->group(function () {
    Route::get('/{roomNumber}/cleaning-request', [IotRoomController::class, 'showCleaningRequest'])->name('cleaning-request.show');
    Route::post('/{roomNumber}/cleaning-request', [IotRoomController::class, 'updateCleaningRequest'])->name('cleaning-request.update');
});

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

// Dify may read public room information only. The endpoints use a separate bearer secret.
Route::middleware('throttle:120,1')->prefix('chatbot/tools')->name('chatbot.tools.')->group(function () {
    Route::post('/room-types', [ChatbotController::class, 'difyRoomTypes'])->name('room-types');
    Route::post('/rooms/search', [ChatbotController::class, 'difySearchRooms'])->name('rooms.search');
});
