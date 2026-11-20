<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);

    Route::get('/account', [UserController::class, 'account'])
        ->name('account.show');

    Route::patch('/account', [UserController::class, 'updateAccount'])
        ->name('account.update');

    Route::patch('/account/password', [UserController::class, 'updateAccountPassword'])
        ->name('account.password.update');

    Route::get('/profile', [UserController::class, 'account'])
        ->name('profile.show');
});