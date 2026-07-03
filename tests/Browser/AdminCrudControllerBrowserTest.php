<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\AccountActiveRequest;
use App\Models\Category;
use App\Models\Currency;
use App\Models\PageCategory;
use App\Models\PaymentGateway;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminCrudControllerBrowserTest extends DuskTestCase
{
    /**
     * @var list<string>
     */
    private array $fixtureEmails = [
        'dusk-admin-crud@example.test',
        'dusk-managed-user@example.test',
        'dusk-disabled-managed-user@example.test',
    ];

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

    public function test_admin_uses_crud_forms_and_account_activation_buttons(): void
    {
        $admin = $this->fixtureUser('dusk-admin-crud@example.test', UserRole::Admin, UserAccountStatus::Active);
        $disabledUser = $this->fixtureUser('dusk-disabled-managed-user@example.test', UserRole::General, UserAccountStatus::Disabled);
        AccountActiveRequest::factory()->create([
            'user_id' => $disabledUser->id,
            'status' => 'pending',
        ]);
        $paymentGateway = $this->paymentGateway();
        $this->currency('USD');

        $this->browse(function (Browser $browser) use ($admin, $disabledUser, $paymentGateway) {
            $browser->loginAs($admin)
                ->visitRoute('admin.create.category')
                ->assertSee('All Page Categories')
                ->type('pagecategory', 'Dusk Page Category')
                ->press('Submit')
                ->waitForText('All Page Categories', 5);

            $browser->visitRoute('admin.user.add')
                ->assertSee('Add a new user')
                ->type('name', 'Dusk Managed User')
                ->type('email', 'dusk-managed-user@example.test')
                ->type('password', 'secret-password')
                ->type('phone', '+37060000003')
                ->type('address', 'Dusk Street')
                ->radio('gender', 'male')
                ->type('bio', 'Created through Dusk')
                ->script(<<<'JS'
                    const dateInput = document.getElementById('date_of_birth');
                    dateInput.value = '1990-01-01';
                    dateInput.dispatchEvent(new Event('input', { bubbles: true }));
                    dateInput.dispatchEvent(new Event('change', { bubbles: true }));
                    document.querySelectorAll('.daterangepicker').forEach((element) => element.style.display = 'none');
                JS);

            $browser
                ->press('Submit')
                ->waitForLocation('/admin/users', 5);

            $browser->visitRoute('admin.payment_gateway.edit', $paymentGateway->id)
                ->assertSee('Dusk Gateway')
                ->select('currency', 'USD')
                ->type('public_key', 'pk_dusk')
                ->type('secret_key', 'sk_dusk')
                ->press('Save changes')
                ->waitForLocation('/admin/settings/payment', 5);

            $browser->visitRoute('admin.users.accountActiveReq')
                ->assertSee($disabledUser->email)
                ->script('window.confirm = () => true;');

            $browser->press('Actions')
                ->clickLink('Approved')
                ->waitForLocation('/admin/users', 5);
        });

        $this->assertDatabaseHas('pagecategories', [
            'name' => 'Dusk Page Category',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'dusk-managed-user@example.test',
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
        ]);
        $this->assertSame([
            'public_key' => 'pk_dusk',
            'secret_key' => 'sk_dusk',
        ], $paymentGateway->refresh()->decodedKeys());
        $this->assertDatabaseHas('users', [
            'id' => $disabledUser->id,
            'status' => UserAccountStatus::Active->value,
        ]);
        $this->assertDatabaseMissing('account_active_requests', [
            'user_id' => $disabledUser->id,
        ]);
    }

    private function fixtureUser(string $email, UserRole $role, UserAccountStatus $status): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill([
            'name' => str($email)->before('@')->headline()->toString(),
            'email' => $email,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => str_replace(['@', '.'], '-', $email),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'user_role' => $role->value,
            'status' => $status->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
        $user->save();

        return $user;
    }

    private function currency(string $code): Currency
    {
        $currency = Currency::query()->where('code', $code)->first() ?? new Currency;
        $currency->forceFill([
            'name' => 'Dollars',
            'code' => $code,
            'symbol' => '$',
            'paypal_supported' => true,
            'stripe_supported' => true,
        ])->save();

        return $currency;
    }

    private function paymentGateway(): PaymentGateway
    {
        $gateway = PaymentGateway::query()->where('identifier', 'dusk_gateway')->first() ?? new PaymentGateway;
        $gateway->forceFill([
            'identifier' => 'dusk_gateway',
            'currency' => 'EUR',
            'title' => 'Dusk Gateway',
            'description' => 'Gateway used by Dusk admin crud tests',
            'keys' => [
                'public_key' => 'old_pk',
                'secret_key' => 'old_sk',
            ],
            'test_mode' => 1,
            'status' => 1,
            'is_addon' => 0,
        ])->save();

        return $gateway;
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', $this->fixtureEmails)
            ->pluck('id');

        if ($userIds->isNotEmpty()) {
            AccountActiveRequest::query()->whereIn('user_id', $userIds)->delete();
            User::query()->whereIn('id', $userIds)->delete();
        }

        PageCategory::query()->where('name', 'Dusk Page Category')->delete();
        Category::query()->where('name', 'Dusk Page Category')->delete();
        PaymentGateway::query()->where('identifier', 'dusk_gateway')->delete();
    }
}
