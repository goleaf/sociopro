<?php

namespace Tests\Feature;

use App\Actions\Install\ImportInstallSqlDump;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Updater;
use App\Jobs\ImportAddonPackageJob;
use App\Models\Addon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;
use ZipArchive;

class UpdaterControllerTest extends TestCase
{
    use RefreshDatabase;

    private const METHODS = [
        'update',
        'addon_manager',
        'addon_status',
        'addon_delete',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string, 3: list<string>}>
     */
    private const ROUTES = [
        'admin.addon.create' => ['update', ['POST'], 'admin/addon/create', ['auth', 'verified', 'activity', 'admin', 'prevent-back-history']],
        'admin.addon.update' => ['update', ['POST'], 'admin/addon/update', ['auth', 'verified', 'activity', 'admin', 'prevent-back-history']],
        'admin.product.update' => ['update', ['POST'], 'admin/product/update', ['auth', 'verified', 'activity', 'admin', 'prevent-back-history']],
        'addon.install' => ['update', ['POST'], 'admin/addon/install', ['auth', 'verified', 'activity', 'admin', 'prevent-back-history']],
        'admin.addon.manager' => ['addon_manager', ['GET', 'HEAD'], 'admin/addon/manager', ['auth', 'verified', 'activity', 'admin', 'prevent-back-history']],
        'addon.status' => ['addon_status', ['GET', 'HEAD'], 'admin/addon/status/{status}/{id}', ['auth', 'verified', 'activity', 'admin', 'prevent-back-history']],
        'addon.delete' => ['addon_delete', ['GET', 'HEAD'], 'admin/addon/delete/{id}', ['auth', 'verified', 'activity', 'admin', 'prevent-back-history']],
    ];

    private string $packagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packagePath = storage_path('framework/testing/updater-controller-addon.zip');
        File::delete($this->packagePath);
        File::ensureDirectoryExists(dirname($this->packagePath));
    }

    protected function tearDown(): void
    {
        File::delete($this->packagePath);

        parent::tearDown();
    }

    public function test_requested_updater_methods_stay_public(): void
    {
        $controller = new ReflectionClass(Updater::class);

        foreach (self::METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "Updater::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "Updater::{$method} should stay public.");
        }
    }

    public function test_updater_routes_keep_expected_contracts(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri, $middleware]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(Updater::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");

            foreach ($middleware as $expectedMiddleware) {
                $this->assertContains($expectedMiddleware, $route->gatherMiddleware(), "Route [{$routeName}] should include [{$expectedMiddleware}].");
            }
        }
    }

    public function test_addon_manager_returns_paginated_backend_view_data(): void
    {
        $admin = $this->adminUser();
        $olderAddon = Addon::factory()->create([
            'title' => 'Updater Older Addon',
            'status' => 1,
        ]);
        $newerAddon = Addon::factory()->create([
            'title' => 'Updater Newer Addon',
            'status' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.addon.manager'));

        $response->assertOk();
        $this->assertSame('addons.index', $response->viewData('view_path'));
        $this->assertSame(
            [$newerAddon->id, $olderAddon->id],
            $response->viewData('addons')->getCollection()->pluck('id')->all()
        );
    }

    public function test_update_stores_uploaded_package_and_dispatches_import_job(): void
    {
        Bus::fake();
        Storage::fake('local');
        $this->createAddonPackage();
        $admin = $this->adminUser();
        $upload = new UploadedFile($this->packagePath, 'updater-addon.zip', 'application/zip', null, true);

        $this->actingAs($admin)
            ->from(route('admin.addon.manager'))
            ->post(route('addon.install'), ['file' => $upload])
            ->assertRedirect(route('admin.addon.manager'))
            ->assertSessionDoesntHaveErrors();

        $this->assertCount(1, Storage::disk('local')->files('addon-imports'));
        Bus::assertDispatched(ImportAddonPackageJob::class, function (ImportAddonPackageJob $job): bool {
            $this->assertSame(ImportInstallSqlDump::DEFAULT_BATCH_SIZE, $job->batchSize);
            $this->assertTrue($job->afterCommit);
            $this->assertStringContainsString('addon-imports/', str_replace('\\', '/', $job->packagePath));
            $this->assertFileExists($job->packagePath);

            return true;
        });
    }

    public function test_addon_status_toggles_active_state_and_ignores_unknown_statuses(): void
    {
        $admin = $this->adminUser();
        $addon = Addon::factory()->create([
            'status' => 0,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.addon.manager'))
            ->get(route('addon.status', ['status' => 'activate', 'id' => $addon->id]))
            ->assertRedirect(route('admin.addon.manager'));
        $this->assertSame(1, $addon->refresh()->status);

        $this->from(route('admin.addon.manager'))
            ->get(route('addon.status', ['status' => 'deactivate', 'id' => $addon->id]))
            ->assertRedirect(route('admin.addon.manager'));
        $this->assertSame(0, $addon->refresh()->status);

        $this->from(route('admin.addon.manager'))
            ->get(route('addon.status', ['status' => 'unexpected', 'id' => $addon->id]))
            ->assertRedirect(route('admin.addon.manager'));
        $this->assertSame(0, $addon->refresh()->status);
    }

    public function test_addon_delete_removes_addon_record(): void
    {
        $admin = $this->adminUser();
        $addon = Addon::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.addon.manager'))
            ->get(route('addon.delete', $addon->id))
            ->assertRedirect(route('admin.addon.manager'));

        $this->assertDatabaseMissing('addons', [
            'id' => $addon->id,
        ]);
    }

    private function createAddonPackage(): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->packagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('updater-addon/step2_config.json', json_encode([
            'is_addon' => '1',
            'product_version' => [
                'minimum_required_version' => '0',
            ],
            'addon_version' => [
                'minimum_required_version' => '0',
                'update_version' => '1.0.0',
            ],
            'addons' => [
                [
                    'unique_identifier' => 'updater-controller-addon',
                    'title' => 'Updater Controller Addon',
                    'features' => 'Feature upload fixture',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $zip->close();
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
