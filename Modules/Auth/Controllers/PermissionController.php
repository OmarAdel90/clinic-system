<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Permission::all());
    }
}
