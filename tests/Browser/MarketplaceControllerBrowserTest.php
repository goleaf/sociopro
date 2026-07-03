<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\MediaFile;
use App\Models\SavedProduct;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MarketplaceControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-market-viewer@example.test',
        'dusk-market-seller@example.test',
    ];

    private const LISTING_TITLE = 'Dusk Market One';

    private const OWN_TITLE = 'Dusk Own Item';

    private const SAVED_TITLE = 'Dusk Saved Item';

    private const DELETE_TITLE = 'Dusk Delete Item';

    private const CREATED_TITLE = 'Dusk Created';

    private const UPDATED_TITLE = 'Dusk Updated';

    private const DELETE_IMAGE = 'dusk-marketplace-delete.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFixtures();
        $this->seedRuntimeSettings();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        parent::tearDown();
    }

    public function test_marketplace_controller_routes_work_through_browser_navigation_and_fetch(): void
    {
        $viewer = $this->activeUser('dusk-market-viewer@example.test', 'Dusk Market Viewer');
        $seller = $this->activeUser('dusk-market-seller@example.test', 'Dusk Market Seller');

        $listingProduct = $this->createProduct($seller, self::LISTING_TITLE);
        $ownProduct = $this->createProduct($viewer, self::OWN_TITLE);
        $savedProduct = $this->createProduct($seller, self::SAVED_TITLE);
        $deleteProduct = $this->createProduct($viewer, self::DELETE_TITLE, [
            'image' => self::DELETE_IMAGE,
        ]);
        $this->putProductImage(self::DELETE_IMAGE);

        $this->browse(function (Browser $browser) use ($deleteProduct, $listingProduct, $ownProduct, $savedProduct, $viewer) {
            $browser->loginAs($viewer)
                ->visit('/products')
                ->assertSee('Marketplace')
                ->assertSee(self::LISTING_TITLE)
                ->visit('/user/product')
                ->assertSee(self::OWN_TITLE)
                ->assertDontSee(self::LISTING_TITLE);

            $this->assertFetchResponseContains($browser, '/load_product_by_scrolling?offset=0', 'marketLoadResponse', self::LISTING_TITLE);
            $this->assertFetchResponseContains(
                $browser,
                '/product/filter?'.http_build_query([
                    'search' => 'Dusk Market',
                    'condition' => 'used',
                    'min' => '1',
                    'max' => '100',
                    'location' => 'Vilnius',
                ]),
                'marketFilterResponse',
                self::LISTING_TITLE
            );

            $browser->visit('/product/view/'.$listingProduct->id)
                ->assertSee(self::LISTING_TITLE)
                ->assertSee('Dusk marketplace description.');

            $this->assertFetchResponseContains(
                $browser,
                '/product/iframe/view/'.$listingProduct->id.'?shared=1',
                'marketIframeResponse',
                self::LISTING_TITLE
            );

            $browser->visit('/product/iframe/view/'.$listingProduct->id)
                ->assertPathIs('/product/view/'.$listingProduct->id);

            $this->postForm($browser, '/product/store', $this->marketplacePayload([
                'title' => self::CREATED_TITLE,
            ]), 'marketStoreResponse', '"reload":1');

            $createdProduct = Marketplace::query()
                ->where('user_id', $viewer->id)
                ->where('title', self::CREATED_TITLE)
                ->firstOrFail();

            $this->postForm($browser, '/update/product/'.$createdProduct->id, $this->marketplacePayload([
                'title' => self::UPDATED_TITLE,
                'price' => '88.00',
            ]), 'marketUpdateResponse', '"reload":1');

            $this->assertSame(self::UPDATED_TITLE, $createdProduct->refresh()->title);
            $this->assertSame('88.00', $createdProduct->price);

            $this->assertFetchResponseContains($browser, '/save/product/'.$savedProduct->id, 'marketSaveResponse', '"reload":1');
            $this->assertDatabaseHas('saved_products', [
                'user_id' => $viewer->id,
                'product_id' => $savedProduct->id,
            ]);

            $browser->visit('/product/saved')
                ->assertSee(self::SAVED_TITLE)
                ->assertDontSee(self::OWN_TITLE);

            $this->assertFetchResponseContains($browser, '/unsave/product/'.$savedProduct->id, 'marketUnsaveResponse', '"reload":1');
            $this->assertDatabaseMissing('saved_products', [
                'user_id' => $viewer->id,
                'product_id' => $savedProduct->id,
            ]);

            $this->assertFetchResponseContains(
                $browser,
                '/product/delete?product_id='.$deleteProduct->id,
                'marketDeleteResponse',
                'Product Deleted Successfully'
            );

            $this->assertDatabaseHas('marketplaces', [
                'id' => $ownProduct->id,
                'user_id' => $viewer->id,
            ]);
        });

        $this->assertDatabaseMissing('marketplaces', ['id' => $deleteProduct->id]);
        $this->assertFileDoesNotExist(public_path('storage/marketplace/coverphoto/'.self::DELETE_IMAGE));
        $this->assertFileDoesNotExist(public_path('storage/marketplace/thumbnail/'.self::DELETE_IMAGE));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeUser(string $email, string $name, array $overrides = []): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill($overrides + [
            'name' => $name,
            'email' => $email,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => str_replace(['@', '.'], '-', $email),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'payment_settings' => '',
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'profile_status' => 'unlock',
        ]);
        $user->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProduct(User $owner, string $title, array $overrides = []): Marketplace
    {
        [$category, $brand, $currency] = $this->lookups();

        $product = new Marketplace;
        $product->forceFill($overrides + [
            'user_id' => $owner->id,
            'title' => $title,
            'currency_id' => $currency->id,
            'price' => '25.00',
            'location' => 'Vilnius',
            'category' => (string) $category->id,
            'status' => '1',
            'condition' => 'used',
            'brand' => (string) $brand->id,
            'buy_link' => 'https://example.com/dusk-marketplace',
            'description' => 'Dusk marketplace description.',
            'image' => '',
        ]);
        $product->save();

        return $product;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function marketplacePayload(array $overrides = []): array
    {
        [$category, $brand, $currency] = $this->lookups();

        return [
            'title' => 'Dusk Payload',
            'price' => '35.00',
            'location' => 'Vilnius',
            'category' => $category->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => $brand->id,
            'currency' => $currency->id,
            'buy_link' => 'https://example.com/dusk-payload',
            'description' => 'Dusk marketplace payload description.',
            ...$overrides,
        ];
    }

    private function postForm(Browser $browser, string $url, array $payload, string $windowKey, string $expectedText): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;
            const payload = {$encodedPayload};
            const params = new URLSearchParams();

            Object.entries(payload).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((item) => params.append(key + '[]', item));
                    return;
                }

                params.append(key, value ?? '');
            });

            const token = document.querySelector('meta[name="csrf_token"], meta[name="csrf-token"]')?.content;

            fetch({$encodedUrl}, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': token,
                },
                body: params,
            }).then(async (response) => {
                window[{$encodedWindowKey}] = {
                    status: response.status,
                    text: await response.text(),
                };
            }).catch((error) => {
                window[{$encodedWindowKey}] = {
                    status: -1,
                    text: String(error),
                };
            });
        JS);

        $browser->waitUntil("window[{$encodedWindowKey}] !== null && window[{$encodedWindowKey}].status === 200", 5)
            ->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
    }

    private function assertFetchResponseContains(Browser $browser, string $url, string $windowKey, string $expectedText): void
    {
        $this->assertFetchOk($browser, $url, $windowKey);

        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
    }

    private function assertFetchOk(Browser $browser, string $url, string $windowKey): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;

            fetch({$encodedUrl}, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                },
            }).then(async (response) => {
                window[{$encodedWindowKey}] = {
                    status: response.status,
                    text: await response.text(),
                };
            }).catch((error) => {
                window[{$encodedWindowKey}] = {
                    status: -1,
                    text: String(error),
                };
            });
        JS);

        $browser->waitUntil("window[{$encodedWindowKey}] !== null && window[{$encodedWindowKey}].status === 200", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('SQLSTATE[')", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('no such table')", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('Internal Server Error')", 5);
    }

    private function seedRuntimeSettings(): void
    {
        $this->upsertSetting('system_language', 'english');
        $this->upsertSetting('system_name', 'Dusk Sociopro');
        $this->upsertSetting('system_fav_icon', '');
        $this->upsertSetting('theme_color', 'default');
    }

    private function upsertSetting(string $type, string $description): void
    {
        $setting = Setting::query()->where('type', $type)->first() ?? new Setting;
        $setting->forceFill([
            'type' => $type,
            'description' => $description,
            'updated_at' => now(),
        ]);

        if (! $setting->exists) {
            $setting->created_at = now();
        }

        $setting->save();
    }

    /**
     * @return array{Category, Brand, Currency}
     */
    private function lookups(): array
    {
        $suffix = (string) str()->uuid();

        $category = new Category;
        $category->forceFill(['name' => 'Dusk Market Category '.$suffix]);
        $category->save();

        $brand = new Brand;
        $brand->forceFill(['name' => 'Dusk Market Brand '.$suffix]);
        $brand->save();

        $currency = new Currency;
        $currency->timestamps = false;
        $currency->forceFill([
            'name' => 'Dusk Market Euro',
            'code' => 'DM'.substr(str_replace('-', '', $suffix), 0, 8),
            'symbol' => 'DM',
            'paypal_supported' => true,
            'stripe_supported' => true,
        ]);
        $currency->save();

        return [$category, $brand, $currency];
    }

    private function putProductImage(string $fileName): void
    {
        File::ensureDirectoryExists(public_path('storage/marketplace/coverphoto'));
        File::ensureDirectoryExists(public_path('storage/marketplace/thumbnail'));
        File::put(public_path('storage/marketplace/coverphoto/'.$fileName), 'dusk marketplace cover');
        File::put(public_path('storage/marketplace/thumbnail/'.$fileName), 'dusk marketplace thumb');
    }

    private function deleteFixtures(): void
    {
        File::delete(public_path('storage/marketplace/coverphoto/'.self::DELETE_IMAGE));
        File::delete(public_path('storage/marketplace/thumbnail/'.self::DELETE_IMAGE));

        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        $productIds = Marketplace::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('title', 'like', 'Dusk %')
            ->pluck('id');

        SavedProduct::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('product_id', $productIds)
            ->delete();
        MediaFile::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('product_id', $productIds)
            ->delete();
        Marketplace::query()
            ->whereIn('id', $productIds)
            ->delete();

        User::query()
            ->whereIn('id', $userIds)
            ->delete();

        Category::query()
            ->where('name', 'like', 'Dusk Market Category %')
            ->delete();
        Brand::query()
            ->where('name', 'like', 'Dusk Market Brand %')
            ->delete();
        Currency::query()
            ->where('code', 'like', 'DM%')
            ->delete();
    }
}
