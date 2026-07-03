<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class InstallRequirementsBrowserTest extends DuskTestCase
{
    public function test_local_install_requirements_screen_skips_file_permissions_and_allows_continue(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/install/step1')
                ->assertPathIs('/install/step1')
                ->assertSee('Local development mode')
                ->assertSee('config/database.php')
                ->assertSee('routes/web.php')
                ->assertSee('Skipped on local installation')
                ->assertSee('Curl Enabled')
                ->assertSee('Continue')
                ->assertAttribute('a.btn.btn-primary', 'href', url('/install/step3'));
        });
    }
}
