<?php

namespace App\Support\Api;

use App\Enums\ApiErrorCode;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class IdempotentApiRequest
{
    public const HEADER = 'Idempotency-Key';

    public const REPLAYED_HEADER = 'Idempotency-Replayed';

    /**
     * @param  Closure(): JsonResponse  $callback
     */
    public function handle(Request $request, string $operation, Closure $callback): JsonResponse
    {
        $idempotencyKey = $this->idempotencyKey($request);

        if ($idempotencyKey === null) {
            return $callback();
        }

        if (! $this->isValidIdempotencyKey($idempotencyKey)) {
            return ApiErrorResponse::make(
                code: ApiErrorCode::Validation,
                message: 'Validation failed',
                details: [
                    'idempotency_key' => [
                        'The idempotency key must be 8 to 128 printable ASCII characters without spaces.',
                    ],
                ],
                transportStatus: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $scope = $this->scope($request, $operation, $idempotencyKey);
        $cacheKey = 'api:idempotency:response:'.$scope;
        $fingerprint = $this->fingerprint($request, $operation);

        try {
            /** @var JsonResponse $response */
            $response = Cache::lock(
                'api:idempotency:lock:'.$scope,
                max(1, (int) config('api.idempotency.lock_seconds', 10))
            )->block(
                max(1, (int) config('api.idempotency.wait_seconds', 3)),
                function () use ($cacheKey, $fingerprint, $callback): JsonResponse {
                    $cached = Cache::get($cacheKey);

                    if (is_array($cached)) {
                        return $this->responseFromCache($cached, $fingerprint);
                    }

                    $response = $callback();
                    $response->headers->set(self::REPLAYED_HEADER, 'false');

                    $this->storeResponse($cacheKey, $fingerprint, $response);

                    return $response;
                }
            );

            return $response;
        } catch (LockTimeoutException) {
            return ApiErrorResponse::make(
                code: ApiErrorCode::Conflict,
                message: 'Idempotent request is already in progress.',
                transportStatus: Response::HTTP_CONFLICT
            );
        }
    }

    private function idempotencyKey(Request $request): ?string
    {
        $key = $request->headers->get(self::HEADER);

        return is_string($key) && $key !== '' ? $key : null;
    }

    private function isValidIdempotencyKey(string $key): bool
    {
        return preg_match('/^[\x21-\x7E]{8,128}$/', $key) === 1;
    }

    private function scope(Request $request, string $operation, string $idempotencyKey): string
    {
        return hash('sha256', implode('|', [
            $operation,
            $this->actorScope($request),
            $idempotencyKey,
        ]));
    }

    private function actorScope(Request $request): string
    {
        $user = $request->user('sanctum') ?? $request->user();

        if ($user instanceof Authenticatable) {
            return 'user:'.(string) $user->getAuthIdentifier();
        }

        return 'guest:'.hash('sha256', (string) $request->ip().'|'.(string) $request->userAgent());
    }

    private function fingerprint(Request $request, string $operation): string
    {
        return hash('sha256', json_encode([
            'operation' => $operation,
            'method' => $request->method(),
            'route' => $request->route()?->getName(),
            'route_parameters' => $this->normalize($request->route()?->parameters() ?? []),
            'query' => $this->normalize($request->query->all()),
            'payload' => $this->normalize($request->request->all()),
            'files' => $this->normalizeFiles($request->allFiles()),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            $normalized[(string) $key] = $file instanceof UploadedFile
                ? [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ]
                : (is_array($file) ? $this->normalizeFiles($file) : null);
        }

        ksort($normalized);

        return $normalized;
    }

    private function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[(string) $key] = $this->normalize($item);
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function responseFromCache(array $cached, string $fingerprint): JsonResponse
    {
        if (($cached['fingerprint'] ?? null) !== $fingerprint) {
            return ApiErrorResponse::make(
                code: ApiErrorCode::Conflict,
                message: 'Idempotency key was already used with a different request.',
                transportStatus: Response::HTTP_CONFLICT
            );
        }

        $payload = $cached['payload'] ?? [];
        $response = response()->json(
            is_array($payload) ? $payload : [],
            (int) ($cached['status'] ?? Response::HTTP_OK)
        );
        $response->headers->set(self::REPLAYED_HEADER, 'true');

        return $response;
    }

    private function storeResponse(string $cacheKey, string $fingerprint, JsonResponse $response): void
    {
        $status = $response->getStatusCode();

        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            return;
        }

        Cache::put($cacheKey, [
            'fingerprint' => $fingerprint,
            'status' => $status,
            'payload' => $response->getData(true),
        ], now()->addMinutes(max(1, (int) config('api.idempotency.ttl_minutes', 60 * 24))));
    }
}
