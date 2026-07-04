<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UserWelcomeControllerBrowserTest extends DuskTestCase
{
    public function test_user_welcome_route_renders_in_browser(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visitRoute('users.welcome', ['user_id' => 'dusk-user-123'])
                ->assertPathIs('/users/dusk-user-123')
                ->assertSee('SocioPro');
        });
    }
}
