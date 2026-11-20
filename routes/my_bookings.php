<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MyBookingController;

Route::middleware('auth')->group(function () {
    Route::get('/my-bookings', [MyBookingController::class, 'index'])->name('my-bookings.index');
    Route::get('/my-bookings/{id}', [MyBookingController::class, 'show'])->name('my-bookings.show');
    Route::patch('/my-bookings/{id}/cancel', [MyBookingController::class, 'cancel'])->name('my-bookings.cancel');
    Route::post('/my-bookings/{id}/review', [MyBookingController::class, 'review'])->name('my-bookings.review');
});