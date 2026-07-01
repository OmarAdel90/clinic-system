<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Modules\Auth\Services\RoleService;
use Modules\Auth\Requests\StoreRoleRequest;
use Modules\Auth\Requests\UpdateRoleRequest;
use Modules\Auth\Requests\SyncRolePermissionsRequest;
use Illuminate\Http\JsonResponse;


class RoleController extends Controller
{
    public function __construct(protected RoleService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json($this->service->get($role->id));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            return response()->json($this->service->create($request->validated()), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => 'error'], $e->getCode() ?: 500);
        }
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        try {
            return response()->json($this->service->update($role, $request->validated()));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => 'error'], $e->getCode() ?: 500);
        }
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->service->delete($role);
        return response()->json(null, 204);
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        return response()->json($this->service->syncPermissions($role, $request->validated()['permissions']));
    }
}
