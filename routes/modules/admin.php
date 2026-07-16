<?php

use App\Http\Middleware\EnsureSeededAdmin;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\PermissionController;
use Modules\Auth\Controllers\RoleController;
use Modules\Auth\Controllers\UserController;
use Modules\CRM\Controllers\MetaSettingsController;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::patch('users/{user}/roles', [UserController::class, 'syncRoles'])->name('users.roles');
    Route::apiResource('users', UserController::class)->except(['update']);
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::patch('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions');
    Route::apiResource('roles', RoleController::class)->except(['update']);
    Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

    Route::middleware([EnsureSeededAdmin::class])->group(function () {
        Route::get('settings/meta', [MetaSettingsController::class, 'show'])->name('settings.meta.show');
        Route::patch('settings/meta/facebook-instagram', [MetaSettingsController::class, 'updateFacebookInstagram'])->name('settings.meta.facebook-instagram.update');
        Route::patch('settings/meta/whatsapp', [MetaSettingsController::class, 'updateWhatsapp'])->name('settings.meta.whatsapp.update');
    });

});
