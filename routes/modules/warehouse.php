<?php

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Controllers\WarehouseController;

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::apiResource('warehouses', WarehouseController::class)->except(['update']);
    Route::patch('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');

});
