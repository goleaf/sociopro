<?php

namespace App\Enums;

use Symfony\Component\HttpFoundation\Response;

enum ApiErrorCode: string
{
    case Validation = 'VALIDATION_ERROR';
    case Authentication = 'AUTHENTICATION_ERROR';
    case Authorization = 'AUTHORIZATION_ERROR';
    case NotFound = 'NOT_FOUND';
    case Conflict = 'CONFLICT';
    case RateLimit = 'RATE_LIMITED';
    case Domain = 'DOMAIN_ERROR';
    case Server = 'SERVER_ERROR';

    public function category(): string
    {
        return match ($this) {
            self::Validation => 'validation',
            self::Authentication => 'authentication',
            self::Authorization => 'authorization',
            self::NotFound => 'not_found',
            self::Conflict => 'conflict',
            self::RateLimit => 'rate_limit',
            self::Domain => 'domain',
            self::Server => 'server',
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::Validation, self::Domain => Response::HTTP_UNPROCESSABLE_ENTITY,
            self::Authentication => Response::HTTP_UNAUTHORIZED,
            self::Authorization => Response::HTTP_FORBIDDEN,
            self::NotFound => Response::HTTP_NOT_FOUND,
            self::Conflict => Response::HTTP_CONFLICT,
            self::RateLimit => Response::HTTP_TOO_MANY_REQUESTS,
            self::Server => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}
