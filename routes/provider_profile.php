<?php

use App\Http\Controllers\ProviderProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/studio-companies', [ProviderProfileController::class, 'publicIndex'])
    ->name('studio-companies.index');

Route::get('/studio-companies/{slug}', [ProviderProfileController::class, 'publicShow'])
    ->name('studio-companies.show');

Route::middleware('auth')->group(function () {
    Route::resource('provider-profiles', ProviderProfileController::class)
        ->parameters(['provider-profiles' => 'providerProfile']);

    Route::patch('/admin/provider-profiles/{providerProfile}/review', [
        ProviderProfileController::class,
        'review',
    ])->name('admin.provider-profiles.review');
});