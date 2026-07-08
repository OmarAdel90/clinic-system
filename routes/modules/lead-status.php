<?php

use Illuminate\Support\Facades\Route;
use Modules\Lead\Controllers\LeadStatusController;

Route::middleware(['auth:sanctum'])->prefix('lead-statuses')->group(function () {
    Route::get('/', [LeadStatusController::class, 'index']);
    Route::post('/', [LeadStatusController::class, 'store']);
    Route::get('{leadStatus}', [LeadStatusController::class, 'show']);
    Route::patch('{leadStatus}', [LeadStatusController::class, 'update']);
    Route::delete('{leadStatus}', [LeadStatusController::class, 'destroy']);
});
