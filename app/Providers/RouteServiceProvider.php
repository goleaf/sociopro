<?php

namespace App\Providers;

use App\Enums\ApiErrorCode;
use App\Support\Api\ApiErrorResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/custom_routes.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/user.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/payment.php'));

            if ($this->shouldLoadFundraiserRoutes()) {
                Route::middleware('web')
                    ->namespace($this->namespace)
                    ->group(base_path('routes/fundraiser.php'));
            }
        });
    }

    private function shouldLoadFundraiserRoutes(): bool
    {
        try {
            if (DB::connection()->getDatabaseName() === 'db_name') {
                return false;
            }

            return Schema::hasTable('addons') && addon_status('fundraiser') == 1;
        } catch (Throwable) {
            return false;
        }
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return $this->apiLimit(60, $this->userOrClientKey($request));
        });

        RateLimiter::for('api-authenticated', function (Request $request) {
            return $this->apiLimit(120, $this->userOrClientKey($request));
        });

        RateLimiter::for('api-expensive', function (Request $request) {
            return $this->apiLimit(30, $this->routeScopedKey($request));
        });

        RateLimiter::for('api-search', function (Request $request) {
            return $this->apiLimit(20, $this->routeScopedKey($request));
        });

        RateLimiter::for('api-token', function (Request $request) {
            return $this->apiLimit(10, $this->emailAndClientKey($request));
        });

        RateLimiter::for('api-registration', function (Request $request) {
            return $this->apiLimit(10, $this->clientKey($request));
        });

        RateLimiter::for('api-password-reset', function (Request $request) {
            return $this->apiLimit(5, $this->emailAndClientKey($request));
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($this->emailAndClientKey($request));
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(10)->by($this->clientKey($request));
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($this->emailAndClientKey($request));
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($this->routeScopedKey($request));
        });

        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinute(5)->by($this->emailAndClientKey($request));
        });

        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(30)->by($this->routeScopedKey($request));
        });
    }

    private function apiLimit(int $maxAttempts, string $key): Limit
    {
        return Limit::perMinute($maxAttempts)
            ->by($key)
            ->response(fn (Request $request, array $headers): JsonResponse => $this->apiRateLimitResponse($headers));
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function apiRateLimitResponse(array $headers): JsonResponse
    {
        return ApiErrorResponse::make(
            code: ApiErrorCode::RateLimit,
            message: 'Too many requests',
            details: [
                'retry_after' => (int) ($headers['Retry-After'] ?? 0),
            ],
            transportStatus: Response::HTTP_TOO_MANY_REQUESTS
        )->withHeaders($headers);
    }

    private function routeScopedKey(Request $request): string
    {
        return $this->userOrClientKey($request).':'.$this->routeName($request);
    }

    private function userOrClientKey(Request $request): string
    {
        if ($request->user()) {
            return 'user:'.$request->user()->getAuthIdentifier();
        }

        if ($request->bearerToken()) {
            return 'token:'.sha1($request->bearerToken());
        }

        return $this->clientKey($request);
    }

    private function emailAndClientKey(Request $request): string
    {
        return $this->normalizedInput($request, 'email').':'.$this->clientKey($request);
    }

    private function clientKey(Request $request): string
    {
        return 'ip:'.($request->ip() ?: 'unknown');
    }

    private function routeName(Request $request): string
    {
        return $request->route()?->getName() ?: trim($request->path(), '/');
    }

    private function normalizedInput(Request $request, string $key): string
    {
        $value = $request->input($key);

        if (! is_scalar($value)) {
            return $key.':missing';
        }

        $normalized = mb_strtolower(trim((string) $value));

        return $key.':'.($normalized !== '' ? $normalized : 'missing');
    }
}
