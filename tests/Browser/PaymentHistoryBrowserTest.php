<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\PaymentHistoryEntry;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PaymentHistoryBrowserTest extends DuskTestCase
{
    private const EMAILS = [
        'dusk-payment-history-admin@example.test',
        'dusk-payment-history-owner@example.test',
        'dusk-payment-history-other@example.test',
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

    public function test_payment_history_index_renders_admin_and_user_scoped_rows_in_browser(): void
    {
        $admin = $this->activeUser('dusk-payment-history-admin@example.test', UserRole::Admin);
        $owner = $this->activeUser('dusk-payment-history-owner@example.test', UserRole::General);
        $otherUser = $this->activeUser('dusk-payment-history-other@example.test', UserRole::General);

        PaymentHistoryEntry::factory()->create([
            'user_id' => $owner->id,
            'item_type' => 'dusk-payment-history',
            'amount' => '13.25',
            'currency' => 'USD',
        ]);
        PaymentHistoryEntry::factory()->create([
            'user_id' => $otherUser->id,
            'item_type' => 'dusk-payment-history',
            'amount' => '88.88',
            'currency' => 'EUR',
        ]);

        $this->browse(function (Browser $browser) use ($admin, $owner) {
            $browser->loginAs($owner)
                ->visit('/user/payment-histories')
                ->assertPathIs('/user/payment-histories')
                ->assertSee('Payment histories')
                ->assertSee('13.25 USD')
                ->assertDontSee('88.88 EUR')
                ->loginAs($admin)
                ->visit('/admin/payment-histories')
                ->assertPathIs('/admin/payment-histories')
                ->assertSee('Payment histories')
                ->assertSee('13.25 USD')
                ->assertSee('88.88 EUR');
        });
    }

    private function activeUser(string $email, UserRole $role): User
    {
        return User::factory()->create([
            'name' => $role === UserRole::Admin ? 'Dusk Payment History Admin' : 'Dusk Payment History User',
            'email' => $email,
            'email_verified_at' => now(),
            'username' => str($email)->before('@')->replace('.', '-')->toString(),
            'phone' => '1555'.random_int(100000, 999999),
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ]);
    }

    private function deleteFixtures(): void
    {
        PaymentHistoryEntry::query()
            ->where('item_type', 'dusk-payment-history')
            ->delete();

        User::query()
            ->whereIn('email', self::EMAILS)
            ->delete();
    }
}
