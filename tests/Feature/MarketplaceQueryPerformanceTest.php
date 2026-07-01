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
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketplaceQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const API_MARKETPLACE_LOOKUP_QUERY_BUDGET = 7;

    public function test_web_marketplace_list_eager_loads_currency_for_rendered_products(): void
    {
        $viewer = $this->activeUser();
        $this->createMarketplaceProducts($viewer, 10);

        $currencyQueries = $this->countQueriesForTables(['currencies'], function () use ($viewer): void {
            $this->actingAs($viewer)
                ->get(route('allproducts'))
                ->assertOk();
        });

        $this->assertLessThanOrEqual(1, $currencyQueries);
    }

    public function test_web_saved_products_does_not_recheck_saved_state_inside_loop(): void
    {
        $viewer = $this->activeUser();
        $products = $this->createMarketplaceProducts($viewer, 5);
        $this->createSavedProducts($viewer, $products);

        $savedProductQueries = $this->countQueriesForTables(['saved_products'], function () use ($viewer): void {
            $this->actingAs($viewer)
                ->get(route('product.saved'))
                ->assertOk();
        });

        $this->assertLessThanOrEqual(1, $savedProductQueries);
    }

    public function test_api_marketplace_index_batches_serializer_lookup_queries(): void
    {
        $viewer = $this->activeUser();
        $products = $this->createMarketplaceProducts($viewer, 6);
        $this->createSavedProducts($viewer, $products);
        $this->createMessageThread($viewer);

        $lookupQueries = $this->countQueriesForTables([
            'users',
            'categories',
            'brands',
            'currencies',
            'saved_products',
            'message_thrades',
        ], function () use ($viewer): void {
            $this->withToken($viewer->createToken('query-budget')->plainTextToken)
                ->getJson(route('api.marketplace.index'))
                ->assertOk();
        });

        $this->assertLessThanOrEqual(self::API_MARKETPLACE_LOOKUP_QUERY_BUDGET, $lookupQueries);
    }

    public function test_api_marketplace_filter_batches_serializer_lookup_queries(): void
    {
        $viewer = $this->activeUser();
        $products = $this->createMarketplaceProducts($viewer, 6);
        $this->createSavedProducts($viewer, $products);
        $this->createMessageThread($viewer);

        $lookupQueries = $this->countQueriesForTables([
            'users',
            'categories',
            'brands',
            'currencies',
            'saved_products',
            'message_thrades',
        ], function () use ($viewer): void {
            $this->withToken($viewer->createToken('query-budget')->plainTextToken)
                ->getJson(route('api.marketplace.filter'))
                ->assertOk();
        });

        $this->assertLessThanOrEqual(self::API_MARKETPLACE_LOOKUP_QUERY_BUDGET, $lookupQueries);
    }

    public function test_marketplace_search_and_sort_indexes_exist(): void
    {
        $migration = require database_path('migrations/2026_07_02_120000_add_marketplace_search_filter_indexes.php');
        $migration->up();

        $indexes = collect(Schema::getIndexes('marketplaces'))
            ->mapWithKeys(fn (array $index): array => [$index['name'] => $index['columns']]);

        $this->assertSame(['status', 'id'], $indexes->get('marketplaces_status_id_idx'));
        $this->assertSame(['status', 'created_at', 'id'], $indexes->get('marketplaces_status_created_id_idx'));
        $this->assertSame(['status', 'price', 'id'], $indexes->get('marketplaces_status_price_id_idx'));
        $this->assertSame(['status', 'title', 'id'], $indexes->get('marketplaces_status_title_id_idx'));
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
        ]);
    }

    /**
     * @return list<Marketplace>
     */
    private function createMarketplaceProducts(User $owner, int $count): array
    {
        $category = Category::factory()->electronics()->create();
        $brand = Brand::factory()->acme()->create();
        $currency = Currency::factory()->euro()->create();
        $products = [];

        for ($index = 1; $index <= $count; $index++) {
            $products[] = Marketplace::factory()
                ->forOwner($owner)
                ->forCategory($category)
                ->forBrand($brand)
                ->forCurrency($currency)
                ->active()
                ->create([
                    'title' => sprintf('Query Budget Product %02d', $index),
                    'price' => '15.00',
                    'location' => 'Vilnius',
                    'description' => 'Marketplace query budget fixture.',
                ]);
        }

        return $products;
    }

    /**
     * @param  list<Marketplace>  $products
     */
    private function createSavedProducts(User $viewer, array $products): void
    {
        foreach (array_slice($products, 0, 3) as $product) {
            SavedProduct::factory()
                ->forUser($viewer)
                ->forProduct($product)
                ->create();
        }
    }

    private function createMessageThread(User $viewer): void
    {
        DB::table('message_thrades')->insert([
            'reciver_id' => $viewer->id,
            'sender_id' => User::factory()->create()->id,
            'chatcenter' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $tables
     */
    private function countQueriesForTables(array $tables, callable $callback): int
    {
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries, $tables): void {
            $sql = strtolower($query->sql);

            foreach ($tables as $table) {
                if (str_contains($sql, 'from "'.$table.'"')) {
                    $queries[] = $sql;

                    return;
                }
            }
        });

        $callback();

        return count($queries);
    }
}
