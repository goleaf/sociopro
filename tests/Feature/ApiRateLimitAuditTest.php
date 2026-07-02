<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRateLimitAuditTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_RATE_LIMIT_KEY = 'ip:127.0.0.1';

    public function test_abuse_sensitive_routes_have_named_throttle_middleware(): void
    {
        $expected = [
            'api.auth.login' => 'throttle:api-token',
            'api.auth.signup' => 'throttle:api-registration',
            'api.password.forgot' => 'throttle:api-password-reset',
            'api.password.update' => 'throttle:api-authenticated',
            'api.marketplace.filter' => 'throttle:api-search',
            'api.marketplace.index' => 'throttle:api-expensive',
            'api.timeline.load' => 'throttle:api-expensive',
            'api.notifications.index' => 'throttle:api-expensive',
            'api.chat.index' => 'throttle:api-expensive',
            'api.users.index' => 'throttle:api-expensive',
            'login.store' => 'throttle:login',
            'register.store' => 'throttle:registration',
            'password.email' => 'throttle:password-reset',
            'contact.send' => 'throttle:contact',
            'payment.status' => 'throttle:webhook',
            'make.payment' => 'throttle:webhook',
        ];

        foreach ($expected as $routeName => $middleware) {
            $route = Route::getRoutes()->getByName($routeName);
            $expandedThrottleSuffix = ':'.substr($middleware, strlen('throttle:'));

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertTrue(
                collect($route->gatherMiddleware())->contains(fn (string $entry): bool => $entry === $middleware || str_ends_with($entry, $expandedThrottleSuffix)),
                "Route [{$routeName}] is missing [{$middleware}]."
            );
        }
    }

    public function test_api_login_token_generation_is_rate_limited(): void
    {
        $email = 'token-limit@example.com';
        $user = $this->activeUser([
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $throttleKey = $this->namedThrottleKey('api-token', $this->emailAndClientKey($email));

        RateLimiter::clear($throttleKey);

        $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertCreated();

        $this->primeThrottleKey($throttleKey, 9);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'RATE_LIMITED');

        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_api_registration_is_rate_limited_by_client(): void
    {
        $throttleKey = $this->namedThrottleKey('api-registration', self::CLIENT_RATE_LIMIT_KEY);

        RateLimiter::clear($throttleKey);

        $this->postJson(route('api.auth.signup'), [])
            ->assertOk()
            ->assertJsonStructure(['validationError']);

        $this->primeThrottleKey($throttleKey, 9);

        $this->postJson(route('api.auth.signup'), [])
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'RATE_LIMITED');
    }

    public function test_api_password_reset_is_rate_limited_by_email_and_client(): void
    {
        $email = 'missing-reset@example.com';
        $throttleKey = $this->namedThrottleKey('api-password-reset', $this->emailAndClientKey($email));

        RateLimiter::clear($throttleKey);

        $this->postJson(route('api.password.forgot'), [
            'email' => $email,
        ])->assertOk();

        $this->primeThrottleKey($throttleKey, 4);

        $this->postJson(route('api.password.forgot'), [
            'email' => $email,
        ])
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'RATE_LIMITED');
    }

    public function test_authenticated_api_search_and_expensive_reads_are_rate_limited(): void
    {
        $user = $this->activeUser();
        $token = $user->createToken('api-rate-limit-test')->plainTextToken;
        $throttleKey = $this->namedThrottleKey(
            'api-search',
            'token:'.sha1($token).':api.marketplace.filter'
        );

        RateLimiter::clear($throttleKey);

        $this->withToken($token)
            ->getJson(route('api.marketplace.filter'))
            ->assertOk();

        $this->primeThrottleKey($throttleKey, 19);

        $this->withToken($token)
            ->getJson(route('api.marketplace.filter'))
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'RATE_LIMITED');
    }

    public function test_contact_form_submission_is_rate_limited(): void
    {
        Mail::fake();
        $email = 'sender@example.com';
        $throttleKey = $this->namedThrottleKey('contact', $this->emailAndClientKey($email));

        $this->activeUser([
            'email' => 'admin-contact@example.com',
            'user_role' => UserRole::Admin->value,
        ]);

        RateLimiter::clear($throttleKey);

        $this->post(route('contact.send'), [
            'name' => 'Contact Sender',
            'email' => $email,
            'subject' => 'Hello',
            'details' => 'Testing contact throttling.',
        ])->assertRedirect();

        $this->primeThrottleKey($throttleKey, 4);

        $this->post(route('contact.send'), [
            'name' => 'Contact Sender',
            'email' => $email,
            'subject' => 'Hello',
            'details' => 'Testing contact throttling.',
        ])->assertTooManyRequests();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
        ], $overrides));
    }

    private function emailAndClientKey(string $email): string
    {
        return 'email:'.mb_strtolower(trim($email)).':'.self::CLIENT_RATE_LIMIT_KEY;
    }

    private function namedThrottleKey(string $limiterName, string $limitKey): string
    {
        return md5($limiterName.$limitKey);
    }

    private function primeThrottleKey(string $throttleKey, int $attempts): void
    {
        RateLimiter::increment($throttleKey, decaySeconds: 60, amount: $attempts);
    }
}
