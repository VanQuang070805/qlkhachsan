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
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\RecceiptionUsserController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\FaceIdController;

// ============================================================
// PUBLIC — Không cần đăng nhập
// ============================================================
Route::get('/',           [HomeController::class, 'index'])->name('home');
Route::get('/contact',    [HomeController::class, 'contact'])->name('contact');
Route::redirect('/about', '/contact', 301)->name('about');
Route::get('/payment/momo/return', [PaymentController::class, 'momoReturn'])->name('payment.momo.return');
Route::post('/chatbot/api', [ChatbotController::class, 'api'])->middleware('throttle:chatbot')->name('chatbot.api');
Route::post('/chatbot/stream', [ChatbotController::class, 'stream'])->middleware('throttle:chatbot')->name('chatbot.stream');

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
    Route::post('/login',                 [AuthController::class, 'login'])->middleware('throttle:customer-login');
    Route::get('/register',               [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',              [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/verify',                 [AuthController::class, 'showVerify'])->name('verify');
    Route::post('/verify',                [AuthController::class, 'verify'])->middleware('throttle:8,1');
    Route::post('/verify/resend',         [AuthController::class, 'resendVerifyOtp'])->middleware('throttle:3,1')->name('verify.resend');
    Route::get('/auth/google',             [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback',    [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// Khách đã đăng nhập vẫn có thể xác minh email để đổi mật khẩu.
Route::get('/forgot-password',        [AuthController::class, 'showForgot'])->name('password.forgot');
Route::post('/forgot-password',       [AuthController::class, 'sendReset'])->middleware('throttle:password-reset');
Route::post('/forgot-password/resend',[AuthController::class, 'resendResetOtp'])->middleware('throttle:3,1')->name('password.resend-otp');
Route::get('/reset-password',         [AuthController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password',        [AuthController::class, 'reset'])->middleware('throttle:password-reset');
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
    Route::post('/booking/{booking}/extend', [BookingController::class, 'extendCustomerStay'])
        ->name('booking.extend');
    Route::patch('/booking/{booking}/rooms/{room}/cleaning-request', [BookingController::class, 'toggleCleaningRequest'])
        ->name('booking.cleaning-request');
    Route::get('/account',               [AuthController::class, 'account'])->name('account.show');
    Route::patch('/account',             [AuthController::class, 'updateAccount'])->name('account.update');

    // Thanh toán
    Route::get('/payment/{bookingId}/form',    [PaymentController::class, 'form'])->name('payment.form');
    Route::get('/preview/payment/{bookingId}', [PaymentController::class, 'preview'])->name('payment.preview');
    Route::get('/payment/{bookingId}',         [PaymentController::class, 'show'])->name('payment.show');
    Route::get('/payment/success/{bookingId}', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/error/{bookingId}',   [PaymentController::class, 'error'])->name('payment.error');
    Route::patch('/payment/{bookingId}',       [PaymentController::class, 'updateMethod'])->name('payment.update');
    Route::get('/payment/check/{bookingId}',   [PaymentController::class, 'checkStatus'])->name('payment.check');
    Route::post('/payment/{bookingId}/keep-alive', [PaymentController::class, 'keepVietQrAlive'])->name('payment.keep-alive');
    Route::post('/payment/{bookingId}/cancel-pending', [PaymentController::class, 'cancelPendingPayment'])->name('payment.cancel-pending');

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
    Route::get('/iot', [FaceIdController::class, 'index'])->name('iot');
    Route::post('/face-id/sessions', [FaceIdController::class, 'createSession'])->middleware('throttle:10,1')->name('face-id.sessions.create');
    Route::post('/face-id/sessions/{sessionId}/samples', [FaceIdController::class, 'addSample'])->middleware('throttle:30,1')->name('face-id.samples.store');
    Route::delete('/face-id/sessions/{sessionId}', [FaceIdController::class, 'cancelSession'])->name('face-id.sessions.cancel');
    Route::post('/face-id/recognize', [FaceIdController::class, 'recognize'])->middleware('throttle:12,1')->name('face-id.recognize');
    Route::get('/face-id/profiles', [FaceIdController::class, 'profiles'])->name('face-id.profiles.index');
    Route::patch('/face-id/profiles/{profile}', [FaceIdController::class, 'updateProfile'])->name('face-id.profiles.update');
    Route::delete('/face-id/profiles/{profile}', [FaceIdController::class, 'deleteProfile'])->name('face-id.profiles.delete');
    Route::get('/face-id/health', [FaceIdController::class, 'health'])->middleware('throttle:30,1')->name('face-id.health');
    Route::post('/face-id/sync', [FaceIdController::class, 'sync'])->middleware('throttle:5,1')->name('face-id.sync');
    Route::post('/face-id/full-sync', [FaceIdController::class, 'fullSync'])->middleware('throttle:2,1')->name('face-id.full-sync');

    Route::get('/bookings', [ReceptionController::class, 'index'])->middleware('permission:bp_view_schedule')->name('bookings');
    Route::patch('/bookings/{id}/confirm', [BookingController::class, 'confirm'])->middleware('permission:bp_create_booking')->name('bookings.confirm');
    Route::patch('/bookings/{id}/checkin', [BookingController::class, 'checkIn'])->middleware('permission:bp_checkin_checkout')->name('bookings.checkin');
    Route::patch('/bookings/{id}/checkout', [BookingController::class, 'checkOut'])->middleware(['permission:bp_checkin_checkout', 'permission:tn_collect_payment'])->name('bookings.checkout');
    Route::patch('/bookings/{id}/refund', [CancellationController::class, 'processRefund'])->middleware('permission:tn_refund')->name('bookings.refund');
    Route::get('/bookings/{id}/vietqr', [PaymentController::class, 'staffVietQR'])
        ->middleware(['permission:bp_checkin_checkout', 'permission:tn_collect_payment'])
        ->name('bookings.vietqr');
    Route::get('/bookings/{id}/check-status', [PaymentController::class, 'staffCheckStatus'])
        ->middleware(['permission:bp_checkin_checkout', 'permission:tn_collect_payment'])
        ->name('bookings.check-status');
    Route::get('/bookings/{id}/checkout-success', [PaymentController::class, 'checkoutSuccess'])
        ->middleware(['permission:bp_checkin_checkout', 'permission:tn_collect_payment'])
        ->name('bookings.checkout-success');
    Route::post('/bookings/{id}/extend', [BookingController::class, 'extendStay'])->middleware('permission:bp_extend_stay')->name('bookings.extend');

    // AJAX — sơ đồ phòng
    Route::get('/room/{id}/current-booking', [BookingController::class, 'currentBooking'])->middleware('permission:bp_view_schedule')->name('room.currentBooking');
    Route::post('/room/{id}/checkin', [BookingController::class, 'checkInRoom'])->middleware('permission:bp_checkin_checkout')->name('room.checkin');
    Route::post('/room/{id}/status', [BookingController::class, 'updateRoomStatus'])->middleware('permission:bp_clean_status')->name('room.status');
    Route::post('/room/{roomId}/cleaning-request/complete', [ReceptionController::class, 'completeCleaningRequest'])->middleware('permission:bp_clean_status')->name('room.cleaning.complete');
    Route::post('/bookings/{id}/checkout', [BookingController::class, 'checkOutRoom'])->middleware(['permission:bp_checkin_checkout', 'permission:tn_collect_payment'])->name('bookings.checkout-room');
    Route::post('/bookings/{id}/checkout-payment', [PaymentController::class, 'staffCheckoutPayment'])->middleware(['permission:bp_checkin_checkout', 'permission:tn_collect_payment'])->name('bookings.checkout-payment');

    // API sơ đồ phòng lễ tân
    Route::get('/cancellations', [ReceptionController::class, 'cancellations'])->middleware('permission:tn_refund')->name('cancellations');
    Route::get('/reception/today-booking', [ReceptionController::class, 'getTodayBooking'])->middleware('permission:bp_view_schedule')->name('reception.today-booking');
    Route::get('/reception/booking-by-scan', [ReceptionController::class, 'getBookingByScan'])->middleware('permission:bp_checkin_checkout')->name('reception.booking-by-scan');
    Route::post('/reception/walkin', [ReceptionController::class, 'walkinCheckin'])->middleware('permission:bp_create_booking')->name('reception.walkin');
    Route::post('/reception/extend', [ReceptionController::class, 'extendStay'])->middleware('permission:bp_extend_stay')->name('reception.extend');
    Route::post('/reception/update-status', [ReceptionController::class, 'updateStatus'])->name('reception.update-status');
    Route::post('/reception/quick-checkin', [ReceptionController::class, 'quickCheckinMultipleRooms'])->middleware('permission:bp_checkin_checkout')->name('reception.quick-checkin');
    Route::get('/cleaning-notifications', [ReceptionController::class, 'cleaningNotifications'])->middleware('permission:bp_clean_status')->name('cleaning-notifications');
});

// ============================================================
// ADMIN
// ============================================================
Route::middleware(['auth.custom', 'permission:rp_view_reports'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
});

Route::middleware(['auth.custom', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Cài đặt giá
    Route::get('/rooms', [AdminRoomController::class, 'index'])->name('rooms.index');
    Route::post('/rooms', [AdminRoomController::class, 'storeRoom'])->name('rooms.store');
    Route::patch('/rooms/{room}', [AdminRoomController::class, 'updateRoom'])->name('rooms.update');
    Route::post('/room-types', [AdminRoomController::class, 'storeRoomType'])->name('room-types.store');
    Route::patch('/room-types/{roomType}', [AdminRoomController::class, 'updateRoomType'])->name('room-types.update');
    Route::get('/price-settings',           [AdminController::class, 'priceSettings'])->name('price-settings.index');
    Route::get('/price-settings/create',    [AdminController::class, 'priceSettingsCreate'])->name('price-settings.create');
    Route::post('/price-settings',          [AdminController::class, 'priceSettingsStore'])->name('price-settings.store');
    Route::get('/price-settings/{id}/edit', [AdminController::class, 'priceSettingsEdit'])->name('price-settings.edit');
    Route::put('/price-settings/{id}',      [AdminController::class, 'priceSettingsUpdate'])->name('price-settings.update');
    Route::delete('/price-settings/{id}',   [AdminController::class, 'priceSettingsDelete'])->name('price-settings.delete');
    Route::patch('/room-types/{roomType}/price', [AdminController::class, 'updateRoomTypePrice'])
        ->name('room-types.price.update');

    // Quản lý người dùng & Phân quyền (RBAC)
    Route::resource('users', UserController::class)->except(['show']);
    Route::patch('users/{user}/toggle-verified', [UserController::class, 'toggleVerified'])
         ->name('users.toggle-verified');
    Route::get('/roles', [AdminController::class, 'roles'])->name('roles.index');
    Route::get('/roles/permissions', [AdminController::class, 'rolePermissions'])->name('roles.permissions.index');
    Route::post('/roles/profiles', [AdminController::class, 'storeRoleProfile'])->name('roles.profiles.store');
    Route::patch('/roles/{role}/permissions', [AdminController::class, 'updateRolePermissions'])->name('roles.role-permissions.update');
    Route::patch('/roles/{user}/user-permissions', [AdminController::class, 'updateUserPermissions'])->name('roles.user-permissions.update');
    Route::patch('/roles/{user}/profile', [AdminController::class, 'assignRoleProfile'])->name('roles.profile.assign');
});
