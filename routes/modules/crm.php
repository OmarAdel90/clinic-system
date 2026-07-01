<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Controllers\CallCenterController;
use Modules\CRM\Controllers\CampaignController;
use Modules\CRM\Controllers\CampaignCostController;

Route::middleware(['auth:sanctum'])->prefix('call-center')->group(function () {
    Route::get('queue', [CallCenterController::class, 'queue']);
    Route::get('queue/next', [CallCenterController::class, 'nextInQueue']);
    Route::post('queue/add/{userId}', [CallCenterController::class, 'addToQueue']);
    Route::delete('queue/remove/{userId}', [CallCenterController::class, 'removeFromQueue']);


    Route::post('leads/assign', [CallCenterController::class, 'assignToUser']);
    Route::post('leads/{leadId}/assign-next', [CallCenterController::class, 'assignNext']);
});

Route::middleware(['auth:sanctum'])->prefix('campaigns')->group(function () {
    Route::get('/', [CampaignController::class, 'index']);
    Route::post('/', [CampaignController::class, 'store']);
    Route::get('{campaign}', [CampaignController::class, 'show']);
    Route::patch('{campaign}', [CampaignController::class, 'update']);
    Route::delete('{campaign}', [CampaignController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('campaign-costs')->group(function () {
    Route::get('/', [CampaignCostController::class, 'index']);
    Route::post('/', [CampaignCostController::class, 'store']);
    Route::get('{campaignCost}', [CampaignCostController::class, 'show']);
    Route::patch('{campaignCost}', [CampaignCostController::class, 'update']);
    Route::delete('{campaignCost}', [CampaignCostController::class, 'destroy']);
});
