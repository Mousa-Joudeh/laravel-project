<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);

    Route::get('/account', [UserController::class, 'account'])
        ->name('account.show');
});