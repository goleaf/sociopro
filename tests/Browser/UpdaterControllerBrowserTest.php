<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Addon;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UpdaterControllerBrowserTest extends DuskTestCase
{
    private const ADMIN_EMAIL = 'dusk-updater-admin@example.test';

    private const ADDON_IDENTIFIER = 'dusk-updater-addon';

    private const ADDON_TITLE = 'Dusk Updater Addon';

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFixtures();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        parent::tearDown();
    }

    public function test_admin_can_use_addon_manager_status_and_delete_buttons(): void
    {
        $admin = $this->fixtureAdmin();
        $addon = $this->fixtureAddon(status: 1);

        $this->browse(function (Browser $browser) use ($admin, $addon) {
            $browser->loginAs($admin)
                ->visitRoute('admin.addon.manager')
                ->assertSee('Addon manager')
                ->assertSee(self::ADDON_TITLE)
                ->press('Actions')
                ->clickLink('Deactivate')
                ->waitForLocation('/admin/addon/manager', 5)
                ->assertSee('Deactivated');

            $this->assertSame(0, $addon->refresh()->status);

            $browser->press('Actions')
                ->clickLink('Activate')
                ->waitForLocation('/admin/addon/manager', 5)
                ->assertSee('Active');

            $this->assertSame(1, $addon->refresh()->status);

            $browser->script('window.confirm = () => true;');
            $browser->press('Actions')
                ->clickLink('Delete')
                ->waitForLocation('/admin/addon/manager', 5)
                ->assertDontSee(self::ADDON_TITLE);
        });

        $this->assertDatabaseMissing('addons', [
            'unique_identifier' => self::ADDON_IDENTIFIER,
        ]);
    }

    private function fixtureAdmin(): User
    {
        $user = User::query()->where('email', self::ADMIN_EMAIL)->first() ?? new User;
        $user->forceFill([
            'name' => 'Dusk Updater Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => 'dusk-updater-admin',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'profile_status' => 'unlock',
        ])->save();

        return $user;
    }

    private function fixtureAddon(int $status): Addon
    {
        $addon = Addon::query()->where('unique_identifier', self::ADDON_IDENTIFIER)->first() ?? new Addon;
        $addon->forceFill([
            'title' => self::ADDON_TITLE,
            'parent_id' => null,
            'features' => 'Dusk browser fixture',
            'unique_identifier' => self::ADDON_IDENTIFIER,
            'version' => '1.0.0',
            'status' => $status,
        ])->save();

        return $addon;
    }

    private function deleteFixtures(): void
    {
        Addon::query()->where('unique_identifier', self::ADDON_IDENTIFIER)->delete();
        User::query()->where('email', self::ADMIN_EMAIL)->delete();
    }
}
