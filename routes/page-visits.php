<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageVisitController;

Route::middleware(['auth'])->group(function () {

    Route::resource('page-visits', PageVisitController::class)
        ->only(['index', 'show', 'destroy']);

});