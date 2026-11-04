<?php

use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:chatbot-internal-tools')
    ->prefix('internal/v1/chatbot/tools')
    ->name('internal.v1.chatbot.tools.')
    ->group(function () {
        Route::match(['get', 'post'], '/room-types', [ChatbotController::class, 'roomTypes'])->name('room-types');
        Route::post('/rooms/search', [ChatbotController::class, 'searchRooms'])->name('rooms.search');
        Route::post('/my-bookings', [ChatbotController::class, 'myBookings'])->name('my-bookings');
    });
