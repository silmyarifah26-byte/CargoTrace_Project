<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiBearerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return response()->json([
                'message' => 'Unauthenticated. Bearer token is required.',
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $userId = Cache::get("cargotrace:api_token:{$hashedToken}");

        if (! $userId) {
            return response()->json([
                'message' => 'Invalid or expired API token.',
            ], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'message' => 'Authenticated user not found.',
            ], 401);
        }

        $request->setUserResolver(fn () => $user);
        auth()->setUser($user);
        $request->attributes->set('api_token_hash', $hashedToken);

        return $next($request);
    }
}