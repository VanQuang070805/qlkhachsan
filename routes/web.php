<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CancellationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InternalAuthController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\RecceiptionUsserController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\GoogleAuthController;

// ============================================================
// PUBLIC — Không cần đăng nhập
// ============================================================
Route::get('/',           [HomeController::class, 'index'])->name('home');
Route::get('/contact',    [HomeController::class, 'contact'])->name('contact');
Route::redirect('/about', '/contact', 301)->name('about');
Route::get('/payment/momo/return', [PaymentController::class, 'momoReturn'])->name('payment.momo.return');
Route::get('/preview/payment/{bookingId}', [PaymentController::class, 'preview'])->name('payment.preview');
Route::post('/chatbot/api', [ChatbotController::class, 'api'])->middleware('throttle:30,1')->name('chatbot.api');
Route::post('/chatbot/stream', [ChatbotController::class, 'stream'])->middleware('throttle:30,1')->name('chatbot.stream');

// Phòng
Route::get('/rooms',               [RoomController::class, 'index'])->name('rooms.index');
Route::get('/rooms/search',        [RoomController::class, 'search'])->name('rooms.search');
Route::get('/rooms/{id}',          [RoomController::class, 'detail'])->name('rooms.detail');
Route::get('/rooms/{id}/amenities',[RoomController::class, 'amenities'])->name('rooms.amenities');
Route::redirect('/amenities', '/rooms', 301)->name('amenities');

