<?php

use Illuminate\Support\Facades\Route;
use Modules\Transaction\Controllers\TransactionController;

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::apiResource('transactions', TransactionController::class)->except(['update']);
    Route::patch('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');

});
