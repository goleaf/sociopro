<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\NotificationController;
use App\Models\Event;
use App\Models\Friendships;
use App\Models\Group;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private const METHODS = [
        'notifications',
        'accept_friend_notification',
        'decline_friend_notification',
        'accept_group_notification',
        'decline_group_notification',
        'accept_event_notification',
        'decline_event_notification',
        'mark_as_read',
        'accept_fundraiser_notification',
        'decline_fundraiser_notification',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string}>
     */
    private const ROUTES = [
        'notifications' => ['notifications', ['GET', 'HEAD'], 'all/notification'],
        'accept.friend.request.from.notification' => ['accept_friend_notification', ['GET', 'HEAD'], 'accept/friend/request/notification/{id}'],
        'decline.friend.request.from.notification' => ['decline_friend_notification', ['GET', 'HEAD'], 'decline/friend/request/notification/{id}'],
        'accept.group.request.from.notification' => ['accept_group_notification', ['GET', 'HEAD'], 'accept/group/request/notification/{id}/{group_id}'],
        'decline.group.request.from.notification' => ['decline_group_notification', ['GET', 'HEAD'], 'decline/group/request/notification/{id}/{group_id}'],
        'accept.event.request.from.notification' => ['accept_event_notification', ['GET', 'HEAD'], 'accept/event/request/notification/{id}/{event_id}'],
        'decline.event.request.from.notification' => ['decline_event_notification', ['GET', 'HEAD'], 'decline/event/request/notification/{id}/{event_id}'],
        'mark.as.read.notification' => ['mark_as_read', ['GET', 'HEAD'], 'mark/as/read/notification/{id}'],
        'accept.fundraiser.request.from.notification' => ['accept_fundraiser_notification', ['GET', 'HEAD'], 'accept/fundraiser/request/notification/{id}/{fundraiser_id}'],
        'decline.fundraiser.request.from.notification' => ['decline_fundraiser_notification', ['GET', 'HEAD'], 'decline/fundraiser/request/notification/{id}/{fundraiser_id}'],
    ];

    public function test_requested_notification_controller_methods_stay_public(): void
    {
        $controller = new ReflectionClass(NotificationController::class);

        foreach (self::METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "NotificationController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "NotificationController::{$method} should stay public.");
        }
    }

    public function test_requested_notification_routes_keep_expected_actions_methods_uris_and_middleware(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(NotificationController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");

            foreach (['auth', 'verified', 'activity'] as $middleware) {
                $this->assertContains($middleware, $route->middleware(), "Route [{$routeName}] lost [{$middleware}] middleware.");
            }
        }
    }

    public function test_notifications_page_returns_current_users_new_and_older_notifications(): void
    {
        $receiver = $this->activeUser();
        $otherReceiver = $this->activeUser();
        $sender = $this->activeUser();
        $currentNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'profile',
            'status' => 0,
            'view' => 0,
            'created_at' => now(),
        ]);
        $olderNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'profile',
            'status' => 1,
            'view' => 1,
            'created_at' => now()->subDays(2),
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $otherReceiver->id,
            'type' => 'profile',
            'status' => 0,
            'view' => 0,
            'created_at' => now(),
        ]);

        $response = $this
            ->actingAs($receiver)
            ->get(route('notifications'));

        $response
            ->assertOk()
            ->assertViewIs('frontend.index')
            ->assertViewHas('view_path', 'frontend.notification.notification');

        $newNotifications = $response->viewData('new_notification');
        $olderNotifications = $response->viewData('older_notification');

        $this->assertSame([$currentNotification->id], $newNotifications->pluck('id')->all());
        $this->assertSame([$olderNotification->id], $olderNotifications->pluck('id')->all());
        $this->assertNotContains($otherNotification->id, $newNotifications->pluck('id')->all());
    }

    public function test_mark_as_read_updates_only_current_users_notification(): void
    {
        $receiver = $this->activeUser();
        $otherReceiver = $this->activeUser();
        $sender = $this->activeUser();
        $ownedNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'status' => 0,
            'view' => 0,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $otherReceiver->id,
            'status' => 0,
            'view' => 0,
        ]);

        $this
            ->actingAs($receiver)
            ->get(route('mark.as.read.notification', $ownedNotification->id))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseHas('notifications', [
            'id' => $ownedNotification->id,
            'status' => 1,
            'view' => 1,
        ]);

        $this
            ->actingAs($receiver)
            ->get(route('mark.as.read.notification', $otherNotification->id))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification->id,
            'status' => 0,
            'view' => 0,
        ]);
    }

    public function test_friend_notification_accept_and_decline_preserve_legacy_response_and_scope_current_user(): void
    {
        $requester = $this->activeUser();
        $receiver = $this->activeUser();
        $otherReceiver = $this->activeUser();
        $this->friendship($requester, $receiver);
        $this->friendship($requester, $otherReceiver);
        $this->notification([
            'sender_user_id' => $requester->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'profile',
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $requester->id,
            'reciver_user_id' => $otherReceiver->id,
            'type' => 'profile',
        ]);

        $this
            ->actingAs($receiver)
            ->get(route('accept.friend.request.from.notification', $requester->id))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseHas('friendships', [
            'requester' => $requester->id,
            'accepter' => $receiver->id,
            'is_accepted' => 1,
        ]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);

        $this
            ->actingAs($otherReceiver)
            ->get(route('decline.friend.request.from.notification', $requester->id))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseMissing('friendships', [
            'requester' => $requester->id,
            'accepter' => $otherReceiver->id,
        ]);
        $this->assertDatabaseMissing('notifications', ['id' => $otherNotification->id]);
    }

    public function test_group_notification_accept_and_decline_are_scoped_to_target_group(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $targetGroup = $this->group($sender, 'Target web group');
        $otherGroup = $this->group($sender, 'Other web group');
        $targetInvite = $this->invite([
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $receiver->id,
            'group_id' => $targetGroup->id,
            'is_accepted' => 0,
        ]);
        $otherInvite = $this->invite([
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $receiver->id,
            'group_id' => $otherGroup->id,
            'is_accepted' => 0,
        ]);
        $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'group',
            'group_id' => $targetGroup->id,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'group',
            'group_id' => $otherGroup->id,
        ]);

        $this
            ->actingAs($receiver)
            ->get(route('accept.group.request.from.notification', [$sender->id, $targetGroup->id]))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseHas('invites', ['id' => $targetInvite->id, 'is_accepted' => 1]);
        $this->assertDatabaseHas('invites', ['id' => $otherInvite->id, 'is_accepted' => 0]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id, 'status' => 0, 'view' => 0]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $receiver->id,
            'reciver_user_id' => $sender->id,
            'type' => 'group_invitation_accept',
            'group_id' => $targetGroup->id,
        ]);

        $this
            ->actingAs($receiver)
            ->get(route('decline.group.request.from.notification', [$sender->id, $otherGroup->id]))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseMissing('invites', ['id' => $otherInvite->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $otherNotification->id]);
    }

    public function test_event_notification_accept_and_decline_are_scoped_and_append_receiver_to_going_users(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $targetEvent = $this->event($sender, 'Target web event');
        $otherEvent = $this->event($sender, 'Other web event');
        $targetInvite = $this->invite([
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $receiver->id,
            'event_id' => $targetEvent->id,
            'is_accepted' => 0,
        ]);
        $otherInvite = $this->invite([
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $receiver->id,
            'event_id' => $otherEvent->id,
            'is_accepted' => 0,
        ]);
        $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'event',
            'event_id' => $targetEvent->id,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'event',
            'event_id' => $otherEvent->id,
        ]);

        $this
            ->actingAs($receiver)
            ->get(route('accept.event.request.from.notification', [$sender->id, $targetEvent->id]))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseHas('invites', ['id' => $targetInvite->id, 'is_accepted' => 1]);
        $this->assertDatabaseHas('invites', ['id' => $otherInvite->id, 'is_accepted' => 0]);
        $this->assertSame([$receiver->id], $this->eventGoingUserIds($targetEvent));
        $this->assertSame([], $this->eventGoingUserIds($otherEvent));
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id, 'status' => 0, 'view' => 0]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $receiver->id,
            'reciver_user_id' => $sender->id,
            'type' => 'event_invitation_accept',
            'event_id' => $targetEvent->id,
        ]);

        $this
            ->actingAs($receiver)
            ->get(route('decline.event.request.from.notification', [$sender->id, $otherEvent->id]))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseMissing('invites', ['id' => $otherInvite->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $otherNotification->id]);
    }

    public function test_fundraiser_notification_accept_and_decline_are_scoped_to_target_fundraiser(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $targetFundraiserId = 801;
        $otherFundraiserId = 802;
        $targetInvite = $this->invite([
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $receiver->id,
            'fundraiser_id' => $targetFundraiserId,
            'is_accepted' => 0,
        ]);
        $otherInvite = $this->invite([
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $receiver->id,
            'fundraiser_id' => $otherFundraiserId,
            'is_accepted' => 0,
        ]);
        $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'fundraiser',
            'fundraiser_id' => $targetFundraiserId,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'fundraiser',
            'fundraiser_id' => $otherFundraiserId,
        ]);

        $this
            ->actingAs($receiver)
            ->get(route('accept.fundraiser.request.from.notification', [$sender->id, $targetFundraiserId]))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseHas('invites', ['id' => $targetInvite->id, 'is_accepted' => 1]);
        $this->assertDatabaseHas('invites', ['id' => $otherInvite->id, 'is_accepted' => 0]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id, 'status' => 0, 'view' => 0]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $receiver->id,
            'reciver_user_id' => $sender->id,
            'type' => 'fundraiser_request_accept',
            'fundraiser_id' => $targetFundraiserId,
        ]);

        $this
            ->actingAs($receiver)
            ->get(route('decline.fundraiser.request.from.notification', [$sender->id, $otherFundraiserId]))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseMissing('invites', ['id' => $otherInvite->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $otherNotification->id]);
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function notification(array $attributes): Notification
    {
        $notification = new Notification;
        $notification->forceFill([
            'sender_user_id' => $attributes['sender_user_id'],
            'reciver_user_id' => $attributes['reciver_user_id'],
            'type' => $attributes['type'] ?? 'profile',
            'status' => $attributes['status'] ?? 0,
            'view' => $attributes['view'] ?? 0,
            'created_at' => $attributes['created_at'] ?? now(),
            'updated_at' => $attributes['updated_at'] ?? now(),
            ...$attributes,
        ]);
        $notification->save();

        return $notification;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function invite(array $attributes): Invite
    {
        $invite = new Invite;
        $invite->forceFill($attributes);
        $invite->save();

        return $invite;
    }

    private function friendship(User $requester, User $receiver): Friendships
    {
        return Friendships::query()->create([
            'requester' => $requester->id,
            'accepter' => $receiver->id,
            'is_accepted' => 0,
        ]);
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
}
