<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\StudioimageController;

Route::get('studios/{studio}/continue-booking', function (string $studio) {
    return redirect()->route('studios.show', ['studio' => $studio]);
})->middleware('auth')->name('studios.booking.continue');

Route::resource('studios', StudioController::class);
Route::resource('studioimages', StudioimageController::class);