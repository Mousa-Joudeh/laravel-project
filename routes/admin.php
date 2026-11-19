<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserListController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [UserListController::class, 'index'])
            ->name('users.index');

        Route::get('/users/export', [UserListController::class, 'export'])
            ->name('users.export');

        Route::post('/users/import', [UserListController::class, 'import'])
            ->name('users.import');
    });
