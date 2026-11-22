<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PackageController;

Route::get('packages/{package}/continue-booking', function (string $package) {
    return redirect()->route('packages.show', ['package' => $package]);
})->middleware('auth')->name('packages.booking.continue');

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