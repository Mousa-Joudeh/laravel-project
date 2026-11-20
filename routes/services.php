<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceBookingController;

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::resource('services', ServiceController::class)
        ->except(['index', 'show']);

    Route::post('services/{service}/bookings', [ServiceBookingController::class, 'store'])
        ->name('services.bookings.store');
});

Route::resource('services', ServiceController::class)
    ->only(['index', 'show']);