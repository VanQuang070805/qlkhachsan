<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks/v1/payments')->name('webhooks.v1.payments.')->group(function () {
    Route::post('/momo', [PaymentController::class, 'webhookMomo'])->name('momo');
    Route::post('/zalopay', [PaymentController::class, 'webhookZalopay'])->name('zalopay');
    Route::post('/vnpay', [PaymentController::class, 'webhookVnpay'])->name('vnpay');
});
