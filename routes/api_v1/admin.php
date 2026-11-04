<?php

use App\Http\Controllers\Api\V1\AdminPortalApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')->name('api.v1.admin.')->middleware('idempotency')->group(function () {
    Route::get('/me', [AdminPortalApiController::class, 'me'])->name('me.show');
    Route::get('/dashboard', [AdminPortalApiController::class, 'dashboard'])->name('dashboard');
    Route::get('/reports', [AdminPortalApiController::class, 'reports'])->name('reports');
    Route::get('/rooms', [AdminPortalApiController::class, 'rooms'])->name('rooms.index');
    Route::post('/rooms', [AdminPortalApiController::class, 'storeRoom'])->name('rooms.store');
    Route::patch('/rooms/{room}', [AdminPortalApiController::class, 'updateRoom'])->name('rooms.update');
    Route::get('/room-types', [AdminPortalApiController::class, 'roomTypes'])->name('room-types.index');
    Route::post('/room-types', [AdminPortalApiController::class, 'storeRoomType'])->name('room-types.store');
    Route::patch('/room-types/{roomType}', [AdminPortalApiController::class, 'updateRoomType'])->name('room-types.update');
    Route::get('/amenities', [AdminPortalApiController::class, 'amenities'])->name('amenities.index');
    Route::post('/amenities', [AdminPortalApiController::class, 'storeAmenity'])->name('amenities.store');
    Route::patch('/amenities/{amenity}', [AdminPortalApiController::class, 'updateAmenity'])->name('amenities.update');
    Route::get('/price-settings', [AdminPortalApiController::class, 'priceSettings'])->name('price-settings.index');
    Route::post('/price-settings', [AdminPortalApiController::class, 'storePriceSetting'])->name('price-settings.store');
    Route::patch('/price-settings/{priceSetting}', [AdminPortalApiController::class, 'updatePriceSetting'])->name('price-settings.update');
    Route::get('/internal-users', [AdminPortalApiController::class, 'internalUsers'])->name('internal-users.index');
    Route::post('/internal-users', [AdminPortalApiController::class, 'storeInternalUser'])->name('internal-users.store');
    Route::patch('/internal-users/{user}', [AdminPortalApiController::class, 'updateInternalUser'])->name('internal-users.update');
    Route::get('/roles', [AdminPortalApiController::class, 'roles'])->name('roles.index');
    Route::patch('/roles/{role}/permissions', [AdminPortalApiController::class, 'updateRolePermissions'])->name('roles.permissions.update');
    Route::patch('/internal-users/{user}/permissions', [AdminPortalApiController::class, 'updateUserPermissions'])->name('internal-users.permissions.update');
});
