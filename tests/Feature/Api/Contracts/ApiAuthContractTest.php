<?php

namespace Tests\Feature\Api\Contracts;

use App\Enums\ApiTokenAbility;
use Illuminate\Support\Facades\Hash;

class ApiAuthContractTest extends ApiContractTestCase
{
    public function test_public_auth_endpoints_can_be_hit_without_api_token(): void
    {
        $this->postJson(route('api.auth.login'), [])
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                    'password',
                ],
            ]);

        $this->postJson(route('api.auth.signup'), [])
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'name',
                    'email',
                    'password',
                ],
            ]);

        $this->postJson(route('api.password.forgot'), [])
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    public function test_protected_endpoints_reject_missing_and_invalid_bearer_tokens_with_legacy_payload(): void
    {
        $this->getJson(route('api.me.profile.show'))
            ->assertOk()
            ->assertJson($this->legacyAuthenticationPayload());

        $this->withToken('invalid-token')
            ->getJson(route('api.me.profile.show'))
            ->assertOk()
            ->assertJson($this->legacyAuthenticationPayload());
    }

    public function test_valid_api_token_reaches_representative_protected_endpoint_groups(): void
    {
        $user = $this->activeApiUser();
        $headers = $this->apiHeaders($user);
        $this->pageFor($user);
        $this->groupFor($user);
        $this->marketplaceFor($user);
        $this->eventFor($user);
        $this->blogFor($user);

        foreach ([
            'profile' => route('api.me.profile.show'),
            'feed' => route('api.timeline.index'),
            'friends' => route('api.friends.index'),
            'pages' => route('api.pages.index'),
            'groups' => route('api.groups.index'),
            'marketplace' => route('api.marketplace.index'),
            'events' => route('api.events.index'),
            'blogs' => route('api.blogs.index'),
            'notifications' => route('api.notifications.index'),
            'chat' => route('api.chat.index'),
        ] as $module => $url) {
            $this->getJson($url, $headers)
                ->assertOk();

            auth()->forgetGuards();
        }
    }

    public function test_invalid_login_returns_current_error_shape(): void
    {
        $this->postJson(route('api.auth.login'), [
            'email' => 'missing-user@example.com',
            'password' => 'password',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'User not found!',
            ]);

        $user = $this->activeApiUser([
            'email' => 'api-contract-login@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials!',
            ]);
    }

    public function test_valid_general_user_login_returns_legacy_token_contract(): void
    {
        $user = $this->activeApiUser([
            'email' => 'api-contract-success@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'user',
                'user_id',
                'user_image',
                'cover_photo',
                'token',
                'token_expires_at',
            ])
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('user_id', $user->id);
    }

    public function test_signup_validation_failure_returns_current_validation_shape(): void
    {
        $this->postJson(route('api.auth.signup'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'name',
                    'email',
                    'password',
                ],
            ]);
    }

    public function test_forgot_password_validation_failure_returns_laravel_validation_shape(): void
    {
        $this->postJson(route('api.password.forgot'), [
            'email' => 'not-an-email',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    public function test_logout_with_valid_token_returns_success_and_revokes_current_token(): void
    {
        $user = $this->activeApiUser();
        $token = $user->createToken('api-contract-logout', [
            ApiTokenAbility::MarketplaceCreate->value,
            ApiTokenAbility::MarketplaceUpdate->value,
            ApiTokenAbility::MarketplaceDelete->value,
        ]);

        $this->withToken($token->plainTextToken)
            ->postJson(route('api.auth.logout'))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }
}
