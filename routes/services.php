<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::resource('services', ServiceController::class)
        ->except(['index', 'show']);
});

Route::resource('services', ServiceController::class)
    ->only(['index', 'show']);
