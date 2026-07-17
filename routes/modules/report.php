<?php

use Illuminate\Support\Facades\Route;
use Modules\Visit\Controllers\ReportController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('reports', [ReportController::class, 'index']);
    Route::get('reports/{report}', [ReportController::class, 'show']);
    Route::patch('reports/{report}', [ReportController::class, 'update']);
});
