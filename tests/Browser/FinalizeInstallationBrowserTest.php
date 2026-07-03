<?php

namespace Tests\Browser;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FinalizeInstallationBrowserTest extends DuskTestCase
{
    private ?string $createdEmail = null;

    private ?string $originalSystemName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalSystemName = $this->settingDescription('system_name');
    }

    protected function tearDown(): void
    {
        if ($this->createdEmail) {
            User::query()
                ->where('email', $this->createdEmail)
                ->delete();
        }

        Setting::query()
            ->where('type', 'system_name')
            ->update(['description' => $this->originalSystemName]);

        parent::tearDown();
    }

    public function test_finalizing_setup_form_creates_admin_user(): void
    {
        $this->createdEmail = 'dusk-finalize-'.Str::uuid()->toString().'@example.test';

        $this->browse(function (Browser $browser) {
            $browser->visit('/install/finalizing_setup')
                ->assertPathIs('/install/finalizing_setup')
                ->assertSee('Set me up')
                ->type('system_name', 'Dusk Finalized App')
                ->type('admin_name', 'Dusk Final Admin')
                ->type('admin_email', $this->createdEmail)
                ->type('admin_password', 'secret-password')
                ->type('admin_address', 'Dusk Street')
                ->type('admin_phone', '123456789')
                ->select('timezone', 'Europe/Vilnius')
                ->press('Set me up')
                ->waitForLocation('/install/success', 10)
                ->assertPathIs('/install/success');
        });

        $admin = User::query()
            ->where('email', $this->createdEmail)
            ->firstOrFail();

        $this->assertSame('Dusk Final Admin', $admin->name);
        $this->assertSame(UserRole::Admin->value, $admin->user_role);
        $this->assertSame('Europe/Vilnius', $admin->timezone);
        $this->assertTrue(Hash::check('secret-password', $admin->password));
        $this->assertSame('Dusk Finalized App', $this->settingDescription('system_name'));
    }

    private function settingDescription(string $type): ?string
    {
        return Setting::query()
            ->where('type', $type)
            ->value('description');
    }
}
