<?php

namespace Tests\Feature;

use App\Enums\ApiTokenAbility;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Requests\Admin\ImportAddonPackageRequest;
use App\Http\Requests\Api\ApiFormRequest;
use App\Http\Requests\Api\Marketplace\Concerns\ValidatesMarketplacePayload;
use App\Http\Requests\Api\Marketplace\DestroyMarketplaceRequest as ApiDestroyMarketplaceRequest;
use App\Http\Requests\Api\Marketplace\FilterMarketplaceRequest;
use App\Http\Requests\Api\Marketplace\StoreMarketplaceRequest as ApiStoreMarketplaceRequest;
use App\Http\Requests\Api\Marketplace\UpdateMarketplaceRequest as ApiUpdateMarketplaceRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Blog\BlogRequest;
use App\Http\Requests\Blog\StoreBlogRequest;
use App\Http\Requests\Blog\UpdateBlogRequest;
use App\Http\Requests\ContactSendRequest;
use App\Http\Requests\Install\FinalizeInstallationRequest;
use App\Http\Requests\Install\PrepareDatabaseConnectionRequest;
use App\Http\Requests\Install\ValidatePurchaseCodeRequest;
use App\Http\Requests\Marketplace\DestroyMarketplaceRequest;
use App\Http\Requests\Marketplace\MarketplaceRequest;
use App\Http\Requests\Marketplace\StoreMarketplaceRequest;
use App\Http\Requests\Marketplace\UpdateMarketplaceRequest;
use App\Http\Requests\Page\PageWriteRequest;
use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdatePageCoverPhotoRequest;
use App\Http\Requests\Page\UpdatePageInfoRequest;
use App\Http\Requests\Page\UpdatePageRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class HttpRequestContractsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RouteFacade::middleware('api')->post('/__tests/api-form-request', function (ExposedApiFormRequest $request) {
            return response()->json([
                'authorized' => $request->authorize(),
                'skip_legacy' => $request->exposesSkipValidationForLegacyGuestFlow(),
                'bearer_user_id' => $request->exposesBearerTokenUserId(),
                'can_create' => $request->exposesBearerTokenCan(ApiTokenAbility::MarketplaceCreate),
                'can_update' => $request->exposesBearerTokenCan(ApiTokenAbility::MarketplaceUpdate),
            ]);
        });

        RouteFacade::middleware(['api', 'api.token'])->post('/__tests/api-marketplace-store', static fn (ApiStoreMarketplaceRequest $request) => response()->json(['ok' => true]));
        RouteFacade::middleware(['api', 'api.token'])->post('/__tests/api-marketplace-update/{id}', static fn (ApiUpdateMarketplaceRequest $request) => response()->json([
            'id' => $request->validationData()['id'],
        ]));
        RouteFacade::middleware(['api', 'api.token'])->post('/__tests/api-marketplace-destroy/{product_id}', static fn (ApiDestroyMarketplaceRequest $request) => response()->json(['ok' => true]));
        RouteFacade::post('/__tests/marketplace-request', static fn (ExposedMarketplaceRequest $request) => response()->json(['ok' => true]));
        RouteFacade::post('/__tests/page-write-request', static fn (ExposedPageWriteRequest $request) => response()->json(['ok' => true]));
    }

    public function test_admin_import_addon_package_request_authorizes_and_requires_zip_file(): void
    {
        $request = $this->formRequest(ImportAddonPackageRequest::class);

        $this->assertTrue($request->authorize());
        $this->assertSame([
            'file' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ], $request->rules());
    }

    public function test_api_form_request_helpers_resolve_legacy_skip_bearer_user_and_abilities(): void
    {
        $this->postJson('/__tests/api-form-request', ['name' => 'Legacy client'])
            ->assertOk()
            ->assertJson([
                'authorized' => true,
                'skip_legacy' => true,
                'bearer_user_id' => null,
                'can_create' => false,
            ]);

        $user = $this->activeUser();
        $token = $user->createToken('request-contract', [ApiTokenAbility::MarketplaceCreate->value])->plainTextToken;

        $this
            ->withToken($token)
            ->postJson('/__tests/api-form-request', ['name' => 'Token client'])
            ->assertOk()
            ->assertJson([
                'authorized' => true,
                'skip_legacy' => false,
                'bearer_user_id' => $user->id,
                'can_create' => true,
                'can_update' => false,
            ]);
    }

    public function test_api_form_request_failed_validation_returns_legacy_api_error_payload(): void
    {
        $this->postJson('/__tests/api-form-request', [])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['validationError' => ['name']]);
    }

    public function test_api_marketplace_payload_trait_exposes_messages_attributes_and_rules(): void
    {
        $request = new ExposedMarketplacePayloadRules;

        $this->assertArrayHasKey('condition.in', $request->messages());
        $this->assertArrayHasKey('status.in', $request->messages());
        $this->assertArrayHasKey('title', $request->exposesMarketplacePayloadRules());
        $this->assertArrayHasKey('multiple_files.*', $request->exposesMarketplacePayloadRules());
        $this->assertContains('exists:categories,id', $request->exposesMarketplacePayloadRules()['category']);
        $this->assertContains('exists:brands,id', $request->exposesMarketplacePayloadRules()['brand']);
        $this->assertIsArray($request->attributes());
    }

    public function test_api_marketplace_write_requests_keep_legacy_guest_rules_and_token_validation_contracts(): void
    {
        $storeGuest = $this->formRequest(ApiStoreMarketplaceRequest::class);
        $updateGuest = $this->formRequest(ApiUpdateMarketplaceRequest::class, routeParameters: ['id' => 123]);
        $destroyGuest = $this->formRequest(ApiDestroyMarketplaceRequest::class, routeParameters: ['product_id' => 123]);

        $this->assertTrue($storeGuest->authorize());
        $this->assertSame([], $storeGuest->rules());
        $this->assertTrue($updateGuest->authorize());
        $this->assertSame([], $updateGuest->rules());
        $this->assertSame(['id' => 123], array_intersect_key($updateGuest->validationData(), ['id' => true]));
        $this->assertTrue($destroyGuest->authorize());
        $this->assertSame([], $destroyGuest->rules());

        $storeToken = $this->formRequest(ApiStoreMarketplaceRequest::class, server: [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);
        $updateToken = $this->formRequest(ApiUpdateMarketplaceRequest::class, server: [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ], routeParameters: ['id' => 456]);

        $this->assertArrayHasKey('title', $storeToken->rules());
        $this->assertArrayHasKey('multiple_files.*', $storeToken->rules());
        $this->assertArrayHasKey('id', $updateToken->rules());
        $this->assertSame(456, $updateToken->validationData()['id']);
    }

    public function test_api_marketplace_authorization_uses_bearer_abilities_and_policies_when_token_user_exists(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($owner);

        $this
            ->withToken($this->plainTokenFor($owner, [ApiTokenAbility::MarketplaceUpdate]))
            ->postJson('/__tests/api-marketplace-update/'.$product->id, $this->marketplacePayload())
            ->assertOk()
            ->assertJsonPath('id', (string) $product->id);
        Auth::forgetGuards();

        $this
            ->withToken($this->plainTokenFor($otherUser, [ApiTokenAbility::MarketplaceUpdate]))
            ->postJson('/__tests/api-marketplace-update/'.$product->id, $this->marketplacePayload())
            ->assertForbidden();
        Auth::forgetGuards();

        $this
            ->withToken($this->plainTokenFor($owner, [ApiTokenAbility::MarketplaceDelete]))
            ->postJson('/__tests/api-marketplace-store', $this->marketplacePayload())
            ->assertForbidden();
        Auth::forgetGuards();

        $this
            ->withToken($this->plainTokenFor($owner, [ApiTokenAbility::MarketplaceDelete]))
            ->postJson('/__tests/api-marketplace-destroy/'.$product->id)
            ->assertOk();
    }

    public function test_api_marketplace_filter_request_normalizes_filters_and_private_defaults(): void
    {
        $request = $this->formRequest(FilterMarketplaceRequest::class, [
            'direction' => 'ASC',
            'filters' => [
                'search' => 'nested title',
                'condition' => 'used',
                'price' => ['min' => '5.00', 'max' => '10.00'],
                'created_between' => ['from' => '2026-07-01', 'to' => '2026-07-03'],
            ],
            'sort' => 'price',
            'page' => '0',
            'per_page' => '500',
            'include_pagination' => '1',
        ], 'GET', server: [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);

        $this->invoke($request, 'prepareForValidation');

        $this->assertArrayHasKey('condition.in', $request->messages());
        $this->assertArrayHasKey('include_pagination.boolean', $request->messages());
        $this->assertIsArray($request->attributes());
        $this->assertArrayHasKey('filters.created_between.to', $request->rules());
        $this->assertTrue($request->includePagination());
        $this->assertSame([
            'search' => 'nested title',
            'category' => null,
            'condition' => 'used',
            'min' => '5.00',
            'max' => '10.00',
            'brand' => null,
            'location' => null,
            'sort' => 'price',
            'direction' => 'asc',
            'page' => FilterMarketplaceRequest::DEFAULT_PAGE,
            'per_page' => FilterMarketplaceRequest::MAX_PER_PAGE,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-03',
        ], $request->filters());

        $request = $this->formRequest(FilterMarketplaceRequest::class, [
            'sort' => 'unknown',
            'direction' => 'SIDEWAYS',
            'page' => '-3',
            'per_page' => '0',
        ], 'GET', server: [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);

        $this->assertSame('id', $this->invoke($request, 'sortField'));
        $this->assertSame('desc', $this->invoke($request, 'sortDirection'));
        $this->assertSame(25, $this->invoke($request, 'positiveInteger', 'missing', 25));
        $this->assertSame(25, $this->invoke($request, 'positiveInteger', 'page', 25));
        $this->assertSame(25, $this->invoke($request, 'positiveInteger', 'per_page', 25, FilterMarketplaceRequest::MAX_PER_PAGE));

        $legacyGuest = $this->formRequest(FilterMarketplaceRequest::class, method: 'GET');
        $this->assertSame([], $legacyGuest->rules());
    }

    public function test_login_request_rules_authentication_rate_limiting_and_throttle_key(): void
    {
        $user = $this->activeUser([
            'email' => 'login@example.com',
            'password' => Hash::make('correct-password'),
        ]);
        $request = $this->formRequest(LoginRequest::class, [
            'email' => 'login@example.com',
            'password' => 'correct-password',
            'remember' => '1',
        ], uri: '/login', server: ['REMOTE_ADDR' => '203.0.113.10']);

        $this->assertTrue($request->authorize());
        $this->assertSame([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], $request->rules());
        $this->assertSame('login@example.com|203.0.113.10', $request->throttleKey());

        RateLimiter::hit($request->throttleKey());

        $request->authenticate();

        $this->assertTrue(Auth::check());
        $this->assertTrue($user->is(Auth::user()));
        $this->assertSame(0, RateLimiter::attempts($request->throttleKey()));
    }

    public function test_login_request_rejects_bad_credentials_and_records_attempt(): void
    {
        $this->activeUser([
            'email' => 'bad-password@example.com',
            'password' => Hash::make('correct-password'),
        ]);
        $request = $this->formRequest(LoginRequest::class, [
            'email' => 'bad-password@example.com',
            'password' => 'wrong-password',
        ], uri: '/login', server: ['REMOTE_ADDR' => '203.0.113.11']);

        try {
            $request->authenticate();
            $this->fail('Invalid credentials should throw a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
            $this->assertSame(1, RateLimiter::attempts($request->throttleKey()));
        }
    }

    public function test_login_request_blocks_when_rate_limited(): void
    {
        Event::fake([Lockout::class]);

        $request = $this->formRequest(LoginRequest::class, [
            'email' => 'locked@example.com',
            'password' => 'password',
        ], uri: '/login', server: ['REMOTE_ADDR' => '203.0.113.12']);

        RateLimiter::clear($request->throttleKey());
        for ($attempt = 0; $attempt < 5; $attempt++) {
            RateLimiter::hit($request->throttleKey(), 60);
        }

        $this->expectException(ValidationException::class);

        try {
            $request->ensureIsNotRateLimited();
        } finally {
            Event::assertDispatched(Lockout::class);
            RateLimiter::clear($request->throttleKey());
        }
    }

    public function test_blog_requests_share_rules_prepare_tags_and_expose_image_file(): void
    {
        $store = $this->formRequest(StoreBlogRequest::class, [
            'tag' => json_encode([
                ['value' => ' first '],
                ['value' => ''],
                ['value' => 'second'],
            ], JSON_THROW_ON_ERROR),
        ]);
        $update = $this->formRequest(UpdateBlogRequest::class);

        $this->assertInstanceOf(BlogRequest::class, $store);
        $this->assertInstanceOf(BlogRequest::class, $update);
        $this->assertTrue($store->authorize());
        $this->assertSame($store->rules(), $update->rules());
        $this->assertArrayHasKey('tags.*.value', $store->rules());
        $this->assertContains('exists:blogcategories,id', $store->rules()['category']);

        $this->invoke($store, 'prepareForValidation');
        $validator = Validator::make($store->all(), [
            'tags' => ['array'],
            'tags.*.value' => ['nullable', 'string'],
        ]);
        $this->assertTrue($validator->passes());
        $store->setValidator($validator);

        $this->assertSame(['first', 'second'], $store->tagValues());

        $file = UploadedFile::fake()->image('blog.jpg');
        $imageRequest = $this->formRequest(StoreBlogRequest::class, files: ['image' => $file]);

        $this->assertSame($file, $imageRequest->imageFile());
    }

    public function test_contact_and_install_requests_expose_expected_authorization_and_rules(): void
    {
        $contact = $this->formRequest(ContactSendRequest::class);
        $purchase = $this->formRequest(ValidatePurchaseCodeRequest::class);
        $databaseGet = $this->formRequest(PrepareDatabaseConnectionRequest::class, method: 'GET');
        $databasePost = $this->formRequest(PrepareDatabaseConnectionRequest::class);
        $finalizeGet = $this->formRequest(FinalizeInstallationRequest::class, method: 'GET');
        $finalizePost = $this->formRequest(FinalizeInstallationRequest::class);

        $this->assertTrue($contact->authorize());
        $this->assertSame(['required', 'email:rfc', 'max:255'], $contact->rules()['email']);
        $this->assertTrue($purchase->authorize());
        $this->assertSame(['required', 'string', 'max:255'], $purchase->rules()['purchase_code']);
        $this->assertTrue($databasePost->authorize());
        $this->assertSame([], $databaseGet->rules());
        $this->assertSame(['required', 'string', 'in:sqlite,mysql'], $databasePost->rules()['db_connection']);
        $this->assertTrue($finalizePost->authorize());
        $this->assertSame([], $finalizeGet->rules());
        $this->assertSame(['required', 'timezone'], $finalizePost->rules()['timezone']);
    }

    public function test_web_marketplace_requests_authorize_authenticated_users_and_keep_legacy_validation_shape(): void
    {
        $user = $this->activeUser();
        $guestStore = $this->formRequest(StoreMarketplaceRequest::class);
        $store = $this->formRequest(StoreMarketplaceRequest::class, user: $user);
        $update = $this->formRequest(UpdateMarketplaceRequest::class, user: $user);
        $destroy = $this->formRequest(DestroyMarketplaceRequest::class, user: $user);

        $this->assertFalse($guestStore->authorize());
        $this->assertTrue($store->authorize());
        $this->assertTrue($update->authorize());
        $this->assertTrue($destroy->authorize());
        $this->assertSame([], $destroy->rules());
        $this->assertArrayHasKey('price', $store->rules());
        $this->assertArrayHasKey('status', $update->rules());

        $this->postJson('/__tests/marketplace-request', [])
            ->assertOk()
            ->assertJsonStructure(['validationError' => ['title']]);
    }

    public function test_page_requests_authorize_authenticated_users_share_rules_and_keep_legacy_validation_shape(): void
    {
        $user = $this->activeUser();
        $guestStore = $this->formRequest(StorePageRequest::class);
        $store = $this->formRequest(StorePageRequest::class, user: $user);
        $update = $this->formRequest(UpdatePageRequest::class, user: $user);
        $cover = $this->formRequest(UpdatePageCoverPhotoRequest::class, user: $user);
        $info = $this->formRequest(UpdatePageInfoRequest::class, user: $user);

        $this->assertFalse($guestStore->authorize());
        $this->assertTrue($store->authorize());
        $this->assertTrue($update->authorize());
        $this->assertTrue($cover->authorize());
        $this->assertTrue($info->authorize());
        $this->assertSame($store->rules(), $update->rules());
        $this->assertContains('exists:pagecategories,id', $store->rules()['category']);
        $this->assertArrayHasKey('cover_photo', $cover->rules());
        $this->assertSame(['nullable', 'string', 'max:255'], $info->rules()['job']);

        $this->postJson('/__tests/page-write-request', [])
            ->assertOk()
            ->assertJsonStructure(['validationError' => ['name']]);
    }

    /**
     * @template TRequest of FormRequest
     *
     * @param  class-string<TRequest>  $class
     * @param  array<string, mixed>  $input
     * @param  array<string, UploadedFile>  $files
     * @param  array<string, string>  $server
     * @param  array<string, mixed>  $routeParameters
     * @return TRequest
     */
    private function formRequest(
        string $class,
        array $input = [],
        string $method = 'POST',
        string $uri = '/',
        array $files = [],
        array $server = [],
        ?User $user = null,
        array $routeParameters = [],
    ): FormRequest {
        /** @var TRequest $request */
        $request = $class::create($uri, $method, $input, [], $files, $server);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);
        $request->setUserResolver(static fn () => $user);

        $route = new Route([$method], $uri, static fn () => null);
        $route->bind($request);
        foreach ($routeParameters as $key => $value) {
            $route->setParameter($key, $value);
        }
        $request->setRouteResolver(static fn () => $route);

        if ($user !== null) {
            Auth::setUser($user);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activeUser(array $attributes = []): User
    {
        return User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            ...$attributes,
        ]);
    }

    private function marketplace(User $owner): Marketplace
    {
        return Marketplace::factory()
            ->forOwner($owner)
            ->forCategory(Category::factory()->create())
            ->forBrand(Brand::factory()->create())
            ->forCurrency(Currency::factory()->create())
            ->create();
    }

    /**
     * @param  list<ApiTokenAbility>  $abilities
     */
    private function plainTokenFor(User $user, array $abilities): string
    {
        return $user->createToken('request-contract', array_map(
            static fn (ApiTokenAbility $ability): string => $ability->value,
            $abilities,
        ))->plainTextToken;
    }

    /**
     * @return array<string, mixed>
     */
    private function marketplacePayload(): array
    {
        return [
            'title' => 'Request contract product',
            'price' => '25.50',
            'location' => 'Vilnius',
            'category' => Category::factory()->create()->id,
            'condition' => 'new',
            'status' => 1,
            'brand' => Brand::factory()->create()->id,
            'currency' => Currency::factory()->create()->id,
            'buy_link' => 'https://example.com/product',
            'description' => 'Request contract payload.',
        ];
    }

    private function invoke(object $object, string $method, mixed ...$parameters): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$parameters);
    }
}

final class ExposedApiFormRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }

    public function exposesSkipValidationForLegacyGuestFlow(): bool
    {
        return $this->skipValidationForLegacyGuestFlow();
    }

    public function exposesBearerTokenUserId(): ?int
    {
        return $this->bearerTokenUser()?->id;
    }

    public function exposesBearerTokenCan(ApiTokenAbility $ability): bool
    {
        return $this->bearerTokenCan($ability);
    }
}

final class ExposedMarketplacePayloadRules
{
    use ValidatesMarketplacePayload;

    public function exposesMarketplacePayloadRules(): array
    {
        return $this->marketplacePayloadRules();
    }
}

final class ExposedMarketplaceRequest extends MarketplaceRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required'],
        ];
    }
}

final class ExposedPageWriteRequest extends PageWriteRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required'],
        ];
    }
}
