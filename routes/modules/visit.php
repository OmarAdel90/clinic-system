<?php

use Illuminate\Support\Facades\Route;
use Modules\Visit\Controllers\VisitController;
use Modules\Visit\Controllers\VisitFlowController;

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::apiResource('visits', VisitController::class)->except(['update']);
    Route::patch('visits/{visit}', [VisitController::class, 'update'])->name('visits.update');

    Route::patch('visits/{visit}/confirm', [VisitFlowController::class, 'confirm']);
    Route::post('visits/{visit}/complete', [VisitFlowController::class, 'complete']);
    Route::patch('visits/{visit}/cancel', [VisitFlowController::class, 'cancel']);
    Route::patch('visits/{visit}/miss', [VisitFlowController::class, 'miss']);

});
