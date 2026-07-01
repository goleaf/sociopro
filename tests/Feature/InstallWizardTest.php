<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

    public function test_finalizing_setup_updates_settings_and_creates_admin_user(): void
    {
        $response = $this->post('/install/finalizing_setup', [
            'system_name' => 'Sociopro Local',
            'admin_name' => 'Site Admin',
            'admin_email' => 'admin@example.test',
            'admin_password' => 'secret-password',
            'admin_address' => 'Local Street',
            'admin_phone' => '123456789',
            'timezone' => 'Europe/Vilnius',
        ]);

        $response->assertRedirect(route('success'));

        $this->assertDatabaseHas('settings', [
            'type' => 'system_name',
            'description' => 'Sociopro Local',
        ]);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('secret-password', $admin->password));
        $this->assertSame('admin', $admin->user_role);
        $this->assertSame('Europe/Vilnius', $admin->timezone);
    }

    public function test_finalizing_setup_requires_admin_details(): void
    {
        $response = $this->from('/install/finalizing_setup')->post('/install/finalizing_setup', [
            'system_name' => 'Sociopro Local',
        ]);

        $response->assertRedirect('/install/finalizing_setup');
        $response->assertSessionHasErrors([
            'admin_name',
            'admin_email',
            'admin_password',
            'admin_address',
            'admin_phone',
            'timezone',
        ]);
    }
}
