<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Friendships;
use App\Models\Group;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-notify-receiver@example.test',
        'dusk-notify-sender@example.test',
        'dusk-notify-accept-friend@example.test',
        'dusk-notify-decline-friend@example.test',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFixtures();
        $this->seedRuntimeSettings();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        parent::tearDown();
    }

    public function test_notification_controller_routes_work_through_browser_and_fetch(): void
    {
        $receiver = $this->activeUser('dusk-notify-receiver@example.test', 'Dusk Notify Receiver');
        $sender = $this->activeUser('dusk-notify-sender@example.test', 'Dusk Notify Sender');
        $acceptFriendSender = $this->activeUser('dusk-notify-accept-friend@example.test', 'Dusk Notify Accept Friend');
        $declineFriendSender = $this->activeUser('dusk-notify-decline-friend@example.test', 'Dusk Notify Decline Friend');

        $markNotification = $this->notification($sender, $receiver, ['type' => 'friend_request_accept']);
        $this->friendship($acceptFriendSender, $receiver);
        $this->notification($acceptFriendSender, $receiver, ['type' => 'profile']);
        $this->friendship($declineFriendSender, $receiver);
        $declineFriendNotification = $this->notification($declineFriendSender, $receiver, ['type' => 'profile']);

        $this->browse(function (Browser $browser) use (
            $acceptFriendSender,
            $declineFriendNotification,
            $declineFriendSender,
            $markNotification,
            $receiver,
            $sender
        ) {
            $browser->loginAs($receiver)
                ->visit('/all/notification')
                ->assertSee('Notifications')
                ->assertSee('Dusk Notify Sender');

            $this->assertFetchResponseContains(
                $browser,
                route('mark.as.read.notification', $markNotification->id, false),
                'notifyMarkReadResponse',
                'reload'
            );
            $this->assertDatabaseHas('notifications', [
                'id' => $markNotification->id,
                'status' => 1,
                'view' => 1,
            ]);

            $this->assertFetchResponseContains(
                $browser,
                route('accept.friend.request.from.notification', $acceptFriendSender->id, false),
                'notifyAcceptFriendResponse',
                'reload'
            );
            $this->assertDatabaseHas('friendships', [
                'requester' => $acceptFriendSender->id,
                'accepter' => $receiver->id,
                'is_accepted' => 1,
            ]);

            $this->assertFetchResponseContains(
                $browser,
                route('decline.friend.request.from.notification', $declineFriendSender->id, false),
                'notifyDeclineFriendResponse',
                'reload'
            );
            $this->assertDatabaseMissing('friendships', [
                'requester' => $declineFriendSender->id,
                'accepter' => $receiver->id,
            ]);
            $this->assertDatabaseMissing('notifications', ['id' => $declineFriendNotification->id]);

            $targetGroup = $this->group($sender, 'Dusk Notify Target Group');
            $declineGroup = $this->group($sender, 'Dusk Notify Decline Group');
            $this->invite($sender, $receiver, ['group_id' => $targetGroup->id]);
            $declineGroupInvite = $this->invite($sender, $receiver, ['group_id' => $declineGroup->id]);
            $this->notification($sender, $receiver, ['type' => 'group', 'group_id' => $targetGroup->id]);
            $declineGroupNotification = $this->notification($sender, $receiver, ['type' => 'group', 'group_id' => $declineGroup->id]);

            $this->assertFetchResponseContains(
                $browser,
                route('accept.group.request.from.notification', [$sender->id, $targetGroup->id], false),
                'notifyAcceptGroupResponse',
                'reload'
            );
            $this->assertDatabaseHas('invites', [
                'invite_sender_id' => $sender->id,
                'invite_reciver_id' => $receiver->id,
                'group_id' => $targetGroup->id,
                'is_accepted' => 1,
            ]);

            $this->assertFetchResponseContains(
                $browser,
                route('decline.group.request.from.notification', [$sender->id, $declineGroup->id], false),
                'notifyDeclineGroupResponse',
                'reload'
            );
            $this->assertDatabaseMissing('invites', ['id' => $declineGroupInvite->id]);
            $this->assertDatabaseMissing('notifications', ['id' => $declineGroupNotification->id]);

            $targetEvent = $this->event($sender, 'Dusk Notify Target Event');
            $declineEvent = $this->event($sender, 'Dusk Notify Decline Event');
            $this->invite($sender, $receiver, ['event_id' => $targetEvent->id]);
            $declineEventInvite = $this->invite($sender, $receiver, ['event_id' => $declineEvent->id]);
            $this->notification($sender, $receiver, ['type' => 'event', 'event_id' => $targetEvent->id]);
            $declineEventNotification = $this->notification($sender, $receiver, ['type' => 'event', 'event_id' => $declineEvent->id]);

            $this->assertFetchResponseContains(
                $browser,
                route('accept.event.request.from.notification', [$sender->id, $targetEvent->id], false),
                'notifyAcceptEventResponse',
                'reload'
            );
            $this->assertSame([$receiver->id], $this->eventGoingUserIds($targetEvent));

            $this->assertFetchResponseContains(
                $browser,
                route('decline.event.request.from.notification', [$sender->id, $declineEvent->id], false),
                'notifyDeclineEventResponse',
                'reload'
            );
            $this->assertDatabaseMissing('invites', ['id' => $declineEventInvite->id]);
            $this->assertDatabaseMissing('notifications', ['id' => $declineEventNotification->id]);

            $targetFundraiserId = 901;
            $declineFundraiserId = 902;
            $this->invite($sender, $receiver, ['fundraiser_id' => $targetFundraiserId]);
            $declineFundraiserInvite = $this->invite($sender, $receiver, ['fundraiser_id' => $declineFundraiserId]);
            $this->notification($sender, $receiver, ['type' => 'fundraiser', 'fundraiser_id' => $targetFundraiserId]);
            $declineFundraiserNotification = $this->notification($sender, $receiver, ['type' => 'fundraiser', 'fundraiser_id' => $declineFundraiserId]);

            $this->assertFetchResponseContains(
                $browser,
                route('accept.fundraiser.request.from.notification', [$sender->id, $targetFundraiserId], false),
                'notifyAcceptFundraiserResponse',
                'reload'
            );
            $this->assertDatabaseHas('invites', [
                'invite_sender_id' => $sender->id,
                'invite_reciver_id' => $receiver->id,
                'fundraiser_id' => $targetFundraiserId,
                'is_accepted' => 1,
            ]);

            $this->assertFetchResponseContains(
                $browser,
                route('decline.fundraiser.request.from.notification', [$sender->id, $declineFundraiserId], false),
                'notifyDeclineFundraiserResponse',
                'reload'
            );
            $this->assertDatabaseMissing('invites', ['id' => $declineFundraiserInvite->id]);
            $this->assertDatabaseMissing('notifications', ['id' => $declineFundraiserNotification->id]);
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
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'payment_settings' => '',
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'profile_status' => 'unlock',
        ]);
        $user->save();

        return $user;
    }

    private function friendship(User $requester, User $receiver): Friendships
    {
        return Friendships::query()->create([
            'requester' => $requester->id,
            'accepter' => $receiver->id,
            'is_accepted' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function invite(User $sender, User $receiver, array $overrides = []): Invite
    {
        $invite = new Invite;
        $invite->forceFill([
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $receiver->id,
            'is_accepted' => 0,
            ...$overrides,
        ]);
        $invite->save();

        return $invite;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function notification(User $sender, User $receiver, array $overrides = []): Notification
    {
        $notification = new Notification;
        $notification->forceFill([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'profile',
            'status' => 0,
            'view' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);
        $notification->save();

        return $notification;
    }

    private function group(User $owner, string $title): Group
    {
        $group = new Group;
        $group->forceFill([
            'user_id' => $owner->id,
            'title' => $title,
            'privacy' => 'public',
            'status' => '1',
        ]);
        $group->save();

        return $group;
    }

    private function event(User $owner, string $title): Event
    {
        $event = new Event;
        $event->forceFill([
            'user_id' => $owner->id,
            'publisher_id' => $owner->id,
            'publisher' => 'user',
            'title' => $title,
            'going_users_id' => json_encode([]),
            'interested_users_id' => json_encode([]),
            'privacy' => 'public',
        ]);
        $event->save();

        return $event;
    }

    private function assertFetchResponseContains(Browser $browser, string $url, string $windowKey, string $expectedText): void
    {
        $this->assertFetchOk($browser, $url, $windowKey);

        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
    }

    private function assertFetchOk(Browser $browser, string $url, string $windowKey): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;

            fetch({$encodedUrl}, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                },
            }).then(async (response) => {
                window[{$encodedWindowKey}] = {
                    status: response.status,
                    text: await response.text(),
                };
            }).catch((error) => {
                window[{$encodedWindowKey}] = {
                    status: -1,
                    text: String(error),
                };
            });
        JS);

        $browser->waitUntil("window[{$encodedWindowKey}] !== null && window[{$encodedWindowKey}].status === 200", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('SQLSTATE[')", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('no such table')", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('Internal Server Error')", 5);
    }

    /**
     * @return list<int>
     */
    private function eventGoingUserIds(Event $event): array
    {
        $goingUsers = json_decode((string) $event->refresh()->going_users_id, true);

        if (! is_array($goingUsers)) {
            return [];
        }

        return array_map('intval', array_values($goingUsers));
    }

    private function seedRuntimeSettings(): void
    {
        $this->upsertSetting('system_language', 'english');
        $this->upsertSetting('system_name', 'Dusk Sociopro');
        $this->upsertSetting('system_fav_icon', '');
        $this->upsertSetting('theme_color', 'default');
    }

    private function upsertSetting(string $type, string $description): void
    {
        $setting = Setting::query()->where('type', $type)->first() ?? new Setting;
        $setting->forceFill([
            'type' => $type,
            'description' => $description,
            'updated_at' => now(),
        ]);

        if (! $setting->exists) {
            $setting->created_at = now();
        }

        $setting->save();
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        Notification::query()
            ->whereIn('sender_user_id', $userIds)
            ->orWhereIn('reciver_user_id', $userIds)
            ->delete();
        Invite::query()
            ->whereIn('invite_sender_id', $userIds)
            ->orWhereIn('invite_reciver_id', $userIds)
            ->delete();
        Friendships::query()
            ->whereIn('requester', $userIds)
            ->orWhereIn('accepter', $userIds)
            ->delete();
        Event::query()
            ->where('title', 'like', 'Dusk Notify %')
            ->delete();
        Group::query()
            ->where('title', 'like', 'Dusk Notify %')
            ->delete();
        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