// ============================================================
// AUTH — Chỉ dành cho khách chưa đăng nhập
// ============================================================
Route::middleware('guest')->group(function () {
    Route::get('/login',                  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',                 [AuthController::class, 'login'])->middleware('throttle:8,1');
    Route::get('/register',               [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',              [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/verify',                 [AuthController::class, 'showVerify'])->name('verify');
    Route::post('/verify',                [AuthController::class, 'verify'])->middleware('throttle:8,1');
    Route::get('/verify/resend',          [AuthController::class, 'resendVerifyOtp'])->middleware('throttle:3,1')->name('verify.resend');
    Route::get('/auth/google',             [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback',    [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// Khách đã đăng nhập vẫn có thể xác minh email để đổi mật khẩu.
Route::get('/forgot-password',        [AuthController::class, 'showForgot'])->name('password.forgot');
Route::post('/forgot-password',       [AuthController::class, 'sendReset'])->middleware('throttle:5,1');
Route::get('/forgot-password/resend', [AuthController::class, 'resendResetOtp'])->middleware('throttle:3,1')->name('password.resend-otp');
Route::get('/reset-password',         [AuthController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password',        [AuthController::class, 'reset'])->middleware('throttle:5,1');
Route::get('/verify-reset-otp',       [AuthController::class, 'showVerifyOtp'])->name('password.verify-otp');
Route::post('/verify-reset-otp',      [AuthController::class, 'verifyOtp'])->middleware('throttle:8,1');

// Internal Auth (Nhân viên)
Route::get('/internalauth/login',   [InternalAuthController::class, 'showLogin'])->name('internalauth.login');
Route::post('/internalauth/login',  [InternalAuthController::class, 'login'])->middleware('throttle:internal-login');
Route::post('/internalauth/logout', [InternalAuthController::class, 'logout'])->name('internalauth.logout');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ============================================================
// CUSTOMER — Cần đăng nhập + đã verify
// ============================================================
Route::middleware(['auth.custom', 'verified.custom'])->group(function () {

    // Booking
    Route::get('/booking/create',       [BookingController::class, 'create'])->name('booking.create');
    Route::post('/booking',             [BookingController::class, 'store'])->name('booking.store');
    Route::get('/booking/success/{id}', [BookingController::class, 'success'])->name('booking.success');
    Route::get('/my-bookings',          [BookingController::class, 'myBookings'])->name('booking.mine');
    Route::get('/account',               [AuthController::class, 'account'])->name('account.show');
    Route::patch('/account',             [AuthController::class, 'updateAccount'])->name('account.update');

    // Thanh toán
    Route::get('/payment/{bookingId}/form',    [PaymentController::class, 'form'])->name('payment.form');
    Route::get('/payment/{bookingId}',         [PaymentController::class, 'show'])->name('payment.show');
    Route::get('/payment/success/{bookingId}', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/error/{bookingId}',   [PaymentController::class, 'error'])->name('payment.error');
    Route::patch('/payment/{bookingId}',       [PaymentController::class, 'updateMethod'])->name('payment.update');
    Route::get('/payment/check/{bookingId}',   [PaymentController::class, 'checkStatus'])->name('payment.check');

    // Hủy phòng
    Route::get('/booking/{id}/cancel',  [CancellationController::class, 'show'])->name('booking.cancel.show');
    Route::post('/booking/{id}/cancel', [CancellationController::class, 'cancel'])->name('booking.cancel');

    // Đánh giá phòng
    Route::get('/reviews/create', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/reviews',       [ReviewController::class, 'store'])->name('reviews.store');
});

// ============================================================
// RECEPTIONIST — Profile
// ============================================================
use App\Http\Controllers\ReceptionUserController;

Route::middleware(['auth.custom', 'role:receptionist,admin'])->prefix('receptionist')->name('receptionist.')->group(function () {
    Route::get('/profile',                  [ReceptionUserController::class, 'profile'])->name('profile');
    Route::post('/profile/update-info',     [ReceptionUserController::class, 'updateInfo'])->name('profile.update-info');
    Route::post('/profile/update-password', [ReceptionUserController::class, 'updatePassword'])->name('profile.update-password');
});
// ============================================================
// RECEPTIONIST + ADMIN — Quản lý đặt phòng
// ============================================================
Route::middleware(['auth.custom', 'role:receptionist,admin'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/bookings',                           [ReceptionController::class, 'index'])->name('bookings');
    Route::patch('/bookings/{id}/confirm',            [BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::patch('/bookings/{id}/checkin',            [BookingController::class, 'checkIn'])->name('bookings.checkin');
    Route::patch('/bookings/{id}/checkout',           [BookingController::class, 'checkOut'])->name('bookings.checkout');
    Route::patch('/bookings/{id}/refund',             [CancellationController::class, 'processRefund'])->name('bookings.refund');
    Route::get('/bookings/{id}/vietqr',               [PaymentController::class, 'staffVietQR'])->name('bookings.vietqr');
    Route::get('/bookings/{id}/check-status',         [PaymentController::class, 'staffCheckStatus'])->name('bookings.check-status');
    Route::get('/bookings/{id}/checkout-success',     [PaymentController::class, 'checkoutSuccess'])->name('bookings.checkout-success');
    Route::post('/bookings/{id}/extend',              [BookingController::class, 'extendStay'])->name('bookings.extend');

    // AJAX — sơ đồ phòng
    Route::get('/room/{id}/current-booking',          [BookingController::class, 'currentBooking'])->name('room.currentBooking');
    Route::post('/room/{id}/checkin',                 [BookingController::class, 'checkInRoom'])->name('room.checkin');
    Route::post('/room/{id}/status',                  [BookingController::class, 'updateRoomStatus'])->name('room.status');
    Route::post('/bookings/{id}/checkout',            [BookingController::class, 'checkOutRoom'])->name('bookings.checkout-room');
    Route::post('/bookings/{id}/checkout-payment',    [PaymentController::class, 'staffCheckoutPayment'])->name('bookings.checkout-payment');

    // API sơ đồ phòng lễ tân
    Route::get('/cancellations', [ReceptionController::class, 'cancellations'])->name('cancellations');
    Route::get('/reception/today-booking',            [ReceptionController::class, 'getTodayBooking'])->name('reception.today-booking');
    Route::get('/reception/booking-by-scan',          [ReceptionController::class, 'getBookingByScan'])->name('reception.booking-by-scan');
    Route::post('/reception/walkin',                  [ReceptionController::class, 'walkinCheckin'])->name('reception.walkin');
    Route::post('/reception/extend',                  [ReceptionController::class, 'extendStay'])->name('reception.extend');
    Route::post('/reception/update-status',           [ReceptionController::class, 'updateStatus'])->name('reception.update-status');
    Route::post('/reception/quick-checkin',           [ReceptionController::class, 'quickCheckinMultipleRooms'])->name('reception.quick-checkin');
});

// ============================================================
// ADMIN
// ============================================================
Route::middleware(['auth.custom', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard & Báo cáo
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/reports',   [AdminController::class, 'reports'])->name('reports');

    // Cài đặt giá
    Route::get('/price-settings',           [AdminController::class, 'priceSettings'])->name('price-settings.index');
    Route::get('/price-settings/create',    [AdminController::class, 'priceSettingsCreate'])->name('price-settings.create');
    Route::post('/price-settings',          [AdminController::class, 'priceSettingsStore'])->name('price-settings.store');
    Route::get('/price-settings/{id}/edit', [AdminController::class, 'priceSettingsEdit'])->name('price-settings.edit');
    Route::put('/price-settings/{id}',      [AdminController::class, 'priceSettingsUpdate'])->name('price-settings.update');
    Route::delete('/price-settings/{id}',   [AdminController::class, 'priceSettingsDelete'])->name('price-settings.delete');

    // Quản lý người dùng
    Route::resource('users', UserController::class)->except(['show']);
    Route::patch('users/{user}/toggle-verified', [UserController::class, 'toggleVerified'])
         ->name('users.toggle-verified');
});
