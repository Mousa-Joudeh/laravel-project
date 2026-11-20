<?php

use App\Http\Controllers\FavoriteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::apiResource('favorites', FavoriteController::class)
        ->only(['index', 'store', 'destroy']);
});