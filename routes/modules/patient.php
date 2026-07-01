<?php

use Illuminate\Support\Facades\Route;
use Modules\Patient\Controllers\MedicalRecordController;
use Modules\Patient\Controllers\PatientFeedbackController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('leads/{lead}/medical-records', [MedicalRecordController::class, 'index']);
    Route::post('leads/{lead}/medical-records', [MedicalRecordController::class, 'store']);
});

Route::middleware(['auth:sanctum'])->prefix('medical-records')->group(function () {
    Route::get('{medicalRecord}', [MedicalRecordController::class, 'show']);
    Route::patch('{medicalRecord}', [MedicalRecordController::class, 'update']);
    Route::delete('{medicalRecord}', [MedicalRecordController::class, 'destroy']);
    Route::get('{medicalRecord}/file', [MedicalRecordController::class, 'file']);
    Route::get('{medicalRecord}/download', [MedicalRecordController::class, 'download']);
});

Route::middleware(['auth:sanctum'])->prefix('patient')->group(function () {
    Route::get('feedback', [PatientFeedbackController::class, 'index']);
    Route::get('feedback/{patientFeedback}', [PatientFeedbackController::class, 'show']);
    Route::post('feedback', [PatientFeedbackController::class, 'store']);
    Route::patch('feedback/{patientFeedback}', [PatientFeedbackController::class, 'update']);
    Route::delete('feedback/{patientFeedback}', [PatientFeedbackController::class, 'destroy']);
});
