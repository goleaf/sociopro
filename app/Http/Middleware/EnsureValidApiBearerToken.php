<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Api\ApiErrorResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidApiBearerToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return $this->unauthorized();
        }

        $user = $request->user('sanctum');

        if (! $user instanceof User) {
            return $this->unauthorized();
        }

        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return $this->unauthorized();
        }

        if ($token->getAttribute('tokenable_type') !== $user->getMorphClass()
            || (int) $token->getAttribute('tokenable_id') !== (int) $user->getKey()) {
            return $this->unauthorized();
        }

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return ApiErrorResponse::authentication(transportStatus: Response::HTTP_OK);
    }
}
