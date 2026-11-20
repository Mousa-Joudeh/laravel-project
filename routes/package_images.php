<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PackageImageController;

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::resource('package-images', PackageImageController::class)
        ->parameters(['package-images' => 'packageImage'])
        ->except(['index', 'show']);
});

Route::resource('package-images', PackageImageController::class)
    ->parameters(['package-images' => 'packageImage'])
    ->only(['index', 'show']);
