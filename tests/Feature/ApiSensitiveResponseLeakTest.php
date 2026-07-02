<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiSensitiveResponseLeakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const FORBIDDEN_RESPONSE_KEYS = [
        'access_token',
        'api_key',
        'api_secret',
        'api_token',
        'current_access_token',
        'debug',
        'exception',
        'keys',
        'password',
        'payment_settings',
        'private_key',
        'remember_token',
        'secret',
        'secret_key',
        'secrets',
        'trace',
        'transaction_keys',
    ];

    public function test_login_response_hides_user_password_remember_token_and_payment_settings(): void
    {
        $user = $this->activeGeneralUser([
            'email' => 'login-sensitive@example.com',
            'password' => Hash::make('password'),
            'remember_token' => 'remember-token-value',
        ]);
        $user->forceFill([
            'payment_settings' => json_encode([
                'provider' => 'stripe',
                'secret_key' => 'sk_test_private_login',
            ]),
        ])->save();

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Login successful');

        $payload = $response->json();

        $this->assertArrayHasKey('token', $payload, 'Login should still return the one-time API token for the client.');
        $this->assertNoForbiddenResponseKeys($payload);
        $this->assertStringNotContainsString('remember-token-value', $response->getContent());
        $this->assertStringNotContainsString('sk_test_private_login', $response->getContent());
        $this->assertStringNotContainsString((string) $user->password, $response->getContent());
    }

    public function test_profile_response_hides_payment_settings_and_secret_values(): void
    {
        $user = $this->activeGeneralUser([
            'email' => 'profile-sensitive@example.com',
        ]);
        $user->forceFill([
            'payment_settings' => json_encode([
                'provider' => 'paypal',
                'secret_key' => 'profile-payment-secret',
                'account_email' => 'merchant-private@example.com',
            ]),
        ])->save();

        $response = $this->withToken($this->plainTokenFor($user))
            ->getJson(route('api.me.profile.show'));

        $response->assertOk();

        $this->assertNoForbiddenResponseKeys($response->json());
        $this->assertStringNotContainsString('profile-payment-secret', $response->getContent());
        $this->assertStringNotContainsString('merchant-private@example.com', $response->getContent());
    }

    public function test_other_profile_response_hides_contact_payment_and_internal_account_fields(): void
    {
        $viewer = $this->activeGeneralUser();
        $profile = $this->activeGeneralUser([
            'email' => 'private-profile@example.com',
            'phone' => '+37060000000',
            'address' => 'Private Street 1',
            'date_of_birth' => 946684800,
            'friends' => json_encode([$viewer->id]),
            'lastActive' => now(),
            'timezone' => 'Europe/Vilnius',
        ]);
        $profile->forceFill([
            'payment_settings' => json_encode([
                'provider' => 'razorpay',
                'secret_key' => 'other-profile-payment-secret',
            ]),
        ])->save();

        $response = $this->withToken($this->plainTokenFor($viewer))
            ->getJson(route('api.profiles.show', $profile));

        $response->assertOk();

        $payload = $response->json();

        $this->assertNoForbiddenResponseKeys($payload);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('phone', $payload);
        $this->assertArrayNotHasKey('address', $payload);
        $this->assertArrayNotHasKey('date_of_birth', $payload);
        $this->assertArrayNotHasKey('email_verified_at', $payload);
        $this->assertArrayNotHasKey('friend', $payload);
        $this->assertArrayNotHasKey('lastActive', $payload);
        $this->assertArrayNotHasKey('status', $payload);
        $this->assertArrayNotHasKey('timezone', $payload);
        $this->assertArrayNotHasKey('updated_at', $payload);
        $this->assertArrayNotHasKey('user_role', $payload);
        $this->assertStringNotContainsString('private-profile@example.com', $response->getContent());
        $this->assertStringNotContainsString('+37060000000', $response->getContent());
        $this->assertStringNotContainsString('Private Street 1', $response->getContent());
        $this->assertStringNotContainsString('other-profile-payment-secret', $response->getContent());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activeGeneralUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'timezone' => 'UTC',
        ], $attributes));
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function assertNoForbiddenResponseKeys(array $payload, string $path = ''): void
    {
        foreach ($payload as $key => $value) {
            $keyPath = $path === '' ? (string) $key : $path.'.'.$key;

            $this->assertNotContains(
                strtolower((string) $key),
                self::FORBIDDEN_RESPONSE_KEYS,
                'Forbidden sensitive API response key leaked at '.$keyPath
            );

            if (is_array($value)) {
                $this->assertNoForbiddenResponseKeys($value, $keyPath);
            }
        }
    }

    private function plainTokenFor(User $user): string
    {
        return $user->createToken('api-sensitive-response-test')->plainTextToken;
    }
}
