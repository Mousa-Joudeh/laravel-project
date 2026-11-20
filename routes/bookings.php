<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;

Route::middleware(['auth'])->group(function () {

    Route::resource('bookings', BookingController::class);

});