<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Auth\Requests\LoginRequest;

class AuthController extends Controller
{
    protected function serializeUser(User $user): array
    {
        $user->loadMissing('roles');

        return array_merge($user->toArray(), [
            'roles' => $user->roles->map->only(['id', 'name', 'guard_name']),
            'permissions' => $user->getAllPermissions()->map->only(['id', 'name', 'guard_name'])->values(),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = trim((string) $request->input('login', $request->input('email')));

        $user = User::query()
            ->where('email', $login)
            ->orWhere('phone_number', $login)
            ->first();

        $passwordValid = $user && Hash::check($request->input('password'), $user->password ?? '');
        if (! $passwordValid) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! ($user->is_active ?? true)) {
            return response()->json(['message' => 'This account is inactive.'], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')));

        return response()->json([
            'user' => $this->serializeUser($user),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(): JsonResponse
    {
        $user = auth()->user();

        return response()->json($this->serializeUser($user));
    }

    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
