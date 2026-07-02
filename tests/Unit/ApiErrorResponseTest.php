<?php

namespace Tests\Unit;

use App\Enums\ApiErrorCode;
use App\Support\Api\ApiErrorResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiErrorResponseTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $expectedPayload
     */
    #[DataProvider('errorResponseProvider')]
    public function test_api_error_response_has_standard_contract(
        ApiErrorCode $code,
        string $message,
        int $transportStatus,
        array $details,
        array $expectedPayload
    ): void {
        $response = ApiErrorResponse::make(
            code: $code,
            message: $message,
            details: $details,
            transportStatus: $transportStatus
        );

        $this->assertSame($transportStatus, $response->getStatusCode());
        $this->assertSame($expectedPayload, json_decode((string) $response->getContent(), true));
    }

    /**
     * @return iterable<string, array{ApiErrorCode, string, int, array<string, mixed>, array<string, mixed>}>
     */
    public static function errorResponseProvider(): iterable
    {
        yield 'validation' => [
            ApiErrorCode::Validation,
            'Validation failed',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ['title' => ['The title field is required.']],
            [
                'success' => false,
                'message' => 'Validation failed',
                'validationError' => ['title' => ['The title field is required.']],
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'category' => 'validation',
                    'message' => 'Validation failed',
                    'http_status' => 422,
                    'details' => ['title' => ['The title field is required.']],
                ],
            ],
        ];

        yield 'authentication' => [
            ApiErrorCode::Authentication,
            'Unauthorized access',
            Response::HTTP_OK,
            [],
            [
                'success' => false,
                'message' => 'Unauthorized access',
                'error' => [
                    'code' => 'AUTHENTICATION_ERROR',
                    'category' => 'authentication',
                    'message' => 'Unauthorized access',
                    'http_status' => 401,
                    'details' => [],
                ],
            ],
        ];

        yield 'authorization' => [
            ApiErrorCode::Authorization,
            'Forbidden',
            Response::HTTP_FORBIDDEN,
            [],
            [
                'success' => false,
                'message' => 'Forbidden',
                'error' => [
                    'code' => 'AUTHORIZATION_ERROR',
                    'category' => 'authorization',
                    'message' => 'Forbidden',
                    'http_status' => 403,
                    'details' => [],
                ],
            ],
        ];

        yield 'not found' => [
            ApiErrorCode::NotFound,
            'not found',
            Response::HTTP_NOT_FOUND,
            [],
            [
                'success' => false,
                'message' => 'not found',
                'error' => [
                    'code' => 'NOT_FOUND',
                    'category' => 'not_found',
                    'message' => 'not found',
                    'http_status' => 404,
                    'details' => [],
                ],
            ],
        ];

        yield 'conflict' => [
            ApiErrorCode::Conflict,
            'Conflict',
            Response::HTTP_CONFLICT,
            [],
            [
                'success' => false,
                'message' => 'Conflict',
                'error' => [
                    'code' => 'CONFLICT',
                    'category' => 'conflict',
                    'message' => 'Conflict',
                    'http_status' => 409,
                    'details' => [],
                ],
            ],
        ];

        yield 'rate limit' => [
            ApiErrorCode::RateLimit,
            'Too many requests',
            Response::HTTP_TOO_MANY_REQUESTS,
            ['retry_after' => 60],
            [
                'success' => false,
                'message' => 'Too many requests',
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'category' => 'rate_limit',
                    'message' => 'Too many requests',
                    'http_status' => 429,
                    'details' => ['retry_after' => 60],
                ],
            ],
        ];

        yield 'domain' => [
            ApiErrorCode::Domain,
            'Request cannot be accepted in the current state.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            [],
            [
                'success' => false,
                'message' => 'Request cannot be accepted in the current state.',
                'error' => [
                    'code' => 'DOMAIN_ERROR',
                    'category' => 'domain',
                    'message' => 'Request cannot be accepted in the current state.',
                    'http_status' => 422,
                    'details' => [],
                ],
            ],
        ];

        yield 'server' => [
            ApiErrorCode::Server,
            'Server error',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            [],
            [
                'success' => false,
                'message' => 'Server error',
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'category' => 'server',
                    'message' => 'Server error',
                    'http_status' => 500,
                    'details' => [],
                ],
            ],
        ];
    }
}
