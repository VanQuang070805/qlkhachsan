<?php

use App\Http\Controllers\Api\V1\PublicCatalogApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/public')->name('api.v1.public.')->group(function () {
    Route::get('/room-types', [PublicCatalogApiController::class, 'roomTypes'])->name('room-types.index');
    Route::get('/room-types/{roomType}', [PublicCatalogApiController::class, 'showRoomType'])->name('room-types.show');
    Route::get('/amenities', [PublicCatalogApiController::class, 'amenities'])->name('amenities.index');
    Route::get('/rooms/availability', [PublicCatalogApiController::class, 'availability'])->name('rooms.availability');
    Route::post('/quotes', [PublicCatalogApiController::class, 'quote'])->name('quotes.store');
});
