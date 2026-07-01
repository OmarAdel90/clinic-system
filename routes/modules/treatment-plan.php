<?php

use Illuminate\Support\Facades\Route;
use Modules\TreatmentPlan\Controllers\TreatmentPlanController;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('treatment-plans', TreatmentPlanController::class)->except(['update']);
    Route::patch('treatment-plans/{treatmentPlan}', [TreatmentPlanController::class, 'update'])->name('treatment-plans.update');

});
