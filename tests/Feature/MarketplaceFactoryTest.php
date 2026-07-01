<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\SavedProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_factory_creates_valid_product_with_related_defaults(): void
    {
        $product = Marketplace::factory()->create();

        $this->assertInstanceOf(User::class, $product->getUser()->first());
        $this->assertInstanceOf(Category::class, $product->getCategory()->first());
        $this->assertInstanceOf(Brand::class, $product->getBrand()->first());
        $this->assertInstanceOf(Currency::class, $product->getCurrency()->first());
        $this->assertNotSame('', (string) $product->title);
        $this->assertNotSame('', (string) $product->description);
        $this->assertContains($product->condition, ['new', 'used']);
        $this->assertSame('1', (string) $product->status);
    }

    public function test_marketplace_factory_supports_explicit_relationships_and_states(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Audio Gear']);
        $brand = Brand::factory()->create(['name' => 'Northwind']);
        $currency = Currency::factory()->euro()->create();

        $product = Marketplace::factory()
            ->forOwner($owner)
            ->forCategory($category)
            ->forBrand($brand)
            ->forCurrency($currency)
            ->used()
            ->inactive()
            ->create([
                'title' => 'Studio headphones',
            ]);

        $this->assertSame($owner->id, $product->user_id);
        $this->assertSame((string) $category->id, (string) $product->category);
        $this->assertSame((string) $brand->id, (string) $product->brand);
        $this->assertSame($currency->id, $product->currency_id);
        $this->assertSame('used', $product->condition);
        $this->assertSame('0', (string) $product->status);
        $this->assertSame('Studio headphones', $product->title);
    }

    public function test_saved_product_factory_links_user_and_marketplace_product(): void
    {
        $user = User::factory()->create();
        $product = Marketplace::factory()->create();

        $savedProduct = SavedProduct::factory()
            ->forUser($user)
            ->forProduct($product)
            ->create();

        $this->assertSame($user->id, $savedProduct->user_id);
        $this->assertSame($product->id, $savedProduct->product_id);
        $this->assertTrue($savedProduct->productData()->first()->is($product));
    }
}
