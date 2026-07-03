<?php

namespace Tests\Feature;

use App\Actions\Badges\BuildBadgePageDataAction;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuildBadgePageDataActionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_index_builds_current_user_badge_history_rows_newest_first(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-02 12:00:00', config('app.timezone')));
        $user = $this->activeUser(['name' => 'Badge Owner']);
        $otherUser = $this->activeUser(['name' => 'Other Badge Owner']);

        $expiredBadge = $this->badgeFor($user, [
            'title' => 'Expired badge',
            'status' => 1,
            'start_date' => '2026-05-01 00:00:00',
            'end_date' => '2026-05-31 23:59:59',
        ]);
        $activeBadge = $this->badgeFor($user, [
            'title' => 'Active badge',
            'status' => 1,
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-31 23:59:59',
        ]);
        $this->badgeFor($otherUser, [
            'title' => 'Other user badge',
            'status' => 1,
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-31 23:59:59',
        ]);

        $data = app(BuildBadgePageDataAction::class)->index($user);

        $this->assertSame('frontend.badge.badge', $data['view_path']);
        $this->assertSame($user->id, $data['badgeUser']->id);
        $this->assertTrue($data['hasActiveBadge']);
        $this->assertSame([$activeBadge->id, $expiredBadge->id], $data['badges']->pluck('id')->all());
        $this->assertSame([
            [
                'number' => 1,
                'user_name' => 'Badge Owner',
                'start_date' => '01 Jul 2026',
                'end_date' => '31 Jul 2026',
                'is_active' => true,
            ],
            [
                'number' => 2,
                'user_name' => 'Badge Owner',
                'start_date' => '01 May 2026',
                'end_date' => '31 May 2026',
                'is_active' => false,
            ],
        ], $data['badgeHistoryRows']->all());
    }

    public function test_index_reports_active_when_any_status_one_badge_covers_today(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-02 12:00:00', config('app.timezone')));
        $user = $this->activeUser();

        $this->badgeFor($user, [
            'title' => 'Inactive overlapping badge',
            'status' => 0,
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-31 23:59:59',
        ]);
        $this->badgeFor($user, [
            'title' => 'Active overlapping badge',
            'status' => 1,
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-31 23:59:59',
        ]);

        $data = app(BuildBadgePageDataAction::class)->index($user);

        $this->assertTrue($data['hasActiveBadge']);
    }

    public function test_index_keeps_purchase_available_without_a_status_one_badge_covering_today(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-02 12:00:00', config('app.timezone')));
        $user = $this->activeUser();

        $this->badgeFor($user, [
            'title' => 'Inactive current badge',
            'status' => 0,
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-31 23:59:59',
        ]);
        $this->badgeFor($user, [
            'title' => 'Expired active badge',
            'status' => 1,
            'start_date' => '2026-05-01 00:00:00',
            'end_date' => '2026-05-31 23:59:59',
        ]);

        $data = app(BuildBadgePageDataAction::class)->index($user);

        $this->assertFalse($data['hasActiveBadge']);
    }

    public function test_confirmation_builds_badge_checkout_data(): void
    {
        DB::table('settings')->where('type', 'badge_price')->update(['description' => '19']);
        $user = $this->activeUser();

        $data = app(BuildBadgePageDataAction::class)->confirmation($user);

        $this->assertSame('19', $data['badgePrice']);
        $this->assertSame($user->id, $data['badgeUser']->id);
        $this->assertSame('frontend.badge.badge_info', $data['view_path']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function badgeFor(User $user, array $overrides = []): Badge
    {
        $badge = new Badge;
        $badge->forceFill(array_merge([
            'user_id' => $user->id,
            'title' => 'Badge fixture',
            'description' => 'Badge test fixture',
            'icon' => 'fa-circle-check',
            'status' => 1,
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-31 23:59:59',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
        $badge->save();

        return $badge;
    }
}
