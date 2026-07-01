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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiMarketplaceValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_filter_rejects_invalid_flat_query_filters_with_legacy_error_shape(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken('test-token')->getJson($this->apiMarketplaceFilterUrl([
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

    public function test_marketplace_filter_rejects_invalid_nested_query_filters_with_legacy_error_shape(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken('test-token')->getJson($this->apiMarketplaceFilterUrl([
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

    public function test_create_marketplace_rejects_invalid_json_body_without_creating_product(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken('test-token')->postJson(route('api.marketplace.store'), [
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

        $response = $this->withToken('test-token')->postJson(route('api.marketplace.store'), [
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

    public function test_create_marketplace_rejects_invalid_upload_array_before_creating_product(): void
    {
        $this->authenticateApiUser();
        [$category, $brand, $currency] = $this->createMarketplaceLookups();

        $response = $this->withToken('test-token')->post(route('api.marketplace.store'), [
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

        $response = $this->withToken('test-token')->post(route('api.marketplace.store'), [
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

        $response = $this->withToken('test-token')->postJson(route('api.marketplace.update', [
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

        $response = $this->withToken('test-token')->postJson(route('api.marketplace.update', [
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

        $response = $this->withToken('test-token')->getJson($this->apiMarketplaceFilterUrl([
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

        Sanctum::actingAs($user);

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
        $marketplace = new Marketplace;
        $marketplace->forceFill($attributes);
        $marketplace->save();

        return $marketplace;
    }
}
