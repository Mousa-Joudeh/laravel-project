<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

// عمليات الإدارة: تحتاج Login، وفحص Admin داخل Controller.
Route::middleware('auth')->group(function () {
    Route::resource('categories', CategoryController::class)
        ->except(['index', 'show']);
});

// العرض متاح للزوّار، والفلترة حسب الدور داخل Controller.
Route::resource('categories', CategoryController::class)
    ->only(['index', 'show']);
