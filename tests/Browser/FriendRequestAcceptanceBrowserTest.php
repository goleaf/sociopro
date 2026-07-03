<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Follower;
use App\Models\Friendships;
use App\Models\Notification;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FriendRequestAcceptanceBrowserTest extends DuskTestCase
{
    /**
     * @var list<string>
     */
    private array $fixtureEmails = [
        'dusk-profile-requester@example.test',
        'dusk-profile-accepter@example.test',
        'dusk-notification-requester@example.test',
        'dusk-notification-accepter@example.test',
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

    public function test_user_accepts_friend_request_from_profile_friends_page(): void
    {
        $requester = $this->activeUser('dusk-profile-requester@example.test', 'Dusk Profile Requester');
        $accepter = $this->activeUser('dusk-profile-accepter@example.test', 'Dusk Profile Accepter');
        $this->pendingFriendRequest($requester, $accepter);

        $this->browse(function (Browser $browser) use ($requester, $accepter) {
            $browser->loginAs($accepter)
                ->visit('/profile/friends')
                ->assertSee('Friends')
                ->assertSee('Friend Requests')
                ->click('#profile-tab')
                ->pause(500)
                ->assertSee('Dusk Profile Requester')
                ->click('#friendRequestConfirmBtn'.$requester->id)
                ->pause(1200);
        });

        $this->assertFriendAccepted($requester, $accepter);
        $this->assertSame(1, Follower::query()
            ->where('user_id', $accepter->id)
            ->where('follow_id', $requester->id)
            ->count());
    }

    public function test_user_accepts_friend_request_from_notifications_page(): void
    {
        $requester = $this->activeUser('dusk-notification-requester@example.test', 'Dusk Notification Requester');
        $accepter = $this->activeUser('dusk-notification-accepter@example.test', 'Dusk Notification Accepter');
        $this->pendingFriendRequest($requester, $accepter);

        $this->browse(function (Browser $browser) use ($accepter) {
            $browser->loginAs($accepter)
                ->visit('/all/notification')
                ->assertSee('Notifications')
                ->assertSee('Dusk Notification Requester')
                ->clickLink('Accept')
                ->pause(1500);
        });

        $this->assertFriendAccepted($requester, $accepter);
        $this->assertSame(0, Follower::query()
            ->where('user_id', $accepter->id)
            ->where('follow_id', $requester->id)
            ->count());
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
            'friends' => json_encode([]),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
        $user->save();

        return $user;
    }

    private function pendingFriendRequest(User $requester, User $accepter): void
    {
        Friendships::query()->create([
            'requester' => $requester->id,
            'accepter' => $accepter->id,
            'is_accepted' => 0,
        ]);

        $notification = new Notification;
        $notification->sender_user_id = $requester->id;
        $notification->reciver_user_id = $accepter->id;
        $notification->type = 'profile';
        $notification->status = 0;
        $notification->view = 0;
        $notification->save();
    }

    private function assertFriendAccepted(User $requester, User $accepter): void
    {
        $this->assertSame(1, Friendships::query()
            ->where('requester', $requester->id)
            ->where('accepter', $accepter->id)
            ->where('is_accepted', 1)
            ->count());
        $this->assertSame(1, Notification::query()
            ->where('sender_user_id', $requester->id)
            ->where('reciver_user_id', $accepter->id)
            ->where('type', 'profile')
            ->where('status', 1)
            ->where('view', 1)
            ->count());
        $this->assertSame(1, Notification::query()
            ->where('sender_user_id', $accepter->id)
            ->where('reciver_user_id', $requester->id)
            ->where('type', 'friend_request_accept')
            ->count());
        $this->assertSame([$accepter->id], $this->friendIds($requester));
        $this->assertSame([$requester->id], $this->friendIds($accepter));
    }

    /**
     * @return list<int>
     */
    private function friendIds(User $user): array
    {
        $friends = json_decode((string) $user->refresh()->friends, true);

        return is_array($friends) ? array_values($friends) : [];
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', $this->fixtureEmails)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        Notification::query()
            ->whereIn('sender_user_id', $userIds)
            ->orWhereIn('reciver_user_id', $userIds)
            ->delete();
        Friendships::query()
            ->whereIn('requester', $userIds)
            ->orWhereIn('accepter', $userIds)
            ->delete();
        Follower::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('follow_id', $userIds)
            ->delete();
        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
