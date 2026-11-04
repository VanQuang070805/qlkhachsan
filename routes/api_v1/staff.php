<?php

use App\Http\Controllers\Api\V1\StaffPortalApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/staff')->name('api.v1.staff.')->middleware('idempotency')->group(function () {
    Route::get('/me', [StaffPortalApiController::class, 'me'])->name('me.show');
    Route::get('/bookings', [StaffPortalApiController::class, 'bookings'])->name('bookings.index');
    Route::get('/bookings/{booking}', [StaffPortalApiController::class, 'showBooking'])->name('bookings.show');
    Route::post('/bookings', [StaffPortalApiController::class, 'storeBooking'])->name('bookings.store');
    Route::post('/bookings/{booking}/confirmations', [StaffPortalApiController::class, 'confirmBooking'])->name('bookings.confirm');
    Route::post('/bookings/{booking}/check-ins', [StaffPortalApiController::class, 'checkIn'])->name('bookings.check-in');
    Route::post('/bookings/{booking}/check-outs', [StaffPortalApiController::class, 'checkOut'])->name('bookings.check-out');
    Route::post('/bookings/{booking}/extensions', [StaffPortalApiController::class, 'extendStay'])->name('bookings.extend');
    Route::post('/bookings/{booking}/checkout-payments', [StaffPortalApiController::class, 'checkoutPayment'])->name('bookings.checkout-payment');
    Route::post('/bookings/{booking}/refunds', [StaffPortalApiController::class, 'refundBooking'])->name('bookings.refund');
    Route::get('/rooms', [StaffPortalApiController::class, 'rooms'])->name('rooms.index');
    Route::get('/rooms/{room}/current-booking', [StaffPortalApiController::class, 'currentBookingForRoom'])->name('rooms.current-booking');
    Route::post('/rooms/{room}/cleaning-requests/completions', [StaffPortalApiController::class, 'completeCleaning'])->name('rooms.cleaning.complete');
    Route::get('/notifications/cleaning', [StaffPortalApiController::class, 'cleaningNotifications'])->name('notifications.cleaning');
    Route::post('/check-in-scans/resolve', [StaffPortalApiController::class, 'resolveCheckInScan'])->name('scans.resolve');
});
