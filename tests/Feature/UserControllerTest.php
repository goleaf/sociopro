<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\UserController;
use App\Models\Setting;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_METHODS = [
        'dashboard',
        'ads',
        'ad_create',
        'ad_store',
        'ad_edit',
        'ad_update',
        'ad_delete',
        'ad_activation',
        'ad_charge_by_daterange',
        'payment_configuration',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string, 3: list<string>}>
     */
    private const ROUTES = [
        'user.dashboard' => ['dashboard', ['GET', 'HEAD'], 'user/dashboard', ['auth', 'user', 'verified', 'activity', 'prevent-back-history']],
        'user.ads' => ['ads', ['GET', 'HEAD'], 'user/ads', ['auth', 'user', 'verified', 'activity', 'prevent-back-history']],
        'user.ad.create' => ['ad_create', ['GET', 'HEAD'], 'user/ad/create', ['auth', 'user', 'verified', 'activity', 'prevent-back-history']],
        'user.ad.store' => ['ad_store', ['POST'], 'user/ad/store', ['auth', 'user', 'verified', 'activity', 'prevent-back-history']],
        'user.ad.edit' => ['ad_edit', ['GET', 'HEAD'], 'user/ad/edit/{id}', ['auth', 'user', 'verified', 'activity', 'prevent-back-history']],
        'user.ad.update' => ['ad_update', ['POST'], 'user/ad/update/{id}', ['auth', 'user', 'verified', 'activity', 'prevent-back-history']],
        'user.ad.delete' => ['ad_delete', ['GET', 'HEAD'], 'user/ad/delete/{id}', ['auth', 'user', 'verified', 'activity', 'prevent-back-history']],
        'user.ad.ad_charge_by_daterange' => ['ad_charge_by_daterange', ['GET', 'HEAD'], 'user/ad/ad_charge_by_daterange', ['auth', 'user', 'verified', 'activity', 'prevent-back-history']],
        'user.ad.payment_configuration' => ['payment_configuration', ['POST'], 'user/ad/payment_configuration/{id}', ['auth', 'user', 'verified', 'activity', 'prevent-back-history']],
    ];

    public function test_requested_user_controller_methods_keep_expected_visibility(): void
    {
        $controller = new ReflectionClass(UserController::class);

        foreach (self::PUBLIC_METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "UserController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "UserController::{$method} should stay public.");
        }

        $this->assertTrue($controller->hasMethod('userAdOrFail'));
        $this->assertTrue($controller->getMethod('userAdOrFail')->isPrivate(), 'UserController::userAdOrFail should stay private.');
    }

    public function test_user_routes_keep_expected_contracts(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri, $middleware]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(UserController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");

            foreach ($middleware as $expectedMiddleware) {
                $this->assertContains($expectedMiddleware, $route->gatherMiddleware(), "Route [{$routeName}] should include [{$expectedMiddleware}].");
            }
        }

        $this->assertNull(Route::getRoutes()->getByName('user.ad.activation'), 'ad_activation is currently not registered as a named route.');
    }

    public function test_dashboard_ads_create_edit_and_activation_return_expected_view_data(): void
    {
        $user = $this->activeUser();
        $ownAd = Sponsor::factory()->forUser($user)->create([
            'name' => 'Owned User Ad',
        ]);
        Sponsor::factory()->create([
            'name' => 'Other User Ad',
        ]);

        $this->actingAs($user);

        $dashboard = $this->get(route('user.dashboard'))->assertOk();
        $this->assertSame('dashboard', $dashboard->viewData('view_path'));

        $ads = $this->get(route('user.ads'))->assertOk();
        $this->assertSame('ads', $ads->viewData('view_path'));
        $this->assertSame([$ownAd->id], $ads->viewData('ads')->pluck('id')->all());

        $create = $this->get(route('user.ad.create'))->assertOk();
        $this->assertSame('ad_create', $create->viewData('view_path'));

        $edit = $this->get(route('user.ad.edit', $ownAd->id))->assertOk();
        $this->assertSame('ad_edit', $edit->viewData('view_path'));
        $this->assertTrue($ownAd->is($edit->viewData('ad')));

        Auth::login($user);
        $activation = app(UserController::class)->ad_activation($ownAd->id, Request::create('/user/ad/activation'));
        $this->assertSame('ad_edit', $activation->getData()['view_path']);
        $this->assertTrue($ownAd->is($activation->getData()['ad']));
    }

    public function test_user_can_store_update_and_delete_owned_ad(): void
    {
        Storage::fake('public');
        $this->disableS3Uploads();
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post(route('user.ad.store'), [
                'name' => 'Feature User Ad',
                'description' => 'Feature user ad description',
                'ext_url' => 'https://example.test/feature-user-ad',
                'image' => UploadedFile::fake()->image('feature-user-ad.jpg', 600, 400),
            ])
            ->assertRedirect(route('user.ads'))
            ->assertSessionDoesntHaveErrors();

        $ad = Sponsor::query()->where('name', 'Feature User Ad')->firstOrFail();
        $originalImage = $ad->image;

        $this->assertSame($user->id, $ad->user_id);
        $this->assertSame(1, $ad->status);
        Storage::disk('public')->assertExists('sponsor/thumbnail/'.$ad->image);

        $this->from(route('user.ads'))
            ->post(route('user.ad.update', $ad->id), [
                'name' => 'Feature User Ad Without New Image',
                'description' => 'Updated without image',
                'ext_url' => 'https://example.test/feature-user-ad-no-image',
            ])
            ->assertRedirect(route('user.ads'))
            ->assertSessionDoesntHaveErrors();

        $ad->refresh();
        $this->assertSame('Feature User Ad Without New Image', $ad->name);
        $this->assertSame($originalImage, $ad->image);

        $this->from(route('user.ads'))
            ->post(route('user.ad.update', $ad->id), [
                'name' => 'Feature User Ad Updated',
                'description' => 'Updated with image',
                'ext_url' => 'https://example.test/feature-user-ad-updated',
                'image' => UploadedFile::fake()->image('feature-user-ad-updated.png', 600, 400),
            ])
            ->assertRedirect(route('user.ads'))
            ->assertSessionDoesntHaveErrors();

        $ad->refresh();
        $this->assertSame('Feature User Ad Updated', $ad->name);
        $this->assertNotSame($originalImage, $ad->image);
        Storage::disk('public')->assertExists('sponsor/thumbnail/'.$ad->image);

        $this->from(route('user.ads'))
            ->get(route('user.ad.delete', $ad->id))
            ->assertRedirect(route('user.ads'));

        $this->assertDatabaseMissing('sponsors', [
            'id' => $ad->id,
        ]);
    }

    public function test_ad_charge_and_payment_configuration_use_inclusive_date_range(): void
    {
        $this->setAdChargePerDay('5');
        $user = $this->activeUser();
        $ad = Sponsor::factory()->forUser($user)->create();
        $this->travelTo(now()->setTime(10, 30, 0));

        $this->actingAs($user)
            ->get(route('user.ad.ad_charge_by_daterange', [
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-03',
            ]))
            ->assertOk()
            ->assertContent('15');

        $this->from(route('user.ads'))
            ->post(route('user.ad.payment_configuration', $ad->id), [
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-03',
            ])
            ->assertRedirect(route('payment'))
            ->assertSessionDoesntHaveErrors();

        $paymentDetails = session('payment_details');

        $this->assertSame(15.0, $paymentDetails['payable_amount']);
        $this->assertSame($ad->id, $paymentDetails['items'][0]['id']);
        $this->assertSame(15.0, $paymentDetails['items'][0]['price']);
        $this->assertSame($user->id, $paymentDetails['custom_field']['user_id']);
        $this->assertSame('2026-07-01 10:30:00', $paymentDetails['custom_field']['start_date']);
        $this->assertSame('2026-07-03 10:30:00', $paymentDetails['custom_field']['end_date']);
        $this->assertSame('Sponsor', $paymentDetails['success_method']['model_name']);
        $this->assertSame('add_payment_success', $paymentDetails['success_method']['function_name']);
    }

    public function test_user_ad_or_fail_returns_only_current_users_ad(): void
    {
        $owner = $this->activeUser();
        $viewer = $this->activeUser();
        $ownAd = Sponsor::factory()->forUser($viewer)->create();
        $foreignAd = Sponsor::factory()->forUser($owner)->create();
        $method = new ReflectionMethod(UserController::class, 'userAdOrFail');
        $method->setAccessible(true);
        $controller = app(UserController::class);

        Auth::login($viewer);

        $this->assertTrue($ownAd->is($method->invoke($controller, $ownAd->id)));

        try {
            $method->invoke($controller, $foreignAd->id);
            $this->fail('A foreign ad should be forbidden.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->expectException(NotFoundHttpException::class);
        $method->invoke($controller, 999999);
    }

    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ], $overrides));
    }

    private function disableS3Uploads(): void
    {
        $values = [
            'description' => json_encode([
                'active' => 0,
                'AWS_ACCESS_KEY_ID' => 'test-key',
                'AWS_SECRET_ACCESS_KEY' => 'test-secret',
                'AWS_DEFAULT_REGION' => 'us-test-1',
                'AWS_BUCKET' => 'test-bucket',
            ]),
            'updated_at' => now(),
        ];

        if (Setting::query()->where('type', 'amazon_s3')->update($values) === 0) {
            (new Setting)->forceFill($values + ['type' => 'amazon_s3'])->save();
        }
    }

    private function setAdChargePerDay(string $amount): void
    {
        $values = [
            'description' => $amount,
            'updated_at' => now(),
        ];

        if (Setting::query()->where('type', 'ad_charge_per_day')->update($values) === 0) {
            (new Setting)->forceFill($values + ['type' => 'ad_charge_per_day'])->save();
        }
    }
}
