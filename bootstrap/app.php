<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
        Route::middleware('web')->group(base_path('routes/bookings.php'));
        Route::middleware('web')->group(base_path('routes/reviews.php'));
        Route::middleware('web')->group(base_path('routes/availabilities.php'));
        Route::middleware('web')->group(base_path('routes/categories.php'));
        Route::middleware('web')->group(base_path('routes/page-visits.php'));
        Route::middleware('web')->group(base_path('routes/studio_types.php'));
        Route::middleware('web')->group(base_path('routes/studios.php'));
    },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
