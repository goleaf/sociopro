<?php

namespace Tests\Feature;

use App\Enums\ApiTokenAbility;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenAbilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_create_requires_create_token_ability(): void
    {
        $user = $this->activeUser();

        $response = $this
            ->withToken($this->plainTokenFor($user, [ApiTokenAbility::MarketplaceUpdate]))
            ->postJson(route('api.marketplace.store'), $this->marketplacePayload([
                'title' => 'Blocked marketplace create',
            ]));

        $response->assertForbidden();

        $this->assertDatabaseMissing('marketplaces', [
            'title' => 'Blocked marketplace create',
        ]);
    }

    public function test_marketplace_update_requires_update_token_ability(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner, ['title' => 'Original token product']);

        $response = $this
            ->withToken($this->plainTokenFor($owner, [ApiTokenAbility::MarketplaceCreate]))
            ->postJson(route('api.marketplace.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Blocked token update',
            ]));

        $response->assertForbidden();

        $this->assertSame('Original token product', $product->refresh()->title);
    }

    public function test_marketplace_delete_requires_delete_token_ability(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this
            ->withToken($this->plainTokenFor($owner, [ApiTokenAbility::MarketplaceUpdate]))
            ->postJson(route('api.marketplace.destroy', ['product_id' => $product->id]));

        $response->assertForbidden();

        $this->assertDatabaseHas('marketplaces', [
            'id' => $product->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_marketplace_update_accepts_owned_unexpired_token_with_required_ability(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner, ['title' => 'Original allowed product']);

        $response = $this
            ->withToken($this->plainTokenFor($owner, [ApiTokenAbility::MarketplaceUpdate], now()->addHour()))
            ->postJson(route('api.marketplace.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Allowed token update',
            ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'update successfully',
            ]);

        $this->assertSame('Allowed token update', $product->refresh()->title);
    }

    public function test_token_owner_cannot_update_another_users_marketplace_product_even_with_required_ability(): void
    {
        $owner = $this->activeUser();
        $attacker = $this->activeUser();
        $product = $this->marketplace($owner, ['title' => 'Owned product']);

        $response = $this
            ->withToken($this->plainTokenFor($attacker, [ApiTokenAbility::MarketplaceUpdate]))
            ->postJson(route('api.marketplace.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Cross-owner update',
            ]));

        $response->assertForbidden();

        $this->assertSame('Owned product', $product->refresh()->title);
    }

    public function test_expired_marketplace_token_is_rejected_without_mutating_product(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner, ['title' => 'Original expired product']);

        $response = $this
            ->withToken($this->plainTokenFor($owner, [ApiTokenAbility::MarketplaceUpdate], now()->subMinute()))
            ->postJson(route('api.marketplace.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Expired token update',
            ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access',
            ]);

        $this->assertSame('Original expired product', $product->refresh()->title);
    }

    public function test_revoked_marketplace_token_is_rejected_without_mutating_product(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner, ['title' => 'Original revoked product']);
        $token = $owner->createToken('api-test', [ApiTokenAbility::MarketplaceUpdate->value]);
        $token->accessToken->delete();

        $response = $this
            ->withToken($token->plainTextToken)
            ->postJson(route('api.marketplace.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Revoked token update',
            ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access',
            ]);

        $this->assertSame('Original revoked product', $product->refresh()->title);
    }

    public function test_web_session_without_personal_access_token_cannot_use_marketplace_api_update(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner, ['title' => 'Original web session product']);

        $response = $this
            ->actingAs($owner)
            ->postJson(route('api.marketplace.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Web session update',
            ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access',
            ]);

        $this->assertSame('Original web session product', $product->refresh()->title);
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
        ]);
    }

    /**
     * @param  list<ApiTokenAbility>  $abilities
     */
    private function plainTokenFor(User $user, array $abilities, ?DateTimeInterface $expiresAt = null): string
    {
        return $user->createToken('api-test', array_map(
            static fn (ApiTokenAbility $ability): string => $ability->value,
            $abilities
        ), $expiresAt)->plainTextToken;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function marketplacePayload(array $overrides = []): array
    {
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        return [
            'title' => 'API token marketplace product',
            'price' => '25.50',
            'location' => 'Vilnius',
            'category' => $category->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => $brand->id,
            'currency' => $currency->id,
            'buy_link' => 'https://example.com/product',
            'description' => 'Marketplace product updated through token tests.',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function marketplace(User $owner, array $attributes = []): Marketplace
    {
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        return Marketplace::factory()
            ->forOwner($owner)
            ->forCategory($category)
            ->forBrand($brand)
            ->forCurrency($currency)
            ->used()
            ->active()
            ->create([
                'title' => 'Original marketplace product',
                'price' => '15.00',
                'location' => 'Kaunas',
                'description' => 'Original marketplace product description.',
                ...$attributes,
            ]);
    }

    /**
     * @return array{Category, Brand, Currency}
     */
    private function createMarketplaceLookups(): array
    {
        return [
            $this->category(),
            $this->brand(),
            $this->currency(),
        ];
    }

    private function category(): Category
    {
        return Category::factory()->electronics()->create();
    }

    private function brand(): Brand
    {
        return Brand::factory()->acme()->create();
    }

    private function currency(): Currency
    {
        return Currency::factory()->euro()->create();
    }
}
