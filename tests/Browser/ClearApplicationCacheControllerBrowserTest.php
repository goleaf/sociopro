<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClearApplicationCacheControllerBrowserTest extends DuskTestCase
{
    private const ADMIN_EMAIL = 'dusk-clear-cache-admin@example.test';

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

    public function test_admin_can_open_clear_cache_route_in_browser(): void
    {
        $admin = $this->adminUser();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visitRoute('system.clear-cache')
                ->assertSee('Application cache cleared');
        });
    }

    private function adminUser(): User
    {
        $user = User::query()->where('email', self::ADMIN_EMAIL)->first() ?? new User;
        $user->forceFill([
            'name' => 'Dusk Clear Cache Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => 'dusk-clear-cache-admin',
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

    private function deleteFixtures(): void
    {
        User::query()->where('email', self::ADMIN_EMAIL)->delete();
    }
}
