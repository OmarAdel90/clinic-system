<?php

namespace Modules\Auth\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class RoleService
{
    public function getAll(): Collection
    {
        try {
            return Role::with('permissions')->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(int $id): Role
    {
        try {
            return Role::with('permissions')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['id' => $id]);
            throw new \Exception("Role with ID '$id' was not found", 404);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    public function create(array $data): Role
    {
        try {
            $permissions = $data['permissions'] ?? [];
            unset($data['permissions']);

            $data['guard_name'] = 'web';
            $role = Role::create($data);

            if (!empty($permissions)) {
                $role->syncPermissions($permissions);
            }

            return $role->load('permissions');
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Role $role, array $data): Role
    {
        try {
            $role->update($data);
            return $role->fresh('permissions');
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $role->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Role $role): void
    {
        try {
            $role->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $role->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        try {
            $role->syncPermissions($permissionIds);
            return $role->fresh('permissions');
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['role_id' => $role->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
