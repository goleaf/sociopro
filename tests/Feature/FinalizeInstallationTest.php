<?php

namespace Tests\Feature;

use App\Actions\Install\FinalizeInstallation;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Throwable;

class FinalizeInstallationTest extends TestCase
{
    private ?string $originalSystemName;

    private ?string $originalPurchaseCode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalSystemName = $this->settingDescription('system_name');
        $this->originalPurchaseCode = $this->settingDescription('purchase_code');
    }

    protected function tearDown(): void
    {
        $this->restoreSetting('system_name', $this->originalSystemName);
        $this->restoreSetting('purchase_code', $this->originalPurchaseCode);

        User::query()
            ->whereIn('email', [
                'finalize-admin@example.test',
                'finalize-no-purchase@example.test',
                'finalize-duplicate@example.test',
            ])
            ->delete();

        parent::tearDown();
    }

    public function test_handle_updates_install_settings_and_creates_admin_user(): void
    {
        $user = app(FinalizeInstallation::class)->handle($this->validData([
            'admin_email' => 'finalize-admin@example.test',
        ]), 'purchase-code-123');

        $this->assertSame('Site Admin', $user->name);
        $this->assertSame('finalize-admin@example.test', $user->email);
        $this->assertSame('male', $user->gender);
        $this->assertSame('Local Street', $user->address);
        $this->assertSame('123456789', $user->phone);
        $this->assertSame('Europe/Vilnius', $user->timezone);
        $this->assertSame(UserRole::Admin->value, $user->user_role);
        $this->assertSame('[]', $user->friends);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('secret-password', $user->password));

        $this->assertDatabaseHas('settings', [
            'type' => 'system_name',
            'description' => 'Sociopro Finalized',
        ]);
        $this->assertDatabaseHas('settings', [
            'type' => 'purchase_code',
            'description' => 'purchase-code-123',
        ]);
    }

    public function test_handle_leaves_purchase_code_unchanged_when_code_is_missing(): void
    {
        Setting::query()
            ->where('type', 'purchase_code')
            ->update(['description' => 'existing-purchase-code']);

        app(FinalizeInstallation::class)->handle($this->validData([
            'admin_email' => 'finalize-no-purchase@example.test',
        ]));

        $this->assertSame('existing-purchase-code', $this->settingDescription('purchase_code'));
    }

    public function test_handle_rolls_back_setting_updates_when_admin_creation_fails(): void
    {
        User::factory()->create([
            'email' => 'finalize-duplicate@example.test',
        ]);

        $originalSystemName = $this->settingDescription('system_name');

        $exception = null;

        try {
            app(FinalizeInstallation::class)->handle($this->validData([
                'system_name' => 'Should Roll Back',
                'admin_email' => 'finalize-duplicate@example.test',
            ]));
        } catch (Throwable $throwable) {
            $exception = $throwable;
        }

        $this->assertInstanceOf(QueryException::class, $exception);
        $this->assertSame($originalSystemName, $this->settingDescription('system_name'));
        $this->assertSame(1, User::query()->where('email', 'finalize-duplicate@example.test')->count());
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'system_name' => 'Sociopro Finalized',
            'admin_name' => 'Site Admin',
            'admin_email' => 'finalize-admin@example.test',
            'admin_password' => 'secret-password',
            'admin_address' => 'Local Street',
            'admin_phone' => '123456789',
            'timezone' => 'Europe/Vilnius',
        ], $overrides);
    }

    private function settingDescription(string $type): ?string
    {
        return Setting::query()
            ->where('type', $type)
            ->value('description');
    }

    private function restoreSetting(string $type, ?string $description): void
    {
        Setting::query()
            ->where('type', $type)
            ->update(['description' => $description]);
    }
}
