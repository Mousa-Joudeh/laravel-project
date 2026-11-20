<?php

use App\Http\Controllers\Provider\AIController;
use App\Http\Controllers\Provider\AvailabilityController;
use App\Http\Controllers\Provider\BookingController;
use App\Http\Controllers\Provider\PackageController;
use App\Http\Controllers\Provider\PackageImageController;
use App\Http\Controllers\Provider\ProfileController;
use App\Http\Controllers\Provider\ReviewController;
use App\Http\Controllers\Provider\ServiceController;
use App\Http\Controllers\Provider\StudioController;
use App\Http\Controllers\Provider\StudioImageController;
use App\Http\Controllers\ProviderDashboardController;
use App\Http\Middleware\EnsureProvider;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureProvider::class])
    ->prefix('provider')
    ->name('provider.')
    ->group(function () {

        Route::get('/dashboard', [ProviderDashboardController::class, 'index'])->name('dashboard');

        // Booking Management
        Route::resource('bookings', BookingController::class)->only(['index', 'show', 'update']);

        // Space Management (+ its photos)
        Route::resource('studios', StudioController::class);
        Route::delete('studio-images/{image}', [StudioImageController::class, 'destroy'])->name('studio-images.destroy');
        Route::patch('studio-images/{image}/cover', [StudioImageController::class, 'cover'])->name('studio-images.cover');

        // Service Management
        Route::resource('services', ServiceController::class);

        // Package Management (+ its photos)
        Route::resource('packages', PackageController::class);
        Route::delete('package-images/{image}', [PackageImageController::class, 'destroy'])->name('package-images.destroy');

        // Opening hours and closed periods
        Route::get('availabilities', [AvailabilityController::class, 'index'])->name('availabilities.index');
        Route::post('availabilities', [AvailabilityController::class, 'store'])->name('availabilities.store');
        Route::delete('availabilities/{availability}', [AvailabilityController::class, 'destroy'])->name('availabilities.destroy');

        // Reviews & Ratings
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');

        // Profile Management
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        // "Write with AI" for descriptions
        Route::post('ai/describe', [AIController::class, 'describe'])->middleware('throttle:20,1')->name('ai.describe');
    });
