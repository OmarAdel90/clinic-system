<?php

use Illuminate\Support\Facades\Route;
use Modules\Clinic\Controllers\ClinicController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('clinics', ClinicController::class)->except(['update']);
    Route::patch('clinics/{clinic}', [ClinicController::class, 'update'])->name('clinics.update');
});
