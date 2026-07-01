<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstallWizardTest extends TestCase
{
    public function test_local_requirement_check_skips_writable_file_gate(): void
    {
        $response = $this
            ->withServerVariables(['SERVER_NAME' => 'localhost'])
            ->get('/install/step1');

        $response->assertOk();
        $response->assertSee('Local development mode');
        $response->assertSee('/install/step3', false);
        $response->assertDontSee('file has write permission');
    }

    public function test_database_step_offers_sqlite_for_local_installation(): void
    {
        $response = $this
            ->withServerVariables(['SERVER_NAME' => 'localhost'])
            ->get('/install/step3');

        $response->assertOk();
        $response->assertSee('SQLite');
        $response->assertSee('name="db_connection"', false);
        $response->assertSee('value="sqlite"', false);
    }
}
