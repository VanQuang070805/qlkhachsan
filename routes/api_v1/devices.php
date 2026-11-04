<?php

use App\Http\Controllers\Api\V1\DeviceApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:30,1')
    ->prefix('devices/v1/rooms')
    ->name('devices.v1.rooms.')
    ->group(function () {
        Route::get('/{roomNumber}/cleaning-request', [DeviceApiController::class, 'showCleaningRequest'])->name('cleaning-request.show');
        Route::post('/{roomNumber}/cleaning-request', [DeviceApiController::class, 'storeCleaningRequest'])->name('cleaning-request.store');
    });
