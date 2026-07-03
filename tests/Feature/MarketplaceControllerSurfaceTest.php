<?php

namespace Tests\Feature;

use App\Enums\MediaFileType;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\MarketplaceController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\MediaFile;
use App\Models\SavedProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class MarketplaceControllerSurfaceTest extends TestCase
{
    use RefreshDatabase;

    private const METHODS = [
        'allproducts',
        'userproduct',
        'store',
        'update',
        'product_delete',
        'load_product_by_scrolling',
        'single_product',
        'filter',
        'saved_product',
        'save_for_later',
        'unsave_for_later',
        'single_product_ifrane',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string}>
     */
    private const ROUTES = [
        'allproducts' => ['allproducts', ['GET', 'HEAD'], 'products'],
        'userproduct' => ['userproduct', ['GET', 'HEAD'], 'user/product'],
        'product.store' => ['store', ['POST'], 'product/store'],
        'product.update' => ['update', ['POST'], 'update/product/{id}'],
        'product.delete' => ['product_delete', ['GET', 'HEAD'], 'product/delete'],
        'load_product_by_scrolling' => ['load_product_by_scrolling', ['GET', 'HEAD'], 'load_product_by_scrolling'],
        'single.product' => ['single_product', ['GET', 'HEAD'], 'product/view/{id}'],
        'filter.product' => ['filter', ['GET', 'HEAD'], 'product/filter/{max?}/{min?}/{location?}'],
        'product.saved' => ['saved_product', ['GET', 'HEAD'], 'product/saved'],
        'save.product.later' => ['save_for_later', ['GET', 'HEAD'], 'save/product/{id}'],
        'unsave.product.later' => ['unsave_for_later', ['GET', 'HEAD'], 'unsave/product/{id}'],
        'single.product.iframe' => ['single_product_ifrane', ['GET', 'HEAD'], 'product/iframe/view/{id}'],
    ];

    public function test_requested_marketplace_controller_methods_stay_public(): void
    {
        $controller = new ReflectionClass(MarketplaceController::class);

        foreach (self::METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "MarketplaceController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "MarketplaceController::{$method} should stay public.");
        }
    }

    public function test_requested_marketplace_routes_keep_expected_actions_methods_and_uris(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(MarketplaceController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");
        }
    }

    public function test_listing_routes_render_marketplace_products_for_the_current_view(): void
    {
        $viewer = $this->activeUser();
        $seller = $this->activeUser();
        $viewerProduct = $this->marketplace($viewer, ['title' => 'Viewer Listing']);
        $sellerProduct = $this->marketplace($seller, ['title' => 'Seller Listing']);

        $this
            ->actingAs($viewer)
            ->get(route('allproducts'))
            ->assertOk()
            ->assertSee($viewerProduct->title)
            ->assertSee($sellerProduct->title);

        $this
            ->actingAs($viewer)
            ->get(route('userproduct'))
            ->assertOk()
            ->assertSee($viewerProduct->title)
            ->assertDontSee($sellerProduct->title);

        $this
            ->actingAs($viewer)
            ->get(route('load_product_by_scrolling', ['offset' => 0]))
            ->assertOk()
            ->assertSee($viewerProduct->title)
            ->assertSee($sellerProduct->title);
    }

    public function test_marketplace_filter_handles_partial_query_parameters_and_status_scope(): void
    {
        $viewer = $this->activeUser();
        $matchingProduct = $this->marketplace($viewer, [
            'title' => 'Camera Match',
            'description' => 'A tested marketplace camera.',
            'condition' => 'used',
            'location' => 'Vilnius',
            'price' => '25.00',
            'status' => '1',
        ]);
        $this->marketplace($viewer, [
            'title' => 'Hidden Camera',
            'description' => 'This inactive item should not render.',
            'condition' => 'used',
            'location' => 'Vilnius',
            'price' => '20.00',
            'status' => '0',
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('filter.product').'?'.http_build_query([
                'search' => 'Camera',
                'min' => '10',
            ]));

        $response
            ->assertOk()
            ->assertSee($matchingProduct->title)
            ->assertDontSee('Hidden Camera');
    }

    public function test_store_update_and_delete_routes_mutate_marketplace_products(): void
    {
        $viewer = $this->activeUser();
        $deleteProduct = $this->marketplace($viewer, [
            'title' => 'Feature marketplace delete target',
            'image' => 'feature-marketplace-delete.jpg',
        ]);
        $this->putProductImage($deleteProduct->image);

        $storeResponse = $this
            ->actingAs($viewer)
            ->post(route('product.store'), $this->marketplacePayload([
                'title' => 'Feature marketplace created product',
            ]));

        $storeResponse
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $createdProduct = Marketplace::query()
            ->where('user_id', $viewer->id)
            ->where('title', 'Feature marketplace created product')
            ->firstOrFail();

        $updateResponse = $this
            ->actingAs($viewer)
            ->post(route('product.update', ['id' => $createdProduct->id]), $this->marketplacePayload([
                'title' => 'Feature marketplace updated product',
                'price' => '77.25',
            ]));

        $updateResponse
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $createdProduct->refresh();
        $this->assertSame('Feature marketplace updated product', $createdProduct->title);
        $this->assertSame('77.25', $createdProduct->price);

        $deleteResponse = $this
            ->actingAs($viewer)
            ->get(route('product.delete', ['product_id' => $deleteProduct->id]));

        $deleteResponse
            ->assertOk()
            ->assertJson([
                'alertMessage' => 'Product Deleted Successfully',
                'fadeOutElem' => '#product-'.$deleteProduct->id,
            ]);

        $this->assertDatabaseMissing('marketplaces', ['id' => $deleteProduct->id]);
        $this->assertFileDoesNotExist(public_path('storage/marketplace/coverphoto/'.$deleteProduct->image));
        $this->assertFileDoesNotExist(public_path('storage/marketplace/thumbnail/'.$deleteProduct->image));
    }

    public function test_saved_product_routes_scope_to_the_authenticated_user(): void
    {
        $viewer = $this->activeUser();
        $seller = $this->activeUser();
        $product = $this->marketplace($seller, ['title' => 'Feature saved marketplace product']);
        $otherProduct = $this->marketplace($seller, ['title' => 'Other user saved marketplace product']);
        $this->savedProduct($seller, $otherProduct);

        $saveResponse = $this
            ->actingAs($viewer)
            ->get(route('save.product.later', ['id' => $product->id]));

        $saveResponse
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this->assertDatabaseHas('saved_products', [
            'user_id' => $viewer->id,
            'product_id' => $product->id,
        ]);

        $this
            ->actingAs($viewer)
            ->get(route('product.saved'))
            ->assertOk()
            ->assertSee($product->title)
            ->assertDontSee($otherProduct->title);

        $unsaveResponse = $this
            ->actingAs($viewer)
            ->get(route('unsave.product.later', ['id' => $product->id]));

        $unsaveResponse
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this->assertDatabaseMissing('saved_products', [
            'user_id' => $viewer->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('saved_products', [
            'user_id' => $seller->id,
            'product_id' => $otherProduct->id,
        ]);
    }

    public function test_single_product_and_iframe_routes_render_and_redirect_expected_contracts(): void
    {
        $seller = $this->activeUser();
        $viewer = $this->activeUser();
        $product = $this->marketplace($seller, [
            'title' => 'Feature single marketplace product',
            'description' => 'Feature single marketplace description.',
        ]);
        $this->productImage($viewer, $product);

        $this
            ->actingAs($viewer)
            ->get(route('single.product', ['id' => $product->id]))
            ->assertOk()
            ->assertSee($product->title)
            ->assertSee('Feature single marketplace description.');

        $this
            ->actingAs($viewer)
            ->get(route('single.product.iframe', ['id' => $product->id, 'shared' => 1]))
            ->assertOk()
            ->assertSee($product->title);

        $this
            ->actingAs($viewer)
            ->get(route('single.product.iframe', ['id' => $product->id]))
            ->assertRedirect(route('single.product', ['id' => $product->id]));
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function marketplace(User $owner, array $overrides = []): Marketplace
    {
        [$category, $brand, $currency] = $this->lookups();

        return Marketplace::factory()
            ->forOwner($owner)
            ->forCategory($category)
            ->forBrand($brand)
            ->forCurrency($currency)
            ->used()
            ->active()
            ->create([
                'title' => 'Feature marketplace product',
                'price' => '15.00',
                'location' => 'Kaunas',
                'description' => 'Feature marketplace product description.',
                ...$overrides,
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function marketplacePayload(array $overrides = []): array
    {
        [$category, $brand, $currency] = $this->lookups();

        return [
            'title' => 'Feature marketplace product payload',
            'price' => '25.50',
            'location' => 'Vilnius',
            'category' => $category->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => $brand->id,
            'currency' => $currency->id,
            'buy_link' => 'https://example.com/product',
            'description' => 'Feature marketplace payload description.',
            ...$overrides,
        ];
    }

    private function savedProduct(User $user, Marketplace $product): SavedProduct
    {
        return SavedProduct::factory()
            ->forUser($user)
            ->forProduct($product)
            ->create();
    }

    private function productImage(User $user, Marketplace $product): MediaFile
    {
        return MediaFile::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'file_name' => 'feature-marketplace-product.jpg',
            'file_type' => MediaFileType::Image->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function putProductImage(string $fileName): void
    {
        File::ensureDirectoryExists(public_path('storage/marketplace/coverphoto'));
        File::ensureDirectoryExists(public_path('storage/marketplace/thumbnail'));
        File::put(public_path('storage/marketplace/coverphoto/'.$fileName), 'feature marketplace cover');
        File::put(public_path('storage/marketplace/thumbnail/'.$fileName), 'feature marketplace thumb');
    }

    /**
     * @return array{Category, Brand, Currency}
     */
    private function lookups(): array
    {
        return [
            Category::factory()->create(['name' => 'Feature Electronics '.str()->uuid()]),
            Brand::factory()->create(['name' => 'Feature Brand '.str()->uuid()]),
            Currency::factory()->create([
                'name' => 'Feature Euro',
                'code' => 'EUR',
                'symbol' => 'EUR',
                'paypal_supported' => true,
                'stripe_supported' => true,
            ]),
        ];
    }
}
