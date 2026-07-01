<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Modules\Auth\Models\User;
use Modules\Auth\Services\UserService;
use Modules\Auth\Requests\StoreUserRequest;
use Modules\Auth\Requests\UpdateUserRequest;
use Modules\Auth\Requests\SyncUserRolesRequest;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(protected UserService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($this->service->get($user->id));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            return response()->json($this->service->create($request->validated()), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => 'error'], $e->getCode() ?: 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            return response()->json($this->service->update($user, $request->validated()));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => 'error'], $e->getCode() ?: 500);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        $this->service->delete($user);
        return response()->json('User deleted', 204);
    }

    public function syncRoles(SyncUserRolesRequest $request, User $user): JsonResponse
    {
        return response()->json($this->service->syncRoles($user, $request->validated()['roles']));
    }
}
