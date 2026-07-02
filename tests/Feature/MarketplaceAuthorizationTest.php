<?php

namespace Tests\Feature;

use App\Enums\ApiTokenAbility;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\MarketplaceController;
use App\Http\Requests\Marketplace\DestroyMarketplaceRequest;
use App\Http\Requests\Marketplace\StoreMarketplaceRequest;
use App\Http\Requests\Marketplace\UpdateMarketplaceRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\SavedProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use ReflectionMethod;
use Tests\TestCase;

class MarketplaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_marketplace_write_actions_use_form_request_authorization(): void
    {
        $this->assertControllerRequestType('store', StoreMarketplaceRequest::class);
        $this->assertControllerRequestType('update', UpdateMarketplaceRequest::class);
        $this->assertControllerRequestType('product_delete', DestroyMarketplaceRequest::class);
    }

    public function test_web_guest_is_redirected_from_marketplace_store(): void
    {
        $response = $this->post(route('product.store'), $this->marketplacePayload());

        $response->assertRedirect(route('login'));
    }

    public function test_web_guest_is_redirected_from_marketplace_update(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this->post(route('product.update', ['id' => $product->id]), $this->marketplacePayload());

        $response->assertRedirect(route('login'));
    }

    public function test_web_guest_is_redirected_from_marketplace_delete(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this->get(route('product.delete').'?product_id='.$product->id);

        $response->assertRedirect(route('login'));
    }

    public function test_web_marketplace_store_validation_keeps_legacy_ok_error_shape(): void
    {
        $user = $this->activeUser();

        $response = $this
            ->actingAs($user)
            ->post(route('product.store'), [
                'title' => '',
                'price' => '',
                'location' => '',
                'condition' => '',
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'title',
                    'price',
                    'location',
                    'condition',
                ],
            ]);
    }

    public function test_web_marketplace_store_rejects_non_decimal_price(): void
    {
        $user = $this->activeUser();

        $response = $this
            ->actingAs($user)
            ->post(route('product.store'), $this->marketplacePayload([
                'price' => 'free',
            ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'price',
                ],
            ]);
    }

    public function test_web_marketplace_update_validation_still_runs_before_model_authorization(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this
            ->actingAs($otherUser)
            ->post(route('product.update', ['id' => $product->id]), [
                'title' => '',
                'price' => '',
                'location' => '',
                'condition' => '',
                'status' => '',
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'title',
                    'price',
                    'location',
                    'condition',
                    'status',
                ],
            ]);
    }

    public function test_web_marketplace_update_rejects_more_than_two_decimal_places(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this
            ->actingAs($owner)
            ->post(route('product.update', ['id' => $product->id]), $this->marketplacePayload([
                'price' => '25.555',
            ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'price',
                ],
            ]);
    }

    public function test_web_marketplace_delete_with_missing_id_keeps_not_found_status(): void
    {
        $user = $this->activeUser();

        $response = $this
            ->actingAs($user)
            ->get(route('product.delete'));

        $response->assertNotFound();
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

    public function test_marketplace_policy_allows_non_owner_to_message_seller_and_denies_owner(): void
    {
        $owner = $this->activeUser();
        $buyer = $this->activeUser();
        $product = $this->marketplace($owner);

        $this->assertFalse(Gate::forUser($owner)->allows('messageSeller', $product));
        $this->assertTrue(Gate::forUser($buyer)->allows('messageSeller', $product));
    }

    public function test_single_product_page_uses_policy_visibility_for_seller_message_action(): void
    {
        $owner = $this->activeUser();
        $buyer = $this->activeUser();
        $product = $this->marketplace($owner, [
            'title' => 'Policy visible product',
        ]);
        $chatUrl = route('chat', [
            'receiver' => $owner->id,
            'product' => $product->id,
        ]);

        $this
            ->actingAs($owner)
            ->get(route('single.product', ['id' => $product->id]))
            ->assertOk()
            ->assertSee('Sold')
            ->assertDontSee($chatUrl);

        $this
            ->actingAs($buyer)
            ->get(route('single.product', ['id' => $product->id]))
            ->assertOk()
            ->assertSee('Message')
            ->assertSee($chatUrl);
    }

    public function test_web_owner_cannot_open_chat_for_their_own_marketplace_product(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this
            ->actingAs($owner)
            ->get(route('chat', [
                'receiver' => $owner->id,
                'product' => $product->id,
            ]));

        $response->assertForbidden();
    }

    public function test_web_user_cannot_open_marketplace_chat_with_mismatched_receiver(): void
    {
        $seller = $this->activeUser();
        $otherUser = $this->activeUser();
        $buyer = $this->activeUser();
        $product = $this->marketplace($seller);

        $response = $this
            ->actingAs($buyer)
            ->get(route('chat', [
                'receiver' => $otherUser->id,
                'product' => $product->id,
            ]));

        $response->assertForbidden();
    }

    public function test_web_user_can_open_chat_for_marketplace_seller(): void
    {
        $seller = $this->activeUser();
        $buyer = $this->activeUser();
        $product = $this->marketplace($seller);

        $response = $this
            ->actingAs($buyer)
            ->get(route('chat', [
                'receiver' => $seller->id,
                'product' => $product->id,
            ]));

        $response->assertOk();
    }

    public function test_web_user_cannot_send_marketplace_chat_message_to_wrong_receiver(): void
    {
        $seller = $this->activeUser();
        $otherUser = $this->activeUser();
        $buyer = $this->activeUser();
        $product = $this->marketplace($seller);

        $response = $this
            ->actingAs($buyer)
            ->post(route('chat.save'), [
                'receiver_id' => $otherUser->id,
                'product_id' => $product->id,
                'message' => 'Is this still available?',
                'messagecenter' => null,
                'thumbsup' => 0,
            ]);

        $response->assertForbidden();
    }

    public function test_web_owner_cannot_send_marketplace_chat_message_to_themselves(): void
    {
        $owner = $this->activeUser();
        $product = $this->marketplace($owner);

        $response = $this
            ->actingAs($owner)
            ->post(route('chat.save'), [
                'receiver_id' => $owner->id,
                'product_id' => $product->id,
                'message' => 'Is my product still available?',
                'messagecenter' => null,
                'thumbsup' => 0,
            ]);

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

        $response = $this
            ->withToken($this->apiTokenFor($otherUser, [ApiTokenAbility::MarketplaceUpdate]))
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

        $response = $this
            ->withToken($this->apiTokenFor($admin, [ApiTokenAbility::MarketplaceUpdate]))
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

        $response = $this
            ->withToken($this->apiTokenFor($otherUser, [ApiTokenAbility::MarketplaceDelete]))
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

        $response = $this
            ->withToken($this->apiTokenFor($currentUser))
            ->getJson(route('api.marketplace.index'));

        $response->assertOk();

        $this->assertSame('not_saved', $response->json('0.is_Saved'));

        $this->savedProduct($currentUser, $product);

        $response = $this
            ->withToken($this->apiTokenFor($currentUser))
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

        $response = $this
            ->withToken($this->apiTokenFor($currentUser))
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

        $response = $this
            ->withToken($this->apiTokenFor($currentUser))
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

        $response = $this
            ->withToken($this->apiTokenFor($currentUser))
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

    public function test_api_unsave_for_later_deletes_only_authenticated_users_saved_product(): void
    {
        $currentUser = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($otherUser);

        $this->savedProduct($currentUser, $product);
        $this->savedProduct($otherUser, $product);

        $response = $this
            ->withToken($this->apiTokenFor($currentUser))
            ->postJson(route('api.marketplace.saves.destroy', ['id' => $product->id]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'unsave successfully',
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

        $response = $this
            ->withToken($this->apiTokenFor($owner, [ApiTokenAbility::MarketplaceDelete]))
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
            'timezone' => 'UTC',
        ]);
    }

    private function assertControllerRequestType(string $method, string $requestClass): void
    {
        $parameterType = (new ReflectionMethod(MarketplaceController::class, $method))
            ->getParameters()[0]
            ->getType();

        $this->assertSame($requestClass, $parameterType?->getName());
        $this->assertTrue(method_exists($requestClass, 'authorize'));
    }

    /**
     * @param  list<ApiTokenAbility|string>  $abilities
     */
    private function apiTokenFor(User $user, array $abilities = ['*']): string
    {
        return $user->createToken('api-test', array_map(
            static fn (ApiTokenAbility|string $ability): string => $ability instanceof ApiTokenAbility
                ? $ability->value
                : $ability,
            $abilities,
        ))->plainTextToken;
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

    private function savedProduct(User $user, Marketplace $product): SavedProduct
    {
        return SavedProduct::factory()
            ->forUser($user)
            ->forProduct($product)
            ->create();
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
