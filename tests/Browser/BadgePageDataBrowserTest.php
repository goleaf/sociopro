<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BadgePageDataBrowserTest extends DuskTestCase
{
    /**
     * @var list<string>
     */
    private array $fixtureEmails = [
        'dusk-badge-owner@example.test',
        'dusk-badge-expired@example.test',
        'dusk-badge-other@example.test',
    ];

    private mixed $originalBadgePrice = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalBadgePrice = DB::table('settings')->where('type', 'badge_price')->value('description');
        $this->deleteFixtures();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        DB::table('settings')->where('type', 'badge_price')->update([
            'description' => $this->originalBadgePrice,
        ]);

        parent::tearDown();
    }

    public function test_badge_index_renders_history_rows_and_blocks_purchase_when_active_badge_exists(): void
    {
        $user = $this->activeUser('dusk-badge-owner@example.test', 'Dusk Badge Owner');
        $otherUser = $this->activeUser('dusk-badge-other@example.test', 'Dusk Other Badge Owner');
        $today = CarbonImmutable::now(config('app.timezone'));
        $expiredStart = $today->subDays(60)->startOfDay();
        $expiredEnd = $today->subDays(30)->endOfDay();
        $activeStart = $today->subDay()->startOfDay();
        $activeEnd = $today->addDays(30)->endOfDay();

        $this->badgeFor($user, 'Expired Dusk Badge', 1, $expiredStart, $expiredEnd);
        $this->badgeFor($user, 'Inactive Overlapping Dusk Badge', 0, $activeStart, $activeEnd);
        $this->badgeFor($user, 'Active Dusk Badge', 1, $activeStart, $activeEnd);
        $this->badgeFor($otherUser, 'Other User Dusk Badge', 1, $activeStart, $activeEnd);

        $this->browse(function (Browser $browser) use ($user, $activeStart, $activeEnd, $expiredStart, $expiredEnd) {
            $browser->loginAs($user)
                ->visit('/badge')
                ->assertSee('Build trust with Sociopro Verified')
                ->assertSee('Already purchased')
                ->assertDontSee('Next')
                ->assertSee('Badge Purchased History')
                ->assertSee('Dusk Badge Owner')
                ->assertSee($activeStart->format('d M Y'))
                ->assertSee($activeEnd->format('d M Y'))
                ->assertSee($expiredStart->format('d M Y'))
                ->assertSee($expiredEnd->format('d M Y'))
                ->assertSee('Active')
                ->assertSee('Expires')
                ->assertDontSee('Dusk Other Badge Owner');
        });
    }

    public function test_badge_index_keeps_next_available_when_only_expired_badges_exist(): void
    {
        $user = $this->activeUser('dusk-badge-expired@example.test', 'Dusk Expired Badge Owner');
        $today = CarbonImmutable::now(config('app.timezone'));
        $expiredStart = $today->subDays(60)->startOfDay();
        $expiredEnd = $today->subDays(30)->endOfDay();

        $this->badgeFor($user, 'Expired Only Dusk Badge', 1, $expiredStart, $expiredEnd);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/badge')
                ->assertSee('Build trust with Sociopro Verified')
                ->assertSee('Next')
                ->assertDontSee('Already purchased')
                ->assertSee('Badge Purchased History')
                ->assertSee('Dusk Expired Badge Owner')
                ->assertSee('Expires');
        });
    }

    public function test_badge_confirmation_renders_checkout_price_and_payment_form(): void
    {
        DB::table('settings')->where('type', 'badge_price')->update(['description' => '19']);
        $user = $this->activeUser('dusk-badge-owner@example.test', 'Dusk Badge Owner');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/badge/info')
                ->assertSee('Confirm and pay')
                ->assertSee('Dusk Badge Owner')
                ->assertSee('19$')
                ->assertSee('What you get with your subscription.')
                ->assertSee('A verified badge')
                ->assertSee('Increased account protection')
                ->assertSee('Pay Now');
        });
    }

    private function activeUser(string $email, string $name): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => str_replace(['@', '.'], '-', $email),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
        $user->save();

        return $user;
    }

    private function badgeFor(
        User $user,
        string $title,
        int $status,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate
    ): Badge {
        $badge = new Badge;
        $badge->forceFill([
            'user_id' => $user->id,
            'title' => $title,
            'description' => 'Dusk badge fixture',
            'icon' => 'fa-circle-check',
            'status' => $status,
            'start_date' => $startDate->toDateTimeString(),
            'end_date' => $endDate->toDateTimeString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $badge->save();

        return $badge;
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', $this->fixtureEmails)
            ->pluck('id');

        if ($userIds->isNotEmpty()) {
            Badge::query()->whereIn('user_id', $userIds)->delete();
        }

        User::query()->whereIn('email', $this->fixtureEmails)->delete();
    }
}
