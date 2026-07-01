<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function activeUser(): User
    {
        return User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
        ]);
    }
}
