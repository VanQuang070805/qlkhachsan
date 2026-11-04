<?php

use App\Http\Controllers\Api\V1\CustomerPortalApiController;
use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/customer')->name('api.v1.customer.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [CustomerPortalApiController::class, 'register'])->name('register');
        Route::post('/login', [CustomerPortalApiController::class, 'login'])->middleware('throttle:customer-login')->name('login');
        Route::post('/logout', [CustomerPortalApiController::class, 'logout'])->name('logout');
        Route::post('/verify-email', [CustomerPortalApiController::class, 'verifyEmail'])->name('verify-email');
        Route::post('/resend-otp', [CustomerPortalApiController::class, 'resendOtp'])->name('resend-otp');
        Route::post('/password/forgot', [CustomerPortalApiController::class, 'forgotPassword'])->middleware('throttle:password-reset')->name('password.forgot');
        Route::post('/password/reset', [CustomerPortalApiController::class, 'resetPassword'])->middleware('throttle:password-reset')->name('password.reset');
    });

    Route::get('/me', [CustomerPortalApiController::class, 'me'])->name('me.show');
    Route::patch('/me', [CustomerPortalApiController::class, 'updateMe'])->name('me.update');

    Route::middleware('idempotency')->group(function () {
        Route::get('/bookings', [CustomerPortalApiController::class, 'indexBookings'])->name('bookings.index');
        Route::post('/bookings', [CustomerPortalApiController::class, 'storeBooking'])->name('bookings.store');
        Route::get('/bookings/{booking}', [CustomerPortalApiController::class, 'showBooking'])->name('bookings.show');
        Route::post('/bookings/{booking}/cancellations', [CustomerPortalApiController::class, 'cancelBooking'])->name('bookings.cancel');
        Route::post('/bookings/{booking}/extensions', [CustomerPortalApiController::class, 'extendBooking'])->name('bookings.extend');
        Route::post('/bookings/{booking}/payments', [CustomerPortalApiController::class, 'initiatePayment'])->name('bookings.payments.store');
        Route::get('/bookings/{booking}/payments', [CustomerPortalApiController::class, 'listPayments'])->name('bookings.payments.index');
        Route::post('/bookings/{booking}/reviews', [CustomerPortalApiController::class, 'storeReview'])->name('bookings.reviews.store');
        Route::patch('/reviews/{review}', [CustomerPortalApiController::class, 'updateReview'])->name('reviews.update');
    });

    Route::middleware('throttle:chatbot')->prefix('chat')->name('chat.')->group(function () {
        Route::post('/threads', [ChatbotController::class, 'createThread'])->name('threads.store');
        Route::delete('/threads/{thread}', [ChatbotController::class, 'deleteThread'])->name('threads.destroy');
        Route::post('/threads/{thread}/messages', [ChatbotController::class, 'postThreadMessage'])->name('threads.messages.store');
        Route::get('/runs/{run}', [ChatbotController::class, 'showRun'])->name('runs.show');
        Route::get('/runs/{run}/events', [ChatbotController::class, 'streamRunEvents'])->name('runs.events');
    });
});
