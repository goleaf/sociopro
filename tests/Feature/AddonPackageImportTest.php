<?php

namespace Tests\Feature;

use App\Actions\Addons\ImportAddonPackage;
use App\Actions\Install\ImportInstallSqlDump;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Jobs\ImportAddonPackageJob;
use App\Models\Addon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class AddonPackageImportTest extends TestCase
{
    use RefreshDatabase;

    private string $packagePath;

    private string $stepMarkerPath;

    private string $distributedPath;

    private string $nestedZipPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packagePath = storage_path('framework/testing/addon-package.zip');
        $this->stepMarkerPath = storage_path('framework/testing/addon-step-executed.txt');
        $this->distributedPath = base_path('storage/framework/testing/addon-distributed');
        $this->nestedZipPath = storage_path('framework/testing/addon-nested.zip');

        File::delete($this->packagePath);
        File::delete($this->nestedZipPath);
        File::delete($this->stepMarkerPath);
        File::delete(base_path('evil-addon.txt'));
        File::deleteDirectory($this->distributedPath);
        File::ensureDirectoryExists(dirname($this->packagePath));
    }

    protected function tearDown(): void
    {
        File::delete($this->packagePath);
        File::delete($this->nestedZipPath);
        File::delete($this->stepMarkerPath);
        File::delete(base_path('evil-addon.txt'));
        File::deleteDirectory($this->distributedPath);

        parent::tearDown();
    }

    public function test_it_imports_addon_package_without_executing_uploaded_php_and_is_idempotent(): void
    {
        $this->createAddonPackage($this->packagePath, 'safe-addon');

        $firstImport = app(ImportAddonPackage::class)->handle($this->packagePath, batchSize: 2);

        $this->assertFalse(File::exists($this->stepMarkerPath));
        $this->assertSame(1, Addon::query()->where('unique_identifier', 'safe-addon')->count());
        $this->assertSame(2, DB::table('addon_import_widgets')->count());
        $this->assertSame(2, $firstImport->sqlResult?->insertedRows());
        $this->assertSame(1, $firstImport->sqlResult?->duplicateRows());
        $this->assertSame(1, $firstImport->addonRowsUpserted);

        $secondImport = app(ImportAddonPackage::class)->handle($this->packagePath, batchSize: 2);

        $this->assertSame(1, Addon::query()->where('unique_identifier', 'safe-addon')->count());
        $this->assertSame(2, DB::table('addon_import_widgets')->count());
        $this->assertSame(0, $secondImport->sqlResult?->insertedRows());
        $this->assertSame(3, $secondImport->sqlResult?->duplicateRows());
        $this->assertSame(1, $secondImport->addonRowsUpserted);
    }

    public function test_import_job_can_process_addon_package_as_queued_work(): void
    {
        $this->createAddonPackage($this->packagePath, 'queued-addon');

        $job = new ImportAddonPackageJob($this->packagePath, batchSize: 1);
        $job->handle(app(ImportAddonPackage::class));

        $this->assertSame(1, Addon::query()->where('unique_identifier', 'queued-addon')->count());
        $this->assertSame('Queued widget', DB::table('addon_import_widgets')->where('id', 1)->value('name'));

        $job->handle(app(ImportAddonPackage::class));

        $this->assertSame(1, Addon::query()->where('unique_identifier', 'queued-addon')->count());
        $this->assertSame('Queued widget', DB::table('addon_import_widgets')->where('id', 1)->value('name'));
        $this->assertSame(2, DB::table('addon_import_widgets')->count());
    }

    public function test_admin_addon_install_route_dispatches_import_job_after_commit_with_stored_payload(): void
    {
        Bus::fake();
        Storage::fake('local');
        $this->createAddonPackage($this->packagePath, 'route-addon');
        $admin = $this->adminUser();

        $upload = new UploadedFile($this->packagePath, 'route-addon.zip', 'application/zip', null, true);

        $this->actingAs($admin)
            ->post(route('addon.install'), ['file' => $upload])
            ->assertRedirect();

        Bus::assertDispatched(ImportAddonPackageJob::class, function (ImportAddonPackageJob $job): bool {
            $this->assertSame(ImportInstallSqlDump::DEFAULT_BATCH_SIZE, $job->batchSize);
            $this->assertTrue($job->afterCommit);
            $this->assertStringContainsString('addon-imports/', str_replace('\\', '/', $job->packagePath));
            $this->assertFileExists($job->packagePath);

            return true;
        });
        $this->assertSame(0, Addon::query()->where('unique_identifier', 'route-addon')->count());
    }

    public function test_admin_addon_install_route_does_not_dispatch_when_validation_fails(): void
    {
        Bus::fake();

        $this->actingAs($this->adminUser())
            ->post(route('addon.install'), [])
            ->assertSessionHasErrors(['file']);

        Bus::assertNotDispatched(ImportAddonPackageJob::class);
    }

    public function test_admin_addon_install_route_does_not_dispatch_when_authorization_fails(): void
    {
        Bus::fake();
        $this->createAddonPackage($this->packagePath, 'blocked-addon');
        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
        ]);
        $upload = new UploadedFile($this->packagePath, 'blocked-addon.zip', 'application/zip', null, true);

        $this->actingAs($user)
            ->post(route('addon.install'), ['file' => $upload])
            ->assertRedirect(route('timeline'));

        Bus::assertNotDispatched(ImportAddonPackageJob::class);
    }

    public function test_it_rejects_zip_entries_that_escape_the_package_directory(): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->packagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('../evil-addon.txt', 'owned');
        $zip->addFromString('unsafe-addon/step2_config.json', $this->manifest('unsafe-addon'));
        $zip->close();

        try {
            app(ImportAddonPackage::class)->handle($this->packagePath);
            $this->fail('Unsafe addon package path was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Addon package contains an unsafe path.', $exception->getMessage());
        }

        $this->assertFalse(File::exists(base_path('evil-addon.txt')));
    }

    public function test_import_action_is_container_resolvable_and_accepts_uploaded_file_packages(): void
    {
        $this->assertInstanceOf(ImportAddonPackage::class, app(ImportAddonPackage::class));
        $this->createAddonPackage($this->packagePath, 'uploaded-addon');

        $result = app(ImportAddonPackage::class)->handle(
            new UploadedFile($this->packagePath, 'uploaded-addon.zip', 'application/zip', null, true)
        );

        $this->assertSame('Addon installed successfully', $result->message);
        $this->assertSame(1, Addon::query()->where('unique_identifier', 'uploaded-addon')->count());
    }

    public function test_it_rejects_missing_and_invalid_package_files(): void
    {
        $this->assertImportFails('/missing/addon.zip', 'Addon package was not found.');

        File::put($this->packagePath, 'not a zip file');

        $this->assertImportFails($this->packagePath, 'Addon package could not be opened.');
    }

    public function test_it_requires_exactly_one_manifest_root(): void
    {
        $this->createPackageFromEntries([
            'addon/readme.txt' => 'missing manifest',
        ]);

        $this->assertImportFails($this->packagePath, 'Addon package must contain one step2_config.json manifest.');

        $this->createPackageFromEntries([
            'addon-a/step2_config.json' => $this->manifest('addon-a'),
            'addon-b/step2_config.json' => $this->manifest('addon-b'),
        ]);

        $this->assertImportFails($this->packagePath, 'Addon package must contain one step2_config.json manifest.');
    }

    public function test_it_accepts_top_level_manifest_root(): void
    {
        $this->createPackageFromEntries([
            'step2_config.json' => $this->manifest('top-level-addon'),
        ]);

        $result = app(ImportAddonPackage::class)->handle($this->packagePath);

        $this->assertSame('Addon installed successfully', $result->message);
        $this->assertSame(1, Addon::query()->where('unique_identifier', 'top-level-addon')->count());
    }

    public function test_it_rejects_invalid_manifest_json(): void
    {
        $this->createPackageFromEntries([
            'broken-addon/step2_config.json' => '{',
        ]);

        $this->assertImportFails($this->packagePath, 'Addon manifest is not valid JSON.');
    }

    public function test_it_rejects_incompatible_addon_and_product_update_versions(): void
    {
        DB::table('settings')->where('type', 'version')->update(['description' => '1.0.0']);

        $this->createPackageFromEntries([
            'future-addon/step2_config.json' => $this->addonManifest(
                addons: [[
                    'unique_identifier' => 'future-addon',
                    'title' => 'Future addon',
                    'features' => 'Requires newer product',
                ]],
                minimumProductVersion: '9.9.9'
            ),
        ]);

        $this->assertImportFails($this->packagePath, "You have to update your main application's version.");

        $this->createPackageFromEntries([
            'skipped-update/step2_config.json' => $this->productUpdateManifest(
                minimumProductVersion: '1.1.0',
                updateVersion: '1.2.0'
            ),
        ]);

        $this->assertImportFails($this->packagePath, 'It looks like you are skipping a version.');
    }

    public function test_it_reports_product_updates_and_addon_updates_with_distinct_success_messages(): void
    {
        DB::table('settings')->where('type', 'version')->update(['description' => '1.0.0']);

        $this->createPackageFromEntries([
            'product-update/step2_config.json' => $this->productUpdateManifest(
                minimumProductVersion: '1.0.0',
                updateVersion: '1.1.0'
            ),
        ]);

        $productUpdate = app(ImportAddonPackage::class)->handle($this->packagePath);

        $this->assertSame('Version updated successfully', $productUpdate->message);
        $this->assertSame('1.1.0', DB::table('settings')->where('type', 'version')->value('description'));

        $this->createPackageFromEntries([
            'addon-update/step2_config.json' => $this->addonManifest(
                addons: [[
                    'unique_identifier' => 'addon-update',
                    'title' => 'Addon update',
                    'features' => 'Update fixture',
                ]],
                minimumProductVersion: '1.1.0',
                minimumAddonVersion: '1.0.0',
                updateVersion: '1.2.0'
            ),
        ]);

        $addonUpdate = app(ImportAddonPackage::class)->handle($this->packagePath);

        $this->assertSame('Addon updated successfully', $addonUpdate->message);
        $this->assertSame('1.2.0', Addon::query()->where('unique_identifier', 'addon-update')->value('version'));
    }

    public function test_it_distributes_sources_in_batches_and_extracts_nested_zip_files(): void
    {
        $this->createNestedZip([
            'nested-inner.txt' => 'nested contents',
        ]);

        $this->createPackageFromEntries([
            'source-addon/step2_config.json' => $this->manifest('source-addon'),
            'source-addon/sources/storage/framework/testing/addon-distributed/copied.txt' => 'copied contents',
            'source-addon/sources/storage/framework/testing/addon-distributed/nested.zip' => File::get($this->nestedZipPath),
        ]);

        $result = app(ImportAddonPackage::class)->handle($this->packagePath, batchSize: 1);

        $this->assertSame(2, $result->filesDistributed);
        $this->assertSame('copied contents', File::get($this->distributedPath.'/copied.txt'));
        $this->assertSame('nested contents', File::get($this->distributedPath.'/nested-inner.txt'));
        $this->assertFalse(File::exists($this->distributedPath.'/nested.zip'));
    }

    public function test_it_rejects_unsafe_nested_zip_entries(): void
    {
        $this->createNestedZip([
            '../evil-addon.txt' => 'owned',
        ]);

        $this->createPackageFromEntries([
            'nested-unsafe-addon/step2_config.json' => $this->manifest('nested-unsafe-addon'),
            'nested-unsafe-addon/sources/storage/framework/testing/addon-distributed/nested.zip' => File::get($this->nestedZipPath),
        ]);

        $this->assertImportFails($this->packagePath, 'Addon package contains an unsafe path.');
        $this->assertFalse(File::exists(base_path('evil-addon.txt')));
    }

    public function test_it_upserts_multiple_addons_with_parent_relationship(): void
    {
        $this->createPackageFromEntries([
            'multi-addon/step2_config.json' => $this->addonManifest([
                [
                    'unique_identifier' => 'parent-addon',
                    'title' => 'Parent addon',
                    'features' => 'Parent fixture',
                ],
                [
                    'unique_identifier' => 'child-addon',
                    'title' => 'Child addon',
                    'features' => 'Child fixture',
                ],
            ]),
        ]);

        $result = app(ImportAddonPackage::class)->handle($this->packagePath);

        $parent = Addon::query()->where('unique_identifier', 'parent-addon')->firstOrFail();
        $child = Addon::query()->where('unique_identifier', 'child-addon')->firstOrFail();

        $this->assertSame(2, $result->addonRowsUpserted);
        $this->assertNull($parent->parent_id);
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame('1.0.0', $child->version);
        $this->assertSame(1, $child->status);
    }

    public function test_zip_path_safety_rules_cover_private_path_checker(): void
    {
        $method = new \ReflectionMethod(ImportAddonPackage::class, 'isUnsafeZipPath');
        $method->setAccessible(true);
        $importer = app(ImportAddonPackage::class);

        foreach (['', '../evil.txt', 'safe/../../evil.txt', '/absolute.txt', 'C:/absolute.txt', "null\0byte.txt"] as $path) {
            $this->assertTrue($method->invoke($importer, $path), "Expected [{$path}] to be unsafe.");
        }

        foreach (['safe/file.txt', 'safe/nested/file.txt', 'storage/framework/testing/copied.txt'] as $path) {
            $this->assertFalse($method->invoke($importer, $path), "Expected [{$path}] to be safe.");
        }
    }

    private function createAddonPackage(string $path, string $identifier): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('safe-addon/step1_pre_checker.php', $this->markerPhp());
        $zip->addFromString('safe-addon/step2_config.json', $this->manifest($identifier));
        $zip->addFromString('safe-addon/step3_database.sql', <<<'SQL'
CREATE TABLE `addon_import_widgets` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `addon_import_widgets` (`id`, `name`) VALUES
(1, 'Queued widget'),
(1, 'Duplicate widget'),
(2, 'Second widget');

ALTER TABLE `addon_import_widgets`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `addon_import_widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
SQL);
        $zip->addFromString('safe-addon/step4_update_data.php', $this->markerPhp());
        $zip->close();
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function createPackageFromEntries(array $entries): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->packagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        foreach ($entries as $path => $contents) {
            $zip->addFromString($path, $contents);
        }

        $zip->close();
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function createNestedZip(array $entries): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->nestedZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        foreach ($entries as $path => $contents) {
            $zip->addFromString($path, $contents);
        }

        $zip->close();
    }

    private function assertImportFails(string $packagePath, string $expectedMessage): void
    {
        try {
            app(ImportAddonPackage::class)->handle($packagePath);
            $this->fail("Import should have failed with [{$expectedMessage}].");
        } catch (RuntimeException $exception) {
            $this->assertSame($expectedMessage, $exception->getMessage());
        }
    }

    private function manifest(string $identifier): string
    {
        return $this->addonManifest([[
            'unique_identifier' => $identifier,
            'title' => 'Safe addon',
            'features' => 'Import hardening fixture',
        ]]);
    }

    /**
     * @param  list<array{unique_identifier: string, title: string, features: string}>  $addons
     */
    private function addonManifest(
        array $addons,
        string $minimumProductVersion = '0',
        string $minimumAddonVersion = '0',
        string $updateVersion = '1.0.0'
    ): string {
        return json_encode([
            'is_addon' => '1',
            'product_version' => [
                'minimum_required_version' => $minimumProductVersion,
            ],
            'addon_version' => [
                'minimum_required_version' => $minimumAddonVersion,
                'update_version' => $updateVersion,
            ],
            'addons' => $addons,
        ], JSON_THROW_ON_ERROR);
    }

    private function productUpdateManifest(string $minimumProductVersion, string $updateVersion): string
    {
        return json_encode([
            'is_addon' => '0',
            'product_version' => [
                'minimum_required_version' => $minimumProductVersion,
                'update_version' => $updateVersion,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function markerPhp(): string
    {
        return "<?php file_put_contents('{$this->stepMarkerPath}', 'executed');";
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
        ]);
    }
}
