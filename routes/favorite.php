<?php

use App\Http\Controllers\FavoriteController;
use Illuminate\Support\Facades\Route;

Route::get('favorites/continue', function (\Illuminate\Http\Request $request) {
    return redirect()->to($request->query('return_to'));
})->middleware(['signed', 'auth'])->name('favorites.continue');

Route::middleware('auth')->group(function () {
    Route::apiResource('favorites', FavoriteController::class)
        ->only(['index', 'store', 'destroy']);
});