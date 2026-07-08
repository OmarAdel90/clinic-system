<?php

use Illuminate\Support\Facades\Route;
use Modules\Lead\Controllers\LeadController;

Route::middleware(['auth:sanctum'])->prefix('leads')->group(function () {
    Route::get('/', [LeadController::class, 'index']);
    Route::post('/', [LeadController::class, 'store']);
    Route::get('{id}', [LeadController::class, 'show']);
    Route::patch('{lead}/assign-clinic', [LeadController::class, 'assignClinic']);
    Route::patch('{lead}', [LeadController::class, 'update']);
    Route::delete('{lead}', [LeadController::class, 'destroy']);
});
