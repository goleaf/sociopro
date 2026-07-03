<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class InstallDatabaseConfigurationBrowserTest extends DuskTestCase
{
    public function test_local_database_step_defaults_to_sqlite_and_toggles_mysql_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/install/step3')
                ->assertPathIs('/install/step3')
                ->assertSelected('db_connection', 'sqlite')
                ->assertSee('SQLite database file')
                ->assertDontSee('Database Name')
                ->select('db_connection', 'mysql')
                ->pause(300)
                ->assertSee('Database Name')
                ->assertSee('Username')
                ->assertSee('Database Host');
        });
    }
}
