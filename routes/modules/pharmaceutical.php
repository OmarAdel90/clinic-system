<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharmaceutical\Controllers\PharmaceuticalController;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('pharmaceuticals', PharmaceuticalController::class)->except(['update']);
    Route::patch('pharmaceuticals/{pharmaceutical}', [PharmaceuticalController::class, 'update'])->name('pharmaceuticals.update');

});
