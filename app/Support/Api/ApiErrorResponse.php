<?php

namespace App\Support\Api;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;

final class ApiErrorResponse
{
    /**
     * @param  array<string, mixed>  $details
     */
    public static function make(
        ApiErrorCode $code,
        string $message,
        array $details = [],
        ?int $transportStatus = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($code === ApiErrorCode::Validation) {
            $payload['validationError'] = $details;
        }

        $payload['error'] = [
            'code' => $code->value,
            'category' => $code->category(),
            'message' => $message,
            'http_status' => $code->httpStatus(),
            'details' => $details,
        ];

        return new JsonResponse($payload, $transportStatus ?? $code->httpStatus());
    }

    public static function authentication(string $message = 'Unauthorized access', ?int $transportStatus = null): JsonResponse
    {
        return self::make(ApiErrorCode::Authentication, $message, transportStatus: $transportStatus);
    }

    public static function authorization(string $message = 'Forbidden', ?int $transportStatus = null): JsonResponse
    {
        return self::make(ApiErrorCode::Authorization, $message, transportStatus: $transportStatus);
    }

    public static function notFound(string $message = 'not found', ?int $transportStatus = null): JsonResponse
    {
        return self::make(ApiErrorCode::NotFound, $message, transportStatus: $transportStatus);
    }

    public static function domain(string $message, ?int $transportStatus = null): JsonResponse
    {
        return self::make(ApiErrorCode::Domain, $message, transportStatus: $transportStatus);
    }
}
