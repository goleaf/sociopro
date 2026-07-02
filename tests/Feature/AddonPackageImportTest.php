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

    protected function setUp(): void
    {
        parent::setUp();

        $this->packagePath = storage_path('framework/testing/addon-package.zip');
        $this->stepMarkerPath = storage_path('framework/testing/addon-step-executed.txt');

        File::delete($this->packagePath);
        File::delete($this->stepMarkerPath);
        File::delete(base_path('evil-addon.txt'));
        File::ensureDirectoryExists(dirname($this->packagePath));
    }

    protected function tearDown(): void
    {
        File::delete($this->packagePath);
        File::delete($this->stepMarkerPath);
        File::delete(base_path('evil-addon.txt'));

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

    private function manifest(string $identifier): string
    {
        return json_encode([
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
                    'unique_identifier' => $identifier,
                    'title' => 'Safe addon',
                    'features' => 'Import hardening fixture',
                ],
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
