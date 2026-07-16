<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Support\SeededAdmin;
use Symfony\Component\HttpFoundation\Response;

class EnsureSeededAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SeededAdmin::matches($request->user())) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return new JsonResponse(['message' => 'Only the seeded admin can access this resource.'], 403);
            }

            abort(403, 'Only the seeded admin can access this resource.');
        }

        return $next($request);
    }
}
