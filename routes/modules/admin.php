<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\UserController;
use Modules\Auth\Controllers\RoleController;
use Modules\Auth\Controllers\PermissionController;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::patch('users/{user}/roles', [UserController::class, 'syncRoles'])->name('users.roles');
    Route::apiResource('users', UserController::class)->except(['update']);
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::patch('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions');
    Route::apiResource('roles', RoleController::class)->except(['update']);
    Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

});
