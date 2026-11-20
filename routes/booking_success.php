<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingSuccessController;

Route::middleware('auth')->group(function () {
    Route::get('/bookings/{id}/confirmed', [BookingSuccessController::class, 'show'])->name('bookings.success');
});