<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\SponsorController;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class SponsorControllerTest extends TestCase
{
    use RefreshDatabase;

    private const METHODS = [
        'view_sponsor',
        'create_sponsor',
        'save_sponsor',
        'edit_sponsor',
        'update_sponsor',
        'delete_sponsor',
        'ad_status',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string, 3: list<string>}>
     */
    private const ROUTES = [
        'admin.view.sponsor' => ['view_sponsor', ['GET', 'HEAD'], 'admin/sponsor/view', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.create.sponsor' => ['create_sponsor', ['GET', 'HEAD'], 'admin/sponsor/create', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.save.sponsor' => ['save_sponsor', ['POST'], 'admin/sponsor/save', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.edit.sponsor' => ['edit_sponsor', ['GET', 'HEAD'], 'admin/sponsor/edit/{id}', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.update.sponsor' => ['update_sponsor', ['POST'], 'admin/sponsor/update/{id}', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.delete.sponsor' => ['delete_sponsor', ['GET', 'HEAD'], 'admin/sponsor/delete/{id}', ['auth', 'verified', 'admin', 'prevent-back-history']],
    ];

    public function test_requested_sponsor_controller_methods_stay_public(): void
    {
        $controller = new ReflectionClass(SponsorController::class);

        foreach (self::METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "SponsorController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "SponsorController::{$method} should stay public.");
        }
    }

    public function test_sponsor_routes_keep_expected_contracts(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri, $middleware]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(SponsorController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");

            foreach ($middleware as $expectedMiddleware) {
                $this->assertContains($expectedMiddleware, $route->gatherMiddleware(), "Route [{$routeName}] should include [{$expectedMiddleware}].");
            }
        }

        $this->assertNull(Route::getRoutes()->getByName('admin.ad.status'), 'ad_status is not currently registered as an admin route.');
    }

    public function test_admin_sponsor_pages_return_expected_view_data(): void
    {
        $admin = $this->adminUser();
        $sponsor = Sponsor::factory()->forUser($admin)->create([
            'name' => 'Feature Sponsor Visible',
            'status' => 1,
        ]);

        $this->actingAs($admin);

        $index = $this->get(route('admin.view.sponsor'))->assertOk();
        $this->assertSame('sponsor.index', $index->viewData('view_path'));
        $this->assertSame([$sponsor->id], $index->viewData('sponsors')->pluck('id')->all());

        $create = $this->get(route('admin.create.sponsor'))->assertOk();
        $this->assertSame('sponsor.create', $create->viewData('view_path'));

        $edit = $this->get(route('admin.edit.sponsor', $sponsor->id))->assertOk();
        $this->assertSame('sponsor.edit', $edit->viewData('view_path'));
        $this->assertTrue($sponsor->is($edit->viewData('sponsor')));
    }

    public function test_save_update_delete_and_ad_status_mutate_sponsor_records(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $this->from(route('admin.create.sponsor'))->post(route('admin.save.sponsor'), [
            'name' => 'Feature Sponsor Created',
            'ext_url' => 'https://example.test/feature-sponsor',
            'description' => 'Feature sponsor description',
            'image' => UploadedFile::fake()->image('feature-sponsor.jpg', 600, 400),
        ])->assertRedirect(route('admin.create.sponsor'));

        $sponsor = Sponsor::query()->where('name', 'Feature Sponsor Created')->firstOrFail();
        $this->assertSame($admin->id, $sponsor->user_id);
        $this->assertSame(1, $sponsor->status);
        Storage::disk('public')->assertExists('sponsor/thumbnail/'.$sponsor->image);

        $this->from(route('admin.edit.sponsor', $sponsor->id))->post(route('admin.update.sponsor', $sponsor->id), [
            'name' => 'Feature Sponsor Updated',
            'ext_url' => 'https://example.test/updated-feature-sponsor',
            'description' => 'Updated feature sponsor description',
            'end_date' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'status' => '0',
            'image' => UploadedFile::fake()->image('feature-sponsor-updated.png', 600, 400),
        ])->assertRedirect(route('admin.view.sponsor'));

        $sponsor->refresh();
        $this->assertSame('Feature Sponsor Updated', $sponsor->name);
        $this->assertSame('https://example.test/updated-feature-sponsor', $sponsor->ext_url);
        $this->assertSame(0, $sponsor->status);
        $this->assertSame(now()->addDays(3)->format('Y-m-d H:i'), $sponsor->end_date->format('Y-m-d H:i'));
        Storage::disk('public')->assertExists('sponsor/thumbnail/'.$sponsor->image);

        $this->assertTrue($this->callAdStatus('active', $sponsor)->isRedirect());
        $this->assertSame(1, $sponsor->refresh()->status);

        $this->assertTrue($this->callAdStatus('deactive', $sponsor)->isRedirect());
        $this->assertSame(0, $sponsor->refresh()->status);

        $this->from(route('admin.view.sponsor'))->get(route('admin.delete.sponsor', $sponsor->id))
            ->assertRedirect(route('admin.view.sponsor'));

        $this->assertDatabaseMissing('sponsors', ['id' => $sponsor->id]);
    }

    public function test_ad_status_does_not_activate_expired_sponsor(): void
    {
        $admin = $this->adminUser();
        $sponsor = Sponsor::factory()->forUser($admin)->expired()->create([
            'status' => 0,
        ]);

        $this->actingAs($admin);

        $this->assertTrue($this->callAdStatus('active', $sponsor)->isRedirect());

        $this->assertSame(0, $sponsor->refresh()->status);
    }

    private function callAdStatus(string $type, Sponsor $sponsor): RedirectResponse
    {
        return app(SponsorController::class)->ad_status($type, $sponsor->id);
    }

    private function adminUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ], $overrides));
    }
}
