<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstallWizardTest extends TestCase
{
    public function test_install_routes_use_install_name_prefix(): void
    {
        $this->assertSame(url('/install/step1'), route('install.step1'));
        $this->assertSame(url('/install/step3'), route('install.step3'));
        $this->assertSame(url('/install/finalizing_setup'), route('install.finalizing'));
        $this->assertSame(url('/install/success'), route('install.success'));
    }

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

    public function test_database_step_rejects_unknown_connection_type(): void
    {
        $response = $this->from('/install/step3')->post(route('install.step3'), [
            'db_connection' => 'pgsql',
        ]);

        $response->assertRedirect('/install/step3');
        $response->assertSessionHasErrors(['db_connection']);
    }

    public function test_database_step_displays_validation_errors(): void
    {
        $response = $this
            ->followingRedirects()
            ->from('/install/step3')
            ->post(route('install.step3'), [
                'db_connection' => 'pgsql',
            ]);

        $response->assertOk();
        $response->assertSee('The selected db connection is invalid.');
    }

    public function test_database_step_prepares_sqlite_connection(): void
    {
        $sqlitePath = database_path('wizard-test.sqlite');

        @unlink($sqlitePath);

        $response = $this->post(route('install.step3'), [
            'db_connection' => 'sqlite',
            'sqlite_path' => $sqlitePath,
        ]);

        $response->assertRedirect(route('install.step4'));
        $response->assertSessionHas('db_connection', 'sqlite');
        $response->assertSessionHas('dbname', $sqlitePath);

        @unlink($sqlitePath);
    }

    public function test_purchase_code_validation_requires_a_code(): void
    {
        $response = $this->from('/install/step2')->post(route('install.validate'));

        $response->assertRedirect('/install/step2');
        $response->assertSessionHasErrors(['purchase_code']);
    }

    public function test_purchase_code_validation_stores_verified_session(): void
    {
        $response = $this->post(route('install.validate'), [
            'purchase_code' => 'purchase-code-123',
        ]);

        $response->assertRedirect(route('install.step3'));
        $response->assertSessionHas('purchase_code', 'purchase-code-123');
        $response->assertSessionHas('purchase_code_verified', 1);
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

        $response->assertRedirect(route('install.success'));

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

    public function test_finalizing_setup_view_does_not_build_timezone_options(): void
    {
        $view = File::get(resource_path('views/install/finalizing_setup.blade.php'));

        $this->assertStringNotContainsString('DateTimeZone::listIdentifiers', $view);
    }
}
