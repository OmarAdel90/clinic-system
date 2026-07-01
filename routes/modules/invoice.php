<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Controllers\InvoiceController;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::patch('invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
    Route::apiResource('invoices', InvoiceController::class)->except(['update']);
    Route::patch('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');

});
