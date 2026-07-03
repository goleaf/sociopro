<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Follower;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FollowControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-follow-viewer@example.test',
        'dusk-follow-other@example.test',
        'dusk-follow-target@example.test',
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

    public function test_user_follows_and_unfollows_without_deleting_other_followers_in_browser(): void
    {
        $viewer = $this->activeUser('dusk-follow-viewer@example.test', 'Dusk Follow Viewer');
        $otherFollower = $this->activeUser('dusk-follow-other@example.test', 'Dusk Follow Other');
        $target = $this->activeUser('dusk-follow-target@example.test', 'Dusk Follow Target');

        $this->createFollower($otherFollower, $target);

        $this->browse(function (Browser $browser) use ($target, $viewer) {
            $browser->loginAs($viewer)
                ->visit('/user/account/follow/'.$target->id)
                ->assertSourceHas('reload')
                ->visit('/user/account/follow/'.$target->id)
                ->assertSourceHas('reload')
                ->visit('/user/account/unfollow/'.$target->id)
                ->assertSourceHas('reload');
        });

        $this->assertSame(0, Follower::query()
            ->where('user_id', $viewer->id)
            ->where('follow_id', $target->id)
            ->count());
        $this->assertSame(1, Follower::query()
            ->where('user_id', $otherFollower->id)
            ->where('follow_id', $target->id)
            ->count());
    }

    private function activeUser(string $email, string $name): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2u.heWG/igi',
            'email_verified_at' => now(),
            'username' => str_replace(['@', '.'], '-', $email),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'profile_status' => 'unlock',
        ]);
        $user->save();

        return $user;
    }

    private function createFollower(User $user, User $target): Follower
    {
        $follower = new Follower;
        $follower->user_id = $user->id;
        $follower->follow_id = $target->id;
        $follower->save();

        return $follower;
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        Follower::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('follow_id', $userIds)
            ->delete();
        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
