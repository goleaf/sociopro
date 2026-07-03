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

class FriendRequestSendingBrowserTest extends DuskTestCase
{
    /**
     * @var list<string>
     */
    private array $fixtureEmails = [
        'dusk-send-requester@example.test',
        'dusk-send-accepter@example.test',
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

    public function test_user_sends_friend_request_from_profile_button(): void
    {
        $requester = $this->activeUser('dusk-send-requester@example.test', 'Dusk Send Requester');
        $accepter = $this->activeUser('dusk-send-accepter@example.test', 'Dusk Send Accepter');

        $this->browse(function (Browser $browser) use ($accepter, $requester) {
            $browser->loginAs($requester)
                ->visit('/user/view/profile/'.$accepter->id)
                ->assertSee('Dusk Send Accepter')
                ->assertSee('Add Friend')
                ->clickLink('Add Friend')
                ->waitForText('Requested', 5);
        });

        $this->assertSame(1, Friendships::query()
            ->where('requester', $requester->id)
            ->where('accepter', $accepter->id)
            ->where('is_accepted', 0)
            ->count());
        $this->assertSame(1, Notification::query()
            ->where('sender_user_id', $requester->id)
            ->where('reciver_user_id', $accepter->id)
            ->where('type', 'profile')
            ->count());
        $this->assertSame(1, Follower::query()
            ->where('user_id', $requester->id)
            ->where('follow_id', $accepter->id)
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
