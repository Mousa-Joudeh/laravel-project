<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PackageSlotController;

Route::get('/packages/{id}/slots', [PackageSlotController::class, 'index'])->name('packages.slots');