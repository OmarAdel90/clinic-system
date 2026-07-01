<?php

use Illuminate\Support\Facades\Route;
use Modules\Patient\Controllers\MedicalRecordController;
use Modules\Patient\Controllers\PatientFeedbackController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('leads/{lead}/medical-records', [MedicalRecordController::class, 'index']);
    Route::get('leads/{lead}/medical-records/{medicalRecord}', [MedicalRecordController::class, 'show']);
    Route::get('leads/{lead}/medical-records/{medicalRecord}/download', [MedicalRecordController::class, 'download']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('leads/{lead}/medical-records', [MedicalRecordController::class, 'store']);
    Route::patch('leads/{lead}/medical-records/{medicalRecord}', [MedicalRecordController::class, 'update']);
    Route::delete('leads/{lead}/medical-records/{medicalRecord}', [MedicalRecordController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('patient')->group(function () {
    Route::get('feedback', [PatientFeedbackController::class, 'index']);
    Route::get('feedback/{patientFeedback}', [PatientFeedbackController::class, 'show']);
    Route::post('feedback', [PatientFeedbackController::class, 'store']);
    Route::patch('feedback/{patientFeedback}', [PatientFeedbackController::class, 'update']);
    Route::delete('feedback/{patientFeedback}', [PatientFeedbackController::class, 'destroy']);
});
