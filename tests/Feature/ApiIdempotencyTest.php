<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_marketplace_create_retries_with_same_idempotency_key_replay_response_without_duplicate_write(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-idempotency-test')->plainTextToken;
        $payload = $this->marketplacePayload([
            'title' => 'Retry Safe Marketplace Product',
        ]);
        $headers = ['Idempotency-Key' => 'marketplace-create-retry-safe'];

        $firstResponse = $this
            ->withToken($token)
            ->withHeaders($headers)
            ->postJson(route('api.marketplace.store'), $payload);
        $retryResponse = $this
            ->withToken($token)
            ->withHeaders($headers)
            ->postJson(route('api.marketplace.store'), $payload);

        $firstResponse
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'false')
            ->assertJson([
                'success' => true,
                'message' => 'Marketplace created successfully',
            ]);
        $retryResponse
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertExactJson($firstResponse->json());

        $this->assertSame(1, Marketplace::query()
            ->where('user_id', $user->id)
            ->where('title', 'Retry Safe Marketplace Product')
            ->count());
    }

    public function test_marketplace_create_rejects_reused_idempotency_key_with_different_payload(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-idempotency-test')->plainTextToken;
        $headers = ['Idempotency-Key' => 'marketplace-create-conflict'];

        $this
            ->withToken($token)
            ->withHeaders($headers)
            ->postJson(route('api.marketplace.store'), $this->marketplacePayload([
                'title' => 'Original Idempotent Product',
            ]))
            ->assertOk();

        $response = $this
            ->withToken($token)
            ->withHeaders($headers)
            ->postJson(route('api.marketplace.store'), $this->marketplacePayload([
                'title' => 'Conflicting Idempotent Product',
            ]));

        $response
            ->assertConflict()
            ->assertJsonPath('error.code', 'CONFLICT')
            ->assertJsonPath('message', 'Idempotency key was already used with a different request.');

        $this->assertDatabaseHas('marketplaces', [
            'user_id' => $user->id,
            'title' => 'Original Idempotent Product',
        ]);
        $this->assertDatabaseMissing('marketplaces', [
            'user_id' => $user->id,
            'title' => 'Conflicting Idempotent Product',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function marketplacePayload(array $overrides = []): array
    {
        return [
            'title' => 'API idempotent marketplace product',
            'price' => '25.50',
            'location' => 'Vilnius',
            'category' => Category::factory()->electronics()->create()->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => Brand::factory()->acme()->create()->id,
            'currency' => Currency::factory()->euro()->create()->id,
            'buy_link' => 'https://example.com/product',
            'description' => 'Marketplace product submitted through an idempotent API request.',
            ...$overrides,
        ];
    }
}
