<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\Media_files;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiMarketplaceValidationTest extends TestCase
{
    use RefreshDatabase;

    private string $apiToken;

    public function test_marketplace_filter_rejects_invalid_flat_query_filters_with_legacy_error_shape(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken($this->apiToken)->getJson($this->apiMarketplaceFilterUrl([
            'search' => str_repeat('a', 256),
            'category' => 'not-a-category-id',
            'condition' => 'refurbished',
            'min' => 'cheap',
            'max' => '10',
            'brand' => 'not-a-brand-id',
            'location' => str_repeat('b', 256),
            'sort' => 'password',
            'direction' => 'sideways',
            'page' => 0,
            'per_page' => 101,
            'date_from' => now()->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
        ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'search',
                    'category',
                    'condition',
                    'min',
                    'brand',
                    'location',
                    'sort',
                    'direction',
                    'page',
                    'per_page',
                    'date_to',
                ],
            ]);
    }

    public function test_marketplace_filter_rejects_overlong_search_query_with_legacy_error_shape(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken($this->apiToken)->getJson($this->apiMarketplaceFilterUrl([
            'search' => str_repeat('a', 121),
            'filters' => [
                'search' => str_repeat('b', 121),
            ],
        ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'search',
                    'filters.search',
                ],
            ]);
    }

    public function test_marketplace_filter_rejects_invalid_nested_query_filters_with_legacy_error_shape(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken($this->apiToken)->getJson($this->apiMarketplaceFilterUrl([
            'filters' => [
                'category' => 'not-a-category-id',
                'condition' => 'broken',
                'price' => [
                    'min' => 'free',
                    'max' => '10',
                ],
                'created_between' => [
                    'from' => now()->toDateString(),
                    'to' => now()->subDay()->toDateString(),
                ],
            ],
        ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'filters.category',
                    'filters.condition',
                    'filters.price.min',
                    'filters.created_between.to',
                ],
            ]);
    }

    public function test_marketplace_filter_rejects_money_filters_with_more_than_two_decimal_places(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken($this->apiToken)->getJson($this->apiMarketplaceFilterUrl([
            'min' => '10.999',
            'filters' => [
                'price' => [
                    'max' => '99.999',
                ],
            ],
        ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'min',
                    'filters.price.max',
                ],
            ]);
    }

    public function test_marketplace_filter_uses_default_pagination_and_safe_default_ordering(): void
    {
        $owner = $this->authenticateApiUser();
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        $latestVisibleProduct = null;
        for ($index = 1; $index <= 25; $index++) {
            $latestVisibleProduct = $this->marketplace([
                'user_id' => $owner->id,
                'title' => sprintf('Visible Product %02d', $index),
                'price' => (string) (100 + $index),
                'location' => 'Vilnius',
                'category' => (string) $category->id,
                'condition' => 'new',
                'status' => '1',
                'brand' => (string) $brand->id,
                'currency_id' => $currency->id,
                'description' => 'Visible product for default pagination.',
            ]);
        }

        $response = $this->withToken($this->apiToken)->getJson(route('api.marketplace.filter'));

        $response->assertOk();

        $products = $response->json();

        $this->assertCount(20, $products);
        $this->assertSame($latestVisibleProduct->id, $products[0]['id']);
    }

    public function test_marketplace_index_uses_default_pagination_while_preserving_array_shape(): void
    {
        $owner = $this->authenticateApiUser();
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        $latestProduct = null;
        for ($index = 1; $index <= 25; $index++) {
            $latestProduct = $this->marketplace([
                'user_id' => $owner->id,
                'title' => sprintf('Index Product %02d', $index),
                'price' => (string) (200 + $index),
                'location' => 'Vilnius',
                'category' => (string) $category->id,
                'condition' => 'new',
                'status' => '1',
                'brand' => (string) $brand->id,
                'currency_id' => $currency->id,
                'description' => 'Marketplace index pagination fixture.',
            ]);
        }

        $response = $this->withToken($this->apiToken)->getJson(route('api.marketplace.index'));

        $response->assertOk();

        $products = $response->json();

        $this->assertIsArray($products);
        $this->assertArrayNotHasKey('data', $products);
        $this->assertCount(20, $products);
        $this->assertSame($latestProduct->id, $products[0]['id']);

        $secondPage = $this->withToken($this->apiToken)
            ->getJson(route('api.marketplace.index', ['page' => 2]));

        $secondPage->assertOk();
        $this->assertCount(5, $secondPage->json());
    }

    public function test_create_marketplace_rejects_invalid_json_body_without_creating_product(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken($this->apiToken)->postJson(route('api.marketplace.store'), [
            'title' => 'Bad API Marketplace Payload',
            'price' => 'free',
            'location' => 'Vilnius',
            'category' => 999999,
            'condition' => 'ancient',
            'status' => 7,
            'brand' => 999999,
            'currency' => 999999,
            'buy_link' => 'not-a-url',
            'description' => ['unexpected' => 'array'],
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'price',
                    'category',
                    'condition',
                    'status',
                    'brand',
                    'currency',
                    'buy_link',
                    'description',
                ],
            ]);

        $this->assertDatabaseMissing('marketplaces', [
            'title' => 'Bad API Marketplace Payload',
        ]);
    }

    public function test_marketplace_payload_validation_messages_are_standardized(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken($this->apiToken)->postJson(route('api.marketplace.store'), [
            'title' => '',
            'price' => 'free',
            'condition' => 'ancient',
            'status' => 7,
            'multiple_files' => [
                UploadedFile::fake()->create('payload.pdf', 12, 'application/pdf'),
            ],
        ]);

        $response->assertOk();

        $errors = $response->json('validationError');

        $this->assertSame('The marketplace title field is required.', $errors['title'][0]);
        $this->assertSame('The marketplace price must be a number.', $errors['price'][0]);
        $this->assertSame('The marketplace condition must be one of the following values: new, used.', $errors['condition'][0]);
        $this->assertSame('The marketplace status must be one of the following values: 0, 1.', $errors['status'][0]);
        $this->assertSame('Each marketplace image must be an image.', $errors['multiple_files.0'][0]);
    }

    public function test_create_marketplace_rejects_money_with_more_than_two_decimal_places(): void
    {
        $this->authenticateApiUser();
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        $response = $this->withToken($this->apiToken)->postJson(route('api.marketplace.store'), [
            'title' => 'Bad Precision API Marketplace Payload',
            'price' => '25.555',
            'location' => 'Vilnius',
            'category' => $category->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => $brand->id,
            'currency' => $currency->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'price',
                ],
            ]);

        $this->assertDatabaseMissing('marketplaces', [
            'title' => 'Bad Precision API Marketplace Payload',
        ]);
    }

    public function test_create_marketplace_rejects_invalid_upload_array_before_creating_product(): void
    {
        $this->authenticateApiUser();
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        $response = $this->withToken($this->apiToken)->post(route('api.marketplace.store'), [
            'title' => 'Bad API Marketplace Upload',
            'price' => '25.50',
            'location' => 'Vilnius',
            'category' => $category->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => $brand->id,
            'currency' => $currency->id,
            'multiple_files' => [
                UploadedFile::fake()->create('payload.pdf', 12, 'application/pdf'),
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'multiple_files.0',
                ],
            ]);

        $this->assertDatabaseMissing('marketplaces', [
            'title' => 'Bad API Marketplace Upload',
        ]);
    }

    public function test_create_marketplace_accepts_valid_multipart_payload_and_upload(): void
    {
        Storage::fake('public');

        $user = $this->authenticateApiUser();
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        $response = $this->withToken($this->apiToken)->post(route('api.marketplace.store'), [
            'title' => 'Valid API Marketplace Upload',
            'price' => '25.50',
            'location' => 'Vilnius',
            'category' => $category->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => $brand->id,
            'currency' => $currency->id,
            'buy_link' => 'https://example.com/product',
            'description' => 'A valid product body.',
            'multiple_files' => [
                UploadedFile::fake()->image('marketplace.jpg', 1200, 800)->size(256),
            ],
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Marketplace created successfully',
            ]);

        $product = Marketplace::query()
            ->where('title', 'Valid API Marketplace Upload')
            ->firstOrFail();

        $this->assertSame($user->id, (int) $product->user_id);
        $this->assertSame('25.50', $product->price);
        $this->assertNotEmpty($product->image);

        Storage::disk('public')->assertExists('marketplace/thumbnail/'.$product->image);
        Storage::disk('public')->assertExists('marketplace/coverphoto/'.$product->image);

        $this->assertTrue(Media_files::query()
            ->where('product_id', $product->id)
            ->where('file_name', $product->image)
            ->where('file_type', 'image')
            ->exists());
    }

    public function test_update_marketplace_rejects_invalid_route_id_with_legacy_error_shape(): void
    {
        $this->authenticateApiUser();
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        $response = $this->withToken($this->apiToken)->postJson(route('api.marketplace.update', [
            'id' => 'not-a-product-id',
        ]), [
            'title' => 'Valid Title',
            'price' => '25.50',
            'location' => 'Vilnius',
            'category' => $category->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => $brand->id,
            'currency' => $currency->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'id',
                ],
            ]);
    }

    public function test_update_marketplace_rejects_invalid_json_body_without_mutating_product(): void
    {
        $user = $this->authenticateApiUser();
        [$category, $brand, $currency] = $this->createMarketplaceLookups();
        $product = $this->marketplace([
            'user_id' => $user->id,
            'title' => 'Original Product Title',
            'price' => '15.00',
            'location' => 'Kaunas',
            'category' => (string) $category->id,
            'condition' => 'used',
            'status' => '1',
            'brand' => (string) $brand->id,
            'currency_id' => $currency->id,
        ]);

        $response = $this->withToken($this->apiToken)->postJson(route('api.marketplace.update', [
            'id' => $product->id,
        ]), [
            'title' => 'Mutated Product Title',
            'price' => 'free',
            'location' => 'Vilnius',
            'category' => 999999,
            'condition' => 'ancient',
            'status' => 7,
            'brand' => 999999,
            'currency' => 999999,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'price',
                    'category',
                    'condition',
                    'status',
                    'brand',
                    'currency',
                ],
            ]);

        $product->refresh();

        $this->assertSame('Original Product Title', $product->title);
        $this->assertSame('15.00', $product->price);
    }

    public function test_marketplace_filter_validation_messages_are_standardized(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken($this->apiToken)->getJson($this->apiMarketplaceFilterUrl([
            'filters' => [
                'condition' => 'broken',
                'price' => [
                    'min' => 'free',
                ],
            ],
            'sort' => 'password',
            'direction' => 'sideways',
            'per_page' => 101,
        ]));

        $response->assertOk();

        $errors = $response->json('validationError');

        $this->assertSame('The filter condition must be one of the following values: new, used.', $errors['filters.condition'][0]);
        $this->assertSame('The minimum price must be a number.', $errors['filters.price.min'][0]);
        $this->assertSame('The sort field must be one of the following values: id, created_at, price, title.', $errors['sort'][0]);
        $this->assertSame('The sort direction must be one of the following values: asc, desc.', $errors['direction'][0]);
        $this->assertSame('The items per page may not be greater than 100.', $errors['per_page'][0]);
    }

    private function authenticateApiUser(): User
    {
        $user = User::factory()->create();

        $this->apiToken = $user->createToken('api-test')->plainTextToken;

        return $user;
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

    /**
     * @param  array<string, mixed>  $query
     */
    private function apiMarketplaceFilterUrl(array $query): string
    {
        return route('api.marketplace.filter').'?'.http_build_query($query);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function marketplace(array $attributes): Marketplace
    {
        return Marketplace::factory()->create($attributes);
    }
}
