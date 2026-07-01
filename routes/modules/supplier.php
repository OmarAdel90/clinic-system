<?php

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Controllers\SupplierController;
use Modules\Supplier\Controllers\SupplierPaymentController;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('suppliers', SupplierController::class)->except(['update']);
    Route::patch('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');

    Route::patch('supplier-payments/{supplierPayment}/pay', [SupplierPaymentController::class, 'pay']);
    Route::apiResource('supplier-payments', SupplierPaymentController::class)->except(['update']);
    Route::patch('supplier-payments/{supplier_payment}', [SupplierPaymentController::class, 'update'])->name('supplier-payments.update');

});
