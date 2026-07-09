<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Auth\Requests\LoginRequest;

class AuthController extends Controller
{
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

        $user->tokens()->delete();

        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')));

        $user->loadMissing('roles');

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(): JsonResponse
    {
        $user = auth()->user()->loadMissing('roles');

        return response()->json(array_merge($user->toArray(), ['roles' => $user->roles->map->only(['id', 'name', 'guard_name'])]));
    }

    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
