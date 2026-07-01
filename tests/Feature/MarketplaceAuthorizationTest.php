<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\SavedProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketplaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_guest_is_redirected_from_marketplace_update(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this->post(route('product.update', ['id' => $product->id]), $this->marketplacePayload());

        $response->assertRedirect(route('login'));
    }

    public function test_web_user_cannot_update_another_users_marketplace_product(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($owner, [
            'title' => 'Original owner product',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->post(route('product.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Unauthorized title change',
            ]));

        $response->assertForbidden();

        $product->refresh();

        $this->assertSame($owner->id, (int) $product->user_id);
        $this->assertSame('Original owner product', $product->title);
    }

    public function test_web_owner_can_update_marketplace_product(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this
            ->actingAs($owner)
            ->post(route('product.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Owner updated product',
            ]));

        $response->assertOk();

        $product->refresh();

        $this->assertSame($owner->id, (int) $product->user_id);
        $this->assertSame('Owner updated product', $product->title);
    }

    public function test_web_admin_can_update_marketplace_product_without_taking_ownership(): void
    {
        $owner = $this->activeUser();
        $admin = $this->activeUser(UserRole::Admin);
        $product = $this->marketplace($owner);

        $response = $this
            ->actingAs($admin)
            ->post(route('product.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Admin updated product',
            ]));

        $response->assertOk();

        $product->refresh();

        $this->assertSame($owner->id, (int) $product->user_id);
        $this->assertSame('Admin updated product', $product->title);
    }

    public function test_web_user_cannot_delete_another_users_marketplace_product(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this
            ->actingAs($otherUser)
            ->get(route('product.delete').'?product_id='.$product->id);

        $response->assertForbidden();

        $this->assertDatabaseHas('marketplaces', [
            'id' => $product->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_web_missing_product_delete_returns_not_found(): void
    {
        $owner = $this->activeUser();

        $response = $this
            ->actingAs($owner)
            ->get(route('product.delete').'?product_id=999999');

        $response->assertNotFound();
    }

    public function test_web_user_cannot_load_another_users_marketplace_edit_modal(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this
            ->actingAs($otherUser)
            ->get(route('load_modal_content', [
                'view_path' => 'frontend.marketplace.edit_product',
                'product_id' => $product->id,
            ]));

        $response->assertForbidden();
    }

    public function test_web_saved_products_page_only_shows_current_users_saved_products(): void
    {
        $currentUser = $this->activeUser();
        $otherUser = $this->activeUser();
        $currentProduct = $this->marketplace($currentUser, [
            'title' => 'Current user saved product',
        ]);
        $otherProduct = $this->marketplace($otherUser, [
            'title' => 'Other user saved product',
        ]);

        $this->savedProduct($currentUser, $currentProduct);
        $this->savedProduct($otherUser, $otherProduct);

        $response = $this
            ->actingAs($currentUser)
            ->get(route('product.saved'));

        $response
            ->assertOk()
            ->assertSee('Current user saved product')
            ->assertDontSee('Other user saved product');
    }

    public function test_web_unsave_for_later_deletes_only_current_users_saved_product(): void
    {
        $currentUser = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($otherUser);

        $this->savedProduct($currentUser, $product);
        $this->savedProduct($otherUser, $product);

        $response = $this
            ->actingAs($currentUser)
            ->get(route('unsave.product.later', ['id' => $product->id]));

        $response->assertOk();

        $this->assertDatabaseMissing('saved_products', [
            'user_id' => $currentUser->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('saved_products', [
            'user_id' => $otherUser->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_api_guest_keeps_legacy_unauthorized_response_for_marketplace_update(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this->postJson(route('api.marketplace.update', ['id' => $product->id]), $this->marketplacePayload());

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access',
            ]);
    }

    public function test_api_user_cannot_update_another_users_marketplace_product(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($owner, [
            'title' => 'Original API product',
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this
            ->withToken('test-token')
            ->postJson(route('api.marketplace.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Unauthorized API title change',
            ]));

        $response->assertForbidden();

        $product->refresh();

        $this->assertSame($owner->id, (int) $product->user_id);
        $this->assertSame('Original API product', $product->title);
    }

    public function test_api_admin_can_update_marketplace_product_without_taking_ownership(): void
    {
        $owner = $this->activeUser();
        $admin = $this->activeUser(UserRole::Admin);
        $product = $this->marketplace($owner);

        Sanctum::actingAs($admin);

        $response = $this
            ->withToken('test-token')
            ->postJson(route('api.marketplace.update', ['id' => $product->id]), $this->marketplacePayload([
                'title' => 'Admin API updated product',
            ]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'update successfully',
            ]);

        $product->refresh();

        $this->assertSame($owner->id, (int) $product->user_id);
        $this->assertSame('Admin API updated product', $product->title);
    }

    public function test_api_user_cannot_delete_another_users_marketplace_product(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($owner);

        Sanctum::actingAs($otherUser);

        $response = $this
            ->withToken('test-token')
            ->postJson(route('api.marketplace.destroy', ['product_id' => $product->id]));

        $response->assertForbidden();

        $this->assertDatabaseHas('marketplaces', [
            'id' => $product->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_api_marketplace_listing_scopes_saved_status_to_authenticated_user(): void
    {
        $currentUser = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($otherUser);

        $this->savedProduct($otherUser, $product);

        Sanctum::actingAs($currentUser);

        $response = $this
            ->withToken('test-token')
            ->getJson(route('api.marketplace.index'));

        $response->assertOk();

        $this->assertSame('not_saved', $response->json('0.is_Saved'));

        $this->savedProduct($currentUser, $product);

        $response = $this
            ->withToken('test-token')
            ->getJson(route('api.marketplace.index'));

        $response->assertOk();

        $this->assertSame('saved', $response->json('0.is_Saved'));
    }

    public function test_api_marketplace_filter_scopes_saved_status_to_authenticated_user(): void
    {
        $currentUser = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($otherUser, [
            'title' => 'Filter saved state product',
        ]);

        $this->savedProduct($otherUser, $product);

        Sanctum::actingAs($currentUser);

        $response = $this
            ->withToken('test-token')
            ->getJson(route('api.marketplace.filter', [
                'search' => 'Filter saved state product',
            ]));

        $response->assertOk();

        $this->assertSame('not_saved', $response->json('0.is_Saved'));
    }

    public function test_api_save_for_later_does_not_toggle_another_users_saved_product(): void
    {
        $currentUser = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($otherUser);

        $this->savedProduct($otherUser, $product);

        Sanctum::actingAs($currentUser);

        $response = $this
            ->withToken('test-token')
            ->postJson(route('api.marketplace.saves.store', ['id' => $product->id]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User saved the product',
            ]);

        $this->assertDatabaseHas('saved_products', [
            'user_id' => $currentUser->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('saved_products', [
            'user_id' => $otherUser->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_api_save_for_later_unsaves_only_authenticated_users_existing_save(): void
    {
        $currentUser = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($otherUser);

        $this->savedProduct($currentUser, $product);
        $this->savedProduct($otherUser, $product);

        Sanctum::actingAs($currentUser);

        $response = $this
            ->withToken('test-token')
            ->postJson(route('api.marketplace.saves.store', ['id' => $product->id]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'product unsave successfully',
            ]);

        $this->assertDatabaseMissing('saved_products', [
            'user_id' => $currentUser->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('saved_products', [
            'user_id' => $otherUser->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_api_owner_can_delete_own_marketplace_product(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        Sanctum::actingAs($owner);

        $response = $this
            ->withToken('test-token')
            ->postJson(route('api.marketplace.destroy', ['product_id' => $product->id]));

        $response->assertOk();

        $this->assertDatabaseMissing('marketplaces', [
            'id' => $product->id,
        ]);
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function marketplacePayload(array $overrides = []): array
    {
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        return [
            'title' => 'Updated marketplace product',
            'price' => '25.50',
            'location' => 'Vilnius',
            'category' => $category->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => $brand->id,
            'currency' => $currency->id,
            'buy_link' => 'https://example.com/product',
            'description' => 'Updated marketplace product description.',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function marketplace(User $owner, array $attributes = []): Marketplace
    {
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        $marketplace = new Marketplace;
        $marketplace->forceFill([
            'user_id' => $owner->id,
            'title' => 'Original marketplace product',
            'currency_id' => $currency->id,
            'price' => '15.00',
            'location' => 'Kaunas',
            'category' => (string) $category->id,
            'condition' => 'used',
            'brand' => (string) $brand->id,
            'status' => '1',
            'description' => 'Original marketplace product description.',
            ...$attributes,
        ]);
        $marketplace->save();

        return $marketplace;
    }

    private function savedProduct(User $user, Marketplace $product): SavedProduct
    {
        $savedProduct = new SavedProduct;
        $savedProduct->forceFill([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        $savedProduct->save();

        return $savedProduct;
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
        $category = new Category;
        $category->forceFill(['name' => 'Electronics']);
        $category->save();

        return $category;
    }

    private function brand(): Brand
    {
        $brand = new Brand;
        $brand->forceFill(['name' => 'Acme']);
        $brand->save();

        return $brand;
    }

    private function currency(): Currency
    {
        $currency = new Currency;
        $currency->timestamps = false;
        $currency->forceFill([
            'name' => 'Euro',
            'code' => 'EUR',
            'symbol' => 'EUR',
        ]);
        $currency->save();

        return $currency;
    }
}
