<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Language;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LanguageSwitchControllerBrowserTest extends DuskTestCase
{
    private const ADMIN_EMAIL = 'dusk-language-switch-admin@example.test';

    private const PRIMARY_LANGUAGE = 'dusk_switch_primary';

    private const SECONDARY_LANGUAGE = 'dusk_switch_secondary';

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

    public function test_admin_header_language_select_switches_active_language_in_browser(): void
    {
        $admin = $this->activeAdmin();
        $this->language(self::PRIMARY_LANGUAGE);
        $this->language(self::SECONDARY_LANGUAGE);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visitRoute('admin.language.settings')
                ->assertSee('Languages')
                ->waitFor('[data-language-switch-url]', 5)
                ->script(<<<'JS'
                    const select = document.querySelector('[data-language-switch-url]');
                    select.value = 'dusk_switch_secondary';
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                JS);

            $browser->waitForLocation('/admin/all/language/settings', 5)
                ->waitUntil("document.querySelector('[data-language-switch-url]')?.value === 'dusk_switch_secondary'", 5);
        });
    }

    private function activeAdmin(): User
    {
        $user = User::query()->where('email', self::ADMIN_EMAIL)->first() ?? new User;
        $user->forceFill([
            'name' => 'Dusk Language Switch Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => 'dusk-language-switch-admin',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
        $user->save();

        return $user;
    }

    private function language(string $name): Language
    {
        $language = Language::query()
            ->where('name', $name)
            ->where('phrase', $name)
            ->first() ?? new Language;
        $language->forceFill([
            'name' => $name,
            'phrase' => $name,
            'translated' => $name,
        ]);
        $language->save();

        return $language;
    }

    private function deleteFixtures(): void
    {
        User::query()->where('email', self::ADMIN_EMAIL)->delete();
        Language::query()
            ->whereIn('name', [
                self::PRIMARY_LANGUAGE,
                self::SECONDARY_LANGUAGE,
            ])
            ->delete();
    }
}
