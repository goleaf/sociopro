<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Addon;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use ZipArchive;

class AddonPackageImportBrowserTest extends DuskTestCase
{
    private string $identifier = 'dusk-upload-addon';

    private string $packagePath;

    private string $addonImportsPath;

    /**
     * @var list<string>
     */
    private array $existingStoredPackages = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->packagePath = storage_path('framework/testing/dusk-upload-addon.zip');
        $this->addonImportsPath = storage_path('app/addon-imports');
        $this->existingStoredPackages = $this->storedAddonPackages();

        Addon::query()->where('unique_identifier', $this->identifier)->delete();
        File::delete($this->packagePath);
        File::ensureDirectoryExists(dirname($this->packagePath));
    }

    protected function tearDown(): void
    {
        Addon::query()->where('unique_identifier', $this->identifier)->delete();
        File::delete($this->packagePath);
        $this->deleteStoredPackagesCreatedByTest();

        parent::tearDown();
    }

    public function test_admin_can_open_addon_install_modal_and_upload_package(): void
    {
        $this->createAddonPackage();
        $admin = $this->adminUser();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/addon/manager')
                ->assertSee('Addon manager')
                ->click('#addon-install-btn')
                ->waitFor('#formFileSm', 10)
                ->script("const purchaseCode = document.querySelector('#purchase_code'); if (purchaseCode) { purchaseCode.value = 'DUSK-CODE'; }");

            $browser->attach('#formFileSm', $this->packagePath)
                ->press('Install')
                ->pause(1000)
                ->assertPathIs('/admin/addon/manager');
        });

        $this->assertSame(1, Addon::query()->where('unique_identifier', $this->identifier)->count());
    }

    private function createAddonPackage(): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->packagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('dusk-addon/step2_config.json', json_encode([
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
                    'unique_identifier' => $this->identifier,
                    'title' => 'Dusk upload addon',
                    'features' => 'Browser upload fixture',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $zip->close();
    }

    private function adminUser(): User
    {
        return User::query()->updateOrCreate([
            'email' => 'dusk-addon-admin@example.test',
        ], [
            'name' => 'Dusk Addon Admin',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => 'dusk-addon-admin',
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
        ]);
    }

    /**
     * @return list<string>
     */
    private function storedAddonPackages(): array
    {
        if (! File::isDirectory($this->addonImportsPath)) {
            return [];
        }

        return array_map(
            static fn (\SplFileInfo $file): string => $file->getPathname(),
            File::files($this->addonImportsPath)
        );
    }

    private function deleteStoredPackagesCreatedByTest(): void
    {
        foreach ($this->storedAddonPackages() as $path) {
            if (! in_array($path, $this->existingStoredPackages, true)) {
                File::delete($path);
            }
        }
    }
}
