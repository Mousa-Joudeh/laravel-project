<?php

use App\Http\Controllers\PageVisitController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/page-visits', [PageVisitController::class, 'index'])
        ->name('page-visits.index');

    Route::get('/page-visits/{pageVisit}', [PageVisitController::class, 'show'])
        ->whereNumber('pageVisit')
        ->name('page-visits.show');
});