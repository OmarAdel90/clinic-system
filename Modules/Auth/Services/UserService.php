<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function getAll(): Collection
    {
        try {
            return User::with('roles')->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(int $id): User
    {
        try {
            return User::with('roles')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['id' => $id]);
            throw new \Exception("User with ID '$id' was not found", 404);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    public function create(array $data): User
    {
        try {
            $roleIds = $data['roles'] ?? [];
            unset($data['roles']);

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user = User::create($data);

            if (!empty($roleIds)) {
                $roleModels = Role::whereIn('id', $roleIds)->get();
                $user->syncRoles($roleModels);
            } elseif (!empty($data['role_id'])) {
                $roleModel = Role::find($data['role_id']);
                if ($roleModel) {
                    $user->assignRole($roleModel);
                }
            }

            return $user->load('roles');
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(User $user, array $data): User
    {
        try {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            if (array_key_exists('role_id', $data)) {
                if ($data['role_id']) {
                    $roleModel = Role::find($data['role_id']);
                    if ($roleModel) {
                        $user->syncRoles([$roleModel]);
                    }
                } else {
                    $user->syncRoles([]);
                }
            }

            return $user->fresh('roles');
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $user->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(User $user): void
    {
        try {
            $user->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function syncRoles(User $user, array $roleIds): User
    {
        try {
            $roleModels = Role::whereIn('id', $roleIds)->get();
            $user->syncRoles($roleModels);
            return $user->fresh('roles');
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
