<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PackageController;

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::resource('packages', PackageController::class)
        ->except(['index', 'show']);

    Route::put('packages/{package}/services', [
        PackageController::class,
        'syncServices'
    ])->name('packages.services.sync');
});

Route::resource('packages', PackageController::class)
    ->only(['index', 'show']);
