<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\AccountActiveRequest;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AccountStatusBrowserTest extends DuskTestCase
{
    /**
     * @var list<string>
     */
    private array $fixtureEmails = [
        'dusk-disabled-account@example.test',
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

    public function test_disabled_user_requests_account_activation_from_disabled_page(): void
    {
        $user = $this->disabledUser('dusk-disabled-account@example.test', 'Dusk Disabled Account');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visitRoute('frontend.disable_view')
                ->assertSee('Your account has been Deactivate')
                ->assertSee('Request Account Activation')
                ->press('Request Account Activation')
                ->waitForText('Account Active Request Pending', 5);
        });

        $this->assertDatabaseHas('account_active_requests', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    private function disabledUser(string $email, string $name): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => str_replace(['@', '.'], '-', $email),
            'friends' => json_encode([]),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Disabled->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
        $user->save();

        return $user;
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', $this->fixtureEmails)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        AccountActiveRequest::query()
            ->whereIn('user_id', $userIds)
            ->delete();
        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
