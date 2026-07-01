<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Controllers\AgentController;

Route::middleware(['auth:sanctum'])->prefix('agent')->group(function () {
    Route::get('conversations', [AgentController::class, 'conversations']);
    Route::get('leads', [AgentController::class, 'leads']);
    Route::get('followups', [AgentController::class, 'followups']);
    Route::get('patients', [AgentController::class, 'patients']);
    Route::post('messages/send', [AgentController::class, 'sendMessage']);
});
