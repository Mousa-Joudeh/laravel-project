<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserListController;
use App\Http\Controllers\Admin\CategoryManagementController;
use App\Http\Controllers\Admin\PartnerManagementController;
use App\Http\Controllers\Admin\CatalogManagementController;
use App\Http\Controllers\Admin\TrafficAnalyticsController;
use App\Http\Controllers\Admin\ApprovalQueueController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\PackageApprovalController;
use App\Http\Controllers\Admin\CsvToolsController;
use App\Http\Controllers\Admin\AiAssistantController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/csv/{section}/export', [CsvToolsController::class, 'export'])->name('csv.export');
        Route::post('/csv/categories/import', [CsvToolsController::class, 'importCategories'])->name('csv.categories.import');

        Route::get('/users', [UserListController::class, 'index'])
            ->name('users.index');

        Route::get('/users/export', [UserListController::class, 'export'])
            ->name('users.export');

        Route::post('/users/import', [UserListController::class, 'import'])
            ->name('users.import');
        Route::get('/categories', [CategoryManagementController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryManagementController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryManagementController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryManagementController::class, 'destroy'])->name('categories.destroy');

        Route::get('/partners', [PartnerManagementController::class, 'index'])->name('partners.index');
        Route::patch('/partners/{providerProfile}/review', [PartnerManagementController::class, 'review'])->name('partners.review');

        Route::get('/catalog', [CatalogManagementController::class, 'index'])->name('catalog.index');
        Route::patch('/catalog/studios/{studio}/review', [CatalogManagementController::class, 'reviewStudio'])->name('catalog.studios.review');
        Route::patch('/catalog/services/{service}/review', [CatalogManagementController::class, 'reviewService'])->name('catalog.services.review');

        Route::get('/analytics', [TrafficAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/intelligence', [AiAssistantController::class, 'index'])->name('ai.index');
        Route::post('/intelligence/chat', [AiAssistantController::class, 'chat'])->middleware('throttle:6,1')->name('ai.chat');
        Route::post('/intelligence/briefing', [AiAssistantController::class, 'briefing'])->middleware('throttle:2,1')->name('ai.briefing');
        Route::patch('/catalog/packages/{package}/review', [PackageApprovalController::class, 'review'])->name('catalog.packages.review');
        Route::get('/approvals', [ApprovalQueueController::class, 'index'])->name('approvals.index');
        Route::get('/operations/bookings', [OperationsController::class, 'bookings'])->name('operations.bookings');
        Route::get('/operations/transactions', [OperationsController::class, 'transactions'])->name('operations.transactions');
        Route::get('/operations/reviews', [OperationsController::class, 'reviews'])->name('operations.reviews');
        Route::delete('/operations/reviews/{review}', [OperationsController::class, 'removeReview'])->name('operations.reviews.remove');
    });
