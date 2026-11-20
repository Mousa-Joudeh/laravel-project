<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AvailabilityController;

Route::middleware(['auth'])->group(function () {

    Route::resource('availabilities', AvailabilityController::class);

});