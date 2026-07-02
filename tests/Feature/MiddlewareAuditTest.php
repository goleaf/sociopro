<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UserActivity;
use App\Http\Middleware\UserMiddleware;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MiddlewareAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_application_middleware_are_registered_or_configured(): void
    {
        $middlewareClasses = collect(File::files(app_path('Http/Middleware')))
            ->map(fn ($file): string => 'App\\Http\\Middleware\\'.$file->getFilenameWithoutExtension())
            ->sort()
            ->values();

        $registered = $this->registeredMiddlewareClasses();

        foreach ($middlewareClasses as $middlewareClass) {
            $this->assertContains($middlewareClass, $registered, "{$middlewareClass} is not registered or configured.");
        }
    }

    public function test_security_sensitive_middleware_aliases_are_registered(): void
    {
        $aliases = $this->kernelProperty('routeMiddleware');

        $this->assertSame(Authenticate::class, $aliases['auth']);
        $this->assertSame(AdminMiddleware::class, $aliases['admin']);
        $this->assertSame(UserMiddleware::class, $aliases['user']);
        $this->assertSame(UserActivity::class, $aliases['activity']);
        $this->assertSame(PreventBackHistory::class, $aliases['prevent-back-history']);
    }

    public function test_role_and_activity_middleware_are_ordered_after_authentication(): void
    {
        foreach (Route::getRoutes() as $route) {
            $middleware = array_values($route->gatherMiddleware());

            if (in_array('admin', $middleware, true)) {
                $this->assertMiddlewareBefore($middleware, 'auth', 'admin', $route->uri());
                $this->assertMiddlewareBefore($middleware, 'verified', 'admin', $route->uri());
            }

            if (in_array('user', $middleware, true)) {
                $this->assertMiddlewareBefore($middleware, 'auth', 'user', $route->uri());
            }

            if (in_array('activity', $middleware, true)) {
                $this->assertMiddlewareBefore($middleware, 'auth', 'activity', $route->uri());
            }
        }
    }

    public function test_custom_authorization_middleware_handle_guest_requests_safely(): void
    {
        $request = Request::create('/admin/dashboard');

        $adminResponse = app(AdminMiddleware::class)->handle($request, fn (): Response => new Response('ok'));
        $userResponse = app(UserMiddleware::class)->handle($request, fn (): Response => new Response('ok'));

        $this->assertSame(route('login'), $adminResponse->getTargetUrl());
        $this->assertSame(route('login'), $userResponse->getTargetUrl());
    }

    public function test_user_activity_allows_guest_requests_without_mutating_user_state(): void
    {
        $response = app(UserActivity::class)->handle(
            Request::create('/guest'),
            fn (): Response => new Response('ok')
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_user_activity_records_authenticated_user_activity(): void
    {
        $user = User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
        ]);

        $this->actingAs($user);

        $request = Request::create('/profile');
        $request->setUserResolver(fn (): User => $user);

        app(UserActivity::class)->handle($request, fn (): Response => new Response('ok'));

        $this->assertNotNull($user->fresh()->lastActive);
        $this->assertTrue(Cache::has('user-is-online-'.$user->id));
    }

    public function test_prevent_back_history_sets_standard_no_cache_headers(): void
    {
        $response = app(PreventBackHistory::class)->handle(
            Request::create('/profile'),
            fn (): Response => new Response('ok')
        );

        $this->assertTrue($response->headers->hasCacheControlDirective('no-cache'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('must-revalidate'));
        $this->assertSame('0', $response->headers->getCacheControlDirective('max-age'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame('Sun, 02 Jan 1990 00:00:00 GMT', $response->headers->get('Expires'));
    }

    public function test_security_headers_are_added_to_responses(): void
    {
        $response = app(SecurityHeaders::class)->handle(
            Request::create('https://example.test/profile'),
            fn (): Response => new Response('ok')
        );

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('0', $response->headers->get('X-XSS-Protection'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }

    /**
     * @return list<string>
     */
    private function registeredMiddlewareClasses(): array
    {
        $kernelMiddleware = [
            ...$this->kernelProperty('middleware'),
            ...Arr::flatten($this->kernelProperty('middlewareGroups')),
            ...array_values($this->kernelProperty('routeMiddleware')),
            ...array_values(config('sanctum.middleware', [])),
        ];

        return collect($kernelMiddleware)
            ->filter(fn ($entry): bool => is_string($entry) && class_exists($entry))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<mixed>
     */
    private function kernelProperty(string $property): array
    {
        $kernel = app(HttpKernelContract::class);
        $reflection = new ReflectionClass($kernel);
        $reflectedProperty = $reflection->getProperty($property);
        $reflectedProperty->setAccessible(true);

        return $reflectedProperty->getValue($kernel);
    }

    /**
     * @param  list<string>  $middleware
     */
    private function assertMiddlewareBefore(array $middleware, string $first, string $second, string $uri): void
    {
        $this->assertContains($first, $middleware, "{$uri} is missing {$first} middleware.");
        $this->assertContains($second, $middleware, "{$uri} is missing {$second} middleware.");
        $this->assertLessThan(
            array_search($second, $middleware, true),
            array_search($first, $middleware, true),
            "{$uri} must run {$first} before {$second}."
        );
    }
}
