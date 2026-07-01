<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Controllers\CallCenterController;

Route::middleware(['auth:sanctum'])->prefix('call-center')->group(function () {

    Route::get('queue', [CallCenterController::class, 'queue']);
    Route::get('queue/next', [CallCenterController::class, 'nextInQueue']);
    Route::post('queue/add/{userId}', [CallCenterController::class, 'addToQueue']);
    Route::delete('queue/remove/{userId}', [CallCenterController::class, 'removeFromQueue']);

    Route::get('leads', [CallCenterController::class, 'leads']);
    Route::get('leads/{id}', [CallCenterController::class, 'lead']);
    Route::post('leads', [CallCenterController::class, 'storeLead']);
    Route::post('leads/{leadId}/assign-next', [CallCenterController::class, 'assignNext']);
    Route::post('leads/assign', [CallCenterController::class, 'assignToUser']);
    Route::patch('leads/{lead}', [CallCenterController::class, 'updateLead']);
    Route::delete('leads/{lead}', [CallCenterController::class, 'destroyLead']);

    Route::get('users', [CallCenterController::class, 'users']);

    Route::apiResource('campaigns', \Modules\CRM\Controllers\CampaignController::class)->except(['create', 'edit', 'update']);
    Route::patch('campaigns/{campaign}', [\Modules\CRM\Controllers\CampaignController::class, 'update'])->name('campaigns.update');
    Route::apiResource('campaign-costs', \Modules\CRM\Controllers\CampaignCostController::class)->except(['create', 'edit', 'update']);
    Route::patch('campaign-costs/{campaignCost}', [\Modules\CRM\Controllers\CampaignCostController::class, 'update'])->name('campaign-costs.update');

});
