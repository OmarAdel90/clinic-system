<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\CRM\Controllers\WebhookController;

Route::withoutMiddleware('auth:sanctum')->group(function () {
    Route::get('webhook/meta', [WebhookController::class, 'verify']);
    Route::post('webhook/meta', [WebhookController::class, 'handle']);
});

require __DIR__ . '/modules/pharmaceutical.php';
require __DIR__ . '/modules/warehouse.php';
require __DIR__ . '/modules/supplier.php';
require __DIR__ . '/modules/transaction.php';
require __DIR__ . '/modules/visit.php';
require __DIR__ . '/modules/invoice.php';
require __DIR__ . '/modules/treatment-plan.php';
require __DIR__ . '/modules/crm.php';
require __DIR__ . '/modules/lead.php';
require __DIR__ . '/modules/auth.php';
require __DIR__ . '/modules/patient.php';
require __DIR__ . '/modules/clinic.php';
require __DIR__ . '/modules/agent.php';
require __DIR__ . '/modules/admin.php';
