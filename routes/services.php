<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceBookingController;

Route::get('services/{service}/continue-booking', function (string $service) {
    return redirect()->route('services.show', ['service' => $service]);
})->middleware('auth')->name('services.booking.continue');

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::resource('services', ServiceController::class)
        ->except(['index', 'show']);

    Route::post('services/{service}/bookings', [ServiceBookingController::class, 'store'])
        ->name('services.bookings.store');
});

Route::resource('services', ServiceController::class)
    ->only(['index', 'show']);