<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProviderDashboardController;

Route::middleware(['auth'])->prefix('provider')->name('provider.')->group(function () {

    Route::get('/dashboard', [ProviderDashboardController::class, 'index'])->name('dashboard');

});
