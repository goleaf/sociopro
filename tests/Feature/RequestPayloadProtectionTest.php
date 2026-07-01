<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Payment_gateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestPayloadProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_payment_gateway_update_ignores_unexpected_sensitive_fields(): void
    {
        $admin = User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::Admin->value,
        ]);
        $gateway = Payment_gateway::create([
            'identifier' => 'custom_gateway',
            'currency' => 'USD',
            'title' => 'Custom Gateway',
            'description' => 'Custom gateway',
            'keys' => json_encode([
                'public_key' => 'old-public',
                'secret_key' => 'old-secret',
            ]),
            'model_name' => 'CustomGateway',
            'status' => '1',
            'is_addon' => '0',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payment_gateway.update', ['id' => $gateway->id]), [
                'currency' => 'EUR',
                'public_key' => 'new-public',
                'secret_key' => 'new-secret',
                'status' => '0',
                'identifier' => 'tampered_gateway',
                'unexpected_secret' => 'do-not-store',
            ])
            ->assertRedirect(route('admin.settings.payment'));

        $gateway->refresh();
        $keys = json_decode((string) $gateway->keys, true);

        $this->assertSame('EUR', $gateway->currency);
        $this->assertSame('1', (string) $gateway->status);
        $this->assertSame([
            'public_key' => 'new-public',
            'secret_key' => 'new-secret',
        ], $keys);
    }
}
