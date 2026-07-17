<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Support\SeededAdmin;

class UserService
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        try {
            $query = User::with('roles');

            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('arabic_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhereHas('roles', function (Builder $roleQuery) use ($search) {
                            $roleQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            }

            $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));

            return $query
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'filters' => $filters]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'filters' => $filters]);
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
            if ($user->email === SeededAdmin::email()) {
                if (array_key_exists('is_active', $data) && ! (bool) $data['is_active']) {
                    throw new \Exception('The seeded admin user must remain active.', 422);
                }

                if (array_key_exists('email', $data) && $data['email'] !== SeededAdmin::email()) {
                    throw new \Exception('The seeded admin email cannot be changed.', 422);
                }
            }

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
            if ($user->email === SeededAdmin::email()) {
                throw new \Exception('The seeded admin user cannot be deleted.', 422);
            }

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
