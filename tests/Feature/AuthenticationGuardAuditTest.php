<?php

namespace Tests\Feature;

use App\Enums\ApiTokenAbility;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthenticationGuardAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_uses_expected_authentication_guards(): void
    {
        $this->assertSame(['web', 'sanctum'], array_keys(config('auth.guards')));
        $this->assertSame(['users'], array_keys(config('auth.providers')));
        $this->assertSame(['web'], config('sanctum.guard'));
    }

    public function test_routes_do_not_reference_undefined_authentication_guards(): void
    {
        $definedGuards = [
            ...array_keys(config('auth.guards', [])),
            'sanctum',
        ];

        $this->assertNotEmpty($definedGuards);

        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (! is_string($middleware)) {
                    continue;
                }

                if (! preg_match('/^(auth|guest):(.+)$/', $middleware, $matches)) {
                    continue;
                }

                foreach (explode(',', $matches[2]) as $guard) {
                    $this->assertContains(
                        $guard,
                        $definedGuards,
                        "{$route->methods()[0]} {$route->uri()} references undefined {$matches[1]} guard [{$guard}]."
                    );
                }
            }
        }
    }

    public function test_protected_api_routes_use_personal_access_token_guard_middleware(): void
    {
        $publicApiRouteNames = [
            'api.data.index',
            'api.auth.login',
            'api.auth.signup',
            'api.password.forgot',
        ];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! is_string($name) || ! str_starts_with($name, 'api.') || in_array($name, $publicApiRouteNames, true)) {
                continue;
            }

            $this->assertContains(
                'api.token',
                $route->gatherMiddleware(),
                "{$route->methods()[0]} {$route->uri()} is missing the API token guard middleware."
            );
        }
    }

    public function test_invalid_bearer_token_is_rejected_before_api_controller_auth_usage(): void
    {
        $this->withToken('not-a-valid-sanctum-token')
            ->getJson(route('api.marketplace.index'))
            ->assertOk()
            ->assertJson($this->legacyUnauthorizedPayload());
    }

    public function test_api_cookie_session_and_csrf_headers_do_not_authenticate_bearer_api(): void
    {
        $user = $this->activeUser();

        $this
            ->actingAs($user)
            ->withSession(['_token' => 'csrf-token'])
            ->withHeaders([
                'X-CSRF-TOKEN' => 'csrf-token',
                'X-XSRF-TOKEN' => 'csrf-token',
            ])
            ->getJson(route('api.marketplace.index'))
            ->assertOk()
            ->assertJson($this->legacyUnauthorizedPayload());
    }

    public function test_web_session_cannot_impersonate_api_bearer_authentication(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->withToken('not-a-real-personal-access-token')
            ->getJson(route('api.marketplace.index'))
            ->assertOk()
            ->assertJson($this->legacyUnauthorizedPayload());
    }

    public function test_expired_and_revoked_personal_access_tokens_are_rejected(): void
    {
        $expiredUser = $this->activeUser();
        $expiredToken = $expiredUser->createToken('expired-api-test', ['*'], now()->subMinute())->plainTextToken;

        $this->withToken($expiredToken)
            ->getJson(route('api.marketplace.index'))
            ->assertOk()
            ->assertJson($this->legacyUnauthorizedPayload());

        $revokedUser = $this->activeUser();
        $revokedToken = $revokedUser->createToken('revoked-api-test', ['*']);
        PersonalAccessToken::query()->whereKey($revokedToken->accessToken->getKey())->delete();

        $this->withToken($revokedToken->plainTextToken)
            ->getJson(route('api.marketplace.index'))
            ->assertOk()
            ->assertJson($this->legacyUnauthorizedPayload());
    }

    public function test_api_login_issues_expiring_token_with_explicit_abilities(): void
    {
        $user = $this->activeUser([
            'email' => 'api-login-token@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonStructure([
                'token',
                'token_expires_at',
            ]);

        $token = PersonalAccessToken::query()
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($token);
        $this->assertSame('auth-token', $token->name);
        $this->assertSame($this->apiTokenAbilityValues(), $token->abilities);
        $this->assertNotContains('*', $token->abilities);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isFuture());
        $this->assertSame($token->expires_at->toJSON(), $response->json('token_expires_at'));
    }

    public function test_api_logout_revokes_only_current_personal_access_token(): void
    {
        $user = $this->activeUser();
        $currentToken = $user->createToken('logout-current-token', ['*'], now()->addHour());
        $otherToken = $user->createToken('logout-other-token', ['*'], now()->addHour());

        $this->withToken($currentToken->plainTextToken)
            ->postJson(route('api.auth.logout'))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $currentToken->accessToken->getKey(),
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->accessToken->getKey(),
        ]);

        $this->app->make(AuthManager::class)->forgetGuards();

        $this->withToken($currentToken->plainTextToken)
            ->getJson(route('api.marketplace.index'))
            ->assertOk()
            ->assertJson($this->legacyUnauthorizedPayload());

        $this->withToken($otherToken->plainTextToken)
            ->getJson(route('api.marketplace.index'))
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'No marketplace found',
            ]);
    }

    public function test_api_authentication_audit_document_covers_guard_token_cookie_and_logout_contracts(): void
    {
        $audit = file_get_contents(base_path('docs/api-authentication-audit.md'));

        $this->assertIsString($audit);
        $this->assertStringContainsString('Sanctum personal access tokens', $audit);
        $this->assertStringContainsString('Passport is not installed', $audit);
        $this->assertStringContainsString('Bearer-only API', $audit);
        $this->assertStringContainsString('CSRF', $audit);
        $this->assertStringContainsString('token revocation', $audit);
        $this->assertStringContainsString('logout', $audit);
    }

    public function test_valid_personal_access_token_can_reach_protected_api_route(): void
    {
        $user = $this->activeUser();
        $token = $user->createToken('valid-api-test')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.marketplace.index'))
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'No marketplace found',
            ]);
    }

    /**
     * @return array{success: false, message: string}
     */
    private function legacyUnauthorizedPayload(): array
    {
        return [
            'success' => false,
            'message' => 'Unauthorized access',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activeUser(array $attributes = []): User
    {
        return User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            ...$attributes,
        ]);
    }

    /**
     * @return list<string>
     */
    private function apiTokenAbilityValues(): array
    {
        return array_map(
            static fn (ApiTokenAbility $ability): string => $ability->value,
            ApiTokenAbility::cases()
        );
    }
}
