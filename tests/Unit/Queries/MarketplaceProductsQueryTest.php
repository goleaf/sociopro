<?php

namespace Tests\Unit\Queries;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\User;
use App\Queries\Marketplace\MarketplaceProductsQuery;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceProductsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_treats_like_wildcards_as_literal_search_text(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->electronics()->create();
        $brand = Brand::factory()->acme()->create();
        $currency = Currency::factory()->euro()->create();

        $literalPercentMatch = $this->marketplace($owner, $category, $brand, $currency, [
            'title' => 'Camera 100% original',
            'description' => 'Literal percent marketplace fixture.',
            'location' => 'Vilnius',
            'price' => '100.00',
        ]);
        $literalUnderscoreMatch = $this->marketplace($owner, $category, $brand, $currency, [
            'title' => 'Camera_PRO kit',
            'description' => 'Literal underscore marketplace fixture.',
            'location' => 'Vilnius',
            'price' => '110.00',
        ]);
        $this->marketplace($owner, $category, $brand, $currency, [
            'title' => 'Camera wildcard bait',
            'description' => 'This listing should not match wildcard-only searches.',
            'location' => 'Vilnius',
            'price' => '120.00',
        ]);

        $percentProducts = app(MarketplaceProductsQuery::class)->handle($this->filters([
            'search' => '%',
        ]));
        $underscoreProducts = app(MarketplaceProductsQuery::class)->handle($this->filters([
            'search' => '_',
        ]));

        $this->assertSame([$literalPercentMatch->id], $percentProducts->pluck('id')->all());
        $this->assertSame([$literalUnderscoreMatch->id], $underscoreProducts->pluck('id')->all());
    }

    public function test_handle_uses_id_as_deterministic_tiebreaker_for_non_id_sort_fields(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->electronics()->create();
        $brand = Brand::factory()->acme()->create();
        $currency = Currency::factory()->euro()->create();

        $firstProduct = $this->marketplace($owner, $category, $brand, $currency, [
            'title' => 'Same price first',
            'price' => '100.00',
        ]);
        $secondProduct = $this->marketplace($owner, $category, $brand, $currency, [
            'title' => 'Same price second',
            'price' => '100.00',
        ]);

        $marketplaceSql = null;

        DB::listen(function (QueryExecuted $query) use (&$marketplaceSql): void {
            if (str_contains($query->sql, 'from "marketplaces"')) {
                $marketplaceSql = $query->sql;
            }
        });

        $products = app(MarketplaceProductsQuery::class)->handle($this->filters([
            'sort' => 'price',
            'direction' => 'asc',
        ]));

        $this->assertSame([$firstProduct->id, $secondProduct->id], $products->pluck('id')->all());
        $this->assertIsString($marketplaceSql);
        $this->assertStringContainsString('order by "price" asc, "id" asc', $marketplaceSql);
    }

    public function test_handle_applies_validated_filter_combination_and_sorting(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->electronics()->create();
        $otherCategory = Category::factory()->create();
        $brand = Brand::factory()->acme()->create();
        $otherBrand = Brand::factory()->create();
        $currency = Currency::factory()->euro()->create();

        $locationMatch = $this->marketplace($owner, $category, $brand, $currency, [
            'title' => 'Plain listing',
            'description' => 'Does not contain the requested search term.',
            'location' => 'Vilnius',
            'price' => '100.00',
            'condition' => 'used',
            'created_at' => '2026-01-03 10:00:00',
            'updated_at' => '2026-01-03 10:00:00',
        ]);
        $searchMatch = $this->marketplace($owner, $category, $brand, $currency, [
            'title' => 'Camera kit',
            'description' => 'A matching product by title.',
            'location' => 'Kaunas',
            'price' => '150.00',
            'condition' => 'used',
            'created_at' => '2026-01-04 10:00:00',
            'updated_at' => '2026-01-04 10:00:00',
        ]);
        $this->marketplace($owner, $category, $brand, $currency, [
            'title' => 'Camera outside price range',
            'price' => '900.00',
            'condition' => 'used',
            'created_at' => '2026-01-05 10:00:00',
            'updated_at' => '2026-01-05 10:00:00',
        ]);
        $this->marketplace($owner, $otherCategory, $brand, $currency, [
            'title' => 'Camera wrong category',
            'price' => '125.00',
            'condition' => 'used',
            'created_at' => '2026-01-06 10:00:00',
            'updated_at' => '2026-01-06 10:00:00',
        ]);
        $this->marketplace($owner, $category, $otherBrand, $currency, [
            'title' => 'Camera wrong brand',
            'price' => '125.00',
            'condition' => 'used',
            'created_at' => '2026-01-07 10:00:00',
            'updated_at' => '2026-01-07 10:00:00',
        ]);
        $this->marketplace($owner, $category, $brand, $currency, [
            'title' => 'Camera inactive',
            'price' => '125.00',
            'condition' => 'used',
            'status' => '0',
            'created_at' => '2026-01-08 10:00:00',
            'updated_at' => '2026-01-08 10:00:00',
        ]);

        $products = app(MarketplaceProductsQuery::class)->handle([
            'search' => 'Camera',
            'category' => $category->id,
            'condition' => 'used',
            'min' => '050.00',
            'max' => '200',
            'brand' => $brand->id,
            'location' => 'Vilnius',
            'sort' => 'price',
            'direction' => 'asc',
            'page' => 1,
            'per_page' => 20,
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]);

        $this->assertSame(
            [$locationMatch->id, $searchMatch->id],
            $products->pluck('id')->all()
        );
        $this->assertTrue($products->every(
            fn (Marketplace $product): bool => $product->relationLoaded('getUser')
                && $product->relationLoaded('getCategory')
                && $product->relationLoaded('getBrand')
                && $product->relationLoaded('getCurrency')
        ));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function marketplace(
        User $owner,
        Category $category,
        Brand $brand,
        Currency $currency,
        array $attributes = []
    ): Marketplace {
        return Marketplace::factory()
            ->forOwner($owner)
            ->forCategory($category)
            ->forBrand($brand)
            ->forCurrency($currency)
            ->active()
            ->create(array_merge([
                'title' => 'Marketplace product',
                'description' => 'Marketplace query fixture.',
                'location' => 'Vilnius',
                'price' => '100.00',
                'condition' => 'new',
            ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{
     *     search: mixed,
     *     category: mixed,
     *     condition: mixed,
     *     min: mixed,
     *     max: mixed,
     *     brand: mixed,
     *     location: mixed,
     *     sort: string,
     *     direction: string,
     *     page: int,
     *     per_page: int,
     *     date_from: mixed,
     *     date_to: mixed
     * }
     */
    private function filters(array $overrides = []): array
    {
        return array_merge([
            'search' => null,
            'category' => null,
            'condition' => null,
            'min' => null,
            'max' => null,
            'brand' => null,
            'location' => null,
            'sort' => 'id',
            'direction' => 'asc',
            'page' => 1,
            'per_page' => 20,
            'date_from' => null,
            'date_to' => null,
        ], $overrides);
    }
}
