<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PackageSlotController;

Route::get('/studios/{id}/slots', [PackageSlotController::class, 'index'])->name('studios.slots');