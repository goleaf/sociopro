<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Friendships;
use App\Models\Group;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $apiToken;

    public function test_notification_api_methods_are_split_out_of_god_api_controller(): void
    {
        $apiController = file_get_contents(app_path('Http/Controllers/ApiController.php'));
        $apiRoutes = file_get_contents(base_path('routes/api.php'));

        $this->assertStringNotContainsString('public function notifications(', $apiController);
        $this->assertStringNotContainsString('public function mark_as_read(', $apiController);
        $this->assertStringContainsString('ApiNotificationController::class', $apiRoutes);
    }

    public function test_notifications_endpoint_uses_api_resources_for_output(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Api/NotificationController.php')) ?: '';

        $this->assertFileExists(app_path('Http/Resources/Api/NotificationResource.php'));
        $this->assertFileExists(app_path('Http/Resources/Api/NotificationCollection.php'));
        $this->assertStringContainsString('NotificationCollection', $controller);
        $this->assertStringNotContainsString('notificationRows(', $controller);
    }

    public function test_notifications_endpoint_rejects_missing_bearer_token(): void
    {
        $this->getJson(route('api.notifications.index'))
            ->assertUnauthorized()
            ->assertJson($this->legacyAuthenticationPayload());
    }

    public function test_notification_action_routes_reject_missing_bearer_token(): void
    {
        foreach ([
            ['postJson', route('api.notifications.friends.accept', 1)],
            ['postJson', route('api.notifications.friends.decline', 1)],
            ['postJson', route('api.notifications.groups.accept', [1, 10])],
            ['postJson', route('api.notifications.groups.decline', [1, 10])],
            ['postJson', route('api.notifications.events.accept', [1, 20])],
            ['postJson', route('api.notifications.events.decline', [1, 20])],
            ['postJson', route('api.notifications.read', 30)],
            ['postJson', route('api.notifications.fundraisers.accept', [1, 40])],
            ['postJson', route('api.notifications.fundraisers.decline', [1, 40])],
        ] as [$method, $url]) {
            $this->{$method}($url)
                ->assertUnauthorized()
                ->assertJson($this->legacyAuthenticationPayload());
        }
    }

    public function test_notifications_endpoint_returns_current_and_older_notifications(): void
    {
        $receiver = User::factory()->create();
        $sender = User::factory()->create(['name' => 'Sender User']);

        $currentNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'friend_request',
            'status' => 0,
            'view' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $olderNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'group_invitation',
            'status' => 1,
            'view' => 1,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->getJson(route('api.notifications.index'))
            ->assertOk()
            ->assertJsonPath('new_notifications.0.id', $currentNotification->id)
            ->assertJsonPath('new_notifications.0.sender_user_id', $sender->id)
            ->assertJsonPath('new_notifications.0.reciver_user_id', $receiver->id)
            ->assertJsonPath('new_notifications.0.name', 'Sender User')
            ->assertJsonPath('new_notifications.0.type', 'friend_request')
            ->assertJsonPath('new_notifications.0.status', 0)
            ->assertJsonPath('new_notifications.0.view', 0)
            ->assertJsonPath('older_notifications.0.id', $olderNotification->id)
            ->assertJsonPath('older_notifications.0.type', 'group_invitation')
            ->assertJsonPath('older_notifications.0.status', 1)
            ->assertJsonPath('older_notifications.0.view', 1);
    }

    public function test_notifications_endpoint_preserves_contract_and_hides_sensitive_sender_fields(): void
    {
        $receiver = User::factory()->create();
        $sender = User::factory()->create([
            'name' => 'Sensitive Sender',
            'email' => 'sensitive-sender@example.com',
            'password' => 'hashed-password-value',
            'remember_token' => 'remember-token-value',
        ]);

        $notification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'friend_request',
            'status' => 0,
            'view' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->authenticateApiUser($receiver);

        $payload = $this->withToken($this->apiToken)
            ->getJson(route('api.notifications.index'))
            ->assertOk()
            ->json();

        $this->assertSame([
            'id',
            'sender_user_id',
            'reciver_user_id',
            'name',
            'photo',
            'type',
            'event_id',
            'event_name',
            'page_id',
            'pageName',
            'group_id',
            'groupName',
            'status',
            'view',
            'created_at',
        ], array_keys($payload['new_notifications'][0]));

        $this->assertSame($notification->id, $payload['new_notifications'][0]['id']);
        $this->assertSame('Sensitive Sender', $payload['new_notifications'][0]['name']);
        $this->assertArrayNotHasKey('email', $payload['new_notifications'][0]);
        $this->assertArrayNotHasKey('password', $payload['new_notifications'][0]);
        $this->assertArrayNotHasKey('remember_token', $payload['new_notifications'][0]);
        $this->assertArrayNotHasKey('user_name', $payload['new_notifications'][0]);
    }

    public function test_notifications_endpoint_bounds_current_and_older_lists(): void
    {
        $receiver = User::factory()->create();
        $sender = User::factory()->create(['name' => 'Sender User']);

        for ($index = 1; $index <= 31; $index++) {
            $this->notification([
                'sender_user_id' => $sender->id,
                'reciver_user_id' => $receiver->id,
                'type' => 'friend_request',
                'status' => 0,
                'view' => 0,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
            $this->notification([
                'sender_user_id' => $sender->id,
                'reciver_user_id' => $receiver->id,
                'type' => 'group_invitation',
                'status' => 1,
                'view' => 1,
                'created_at' => now()->subDays(2)->subMinutes($index),
                'updated_at' => now()->subDays(2)->subMinutes($index),
            ]);
        }

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->getJson(route('api.notifications.index'))
            ->assertOk()
            ->assertJsonCount(25, 'new_notifications')
            ->assertJsonCount(25, 'older_notifications');
    }

    public function test_notifications_endpoint_clamps_custom_per_page_values(): void
    {
        $receiver = User::factory()->create();
        $sender = User::factory()->create(['name' => 'Sender User']);

        for ($index = 1; $index <= 60; $index++) {
            $this->notification([
                'sender_user_id' => $sender->id,
                'reciver_user_id' => $receiver->id,
                'type' => 'friend_request',
                'status' => 0,
                'view' => 0,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
            $this->notification([
                'sender_user_id' => $sender->id,
                'reciver_user_id' => $receiver->id,
                'type' => 'group_invitation',
                'status' => 1,
                'view' => 1,
                'created_at' => now()->subDays(2)->subMinutes($index),
                'updated_at' => now()->subDays(2)->subMinutes($index),
            ]);
        }

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->getJson(route('api.notifications.index', ['per_page' => 100]))
            ->assertOk()
            ->assertJsonCount(50, 'new_notifications')
            ->assertJsonCount(50, 'older_notifications');

        $this->withToken($this->apiToken)
            ->getJson(route('api.notifications.index', ['per_page' => 0]))
            ->assertOk()
            ->assertJsonCount(25, 'new_notifications')
            ->assertJsonCount(25, 'older_notifications');
    }

    public function test_notifications_endpoint_accepts_independent_page_parameters_for_each_list(): void
    {
        $receiver = User::factory()->create();
        $sender = User::factory()->create(['name' => 'Sender User']);

        for ($index = 1; $index <= 26; $index++) {
            $this->notification([
                'sender_user_id' => $sender->id,
                'reciver_user_id' => $receiver->id,
                'type' => 'friend_request',
                'status' => 0,
                'view' => 0,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
            $this->notification([
                'sender_user_id' => $sender->id,
                'reciver_user_id' => $receiver->id,
                'type' => 'group_invitation',
                'status' => 1,
                'view' => 1,
                'created_at' => now()->subDays(2)->subMinutes($index),
                'updated_at' => now()->subDays(2)->subMinutes($index),
            ]);
        }

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->getJson(route('api.notifications.index', [
                'new_page' => 2,
                'older_page' => 2,
                'per_page' => 25,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'new_notifications')
            ->assertJsonCount(1, 'older_notifications');
    }

    public function test_mark_as_read_updates_notification_status_and_view(): void
    {
        $receiver = User::factory()->create();
        $sender = User::factory()->create();
        $notification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'friend_request',
            'status' => 0,
            'view' => 0,
        ]);

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.read', $notification))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'mark as read',
            ]);

        $notification->refresh();

        $this->assertSame(1, (int) $notification->status);
        $this->assertSame(1, (int) $notification->view);
    }

    public function test_mark_as_read_returns_not_found_error_for_missing_notification(): void
    {
        $receiver = User::factory()->create();

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.read', 999999))
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'not found',
                'error' => [
                    'code' => 'NOT_FOUND',
                    'category' => 'not_found',
                    'message' => 'not found',
                    'http_status' => 404,
                    'details' => [],
                ],
            ]);
    }

    public function test_mark_as_read_denies_notifications_owned_by_another_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $sender = User::factory()->create();

        $notification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $owner->id,
            'type' => 'friend_request',
            'status' => 0,
            'view' => 0,
        ]);

        $this->authenticateApiUser($otherUser);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.read', $notification))
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden',
                'error' => [
                    'code' => 'AUTHORIZATION_ERROR',
                    'category' => 'authorization',
                    'message' => 'Forbidden',
                    'http_status' => 403,
                    'details' => [],
                ],
            ]);

        $notification->refresh();

        $this->assertSame(0, (int) $notification->status);
        $this->assertSame(0, (int) $notification->view);
    }

    public function test_decline_friend_notification_removes_friendship_and_inbound_notification(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        Friendships::create([
            'requester' => $requester->id,
            'accepter' => $receiver->id,
            'is_accepted' => 0,
        ]);
        $this->notification([
            'sender_user_id' => $requester->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'profile',
            'status' => 0,
            'view' => 0,
        ]);

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.friends.decline', $requester->id))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'successfully decline',
            ]);

        $this->assertDatabaseMissing('friendships', [
            'requester' => $requester->id,
            'accepter' => $receiver->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'sender_user_id' => $requester->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'profile',
        ]);
    }

    public function test_group_notification_accepts_only_the_target_invitation(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $targetGroup = $this->group($sender, 'Target group');
        $otherGroup = $this->group($sender, 'Other group');

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
            'type' => 'group_invitation',
            'group_id' => $targetGroup->id,
            'status' => 0,
            'view' => 0,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'group_invitation',
            'group_id' => $otherGroup->id,
            'status' => 0,
            'view' => 0,
        ]);

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.groups.accept', [$sender->id, $targetGroup->id]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Group request accept',
            ]);

        $this->assertDatabaseHas('invites', ['id' => $targetInvite->id, 'is_accepted' => 1]);
        $this->assertDatabaseHas('invites', ['id' => $otherInvite->id, 'is_accepted' => 0]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'group_id' => $targetGroup->id,
            'status' => 1,
            'view' => 1,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification->id,
            'status' => 0,
            'view' => 0,
        ]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $receiver->id,
            'reciver_user_id' => $sender->id,
            'type' => 'group_invitation_accept',
            'group_id' => $targetGroup->id,
        ]);
    }

    public function test_group_notification_declines_only_the_target_invitation(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $targetGroup = $this->group($sender, 'Target group');
        $otherGroup = $this->group($sender, 'Other group');

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
            'type' => 'group_invitation',
            'group_id' => $targetGroup->id,
            'status' => 0,
            'view' => 0,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'group_invitation',
            'group_id' => $otherGroup->id,
            'status' => 0,
            'view' => 0,
        ]);

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.groups.decline', [$sender->id, $targetGroup->id]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'group notification decline',
            ]);

        $this->assertDatabaseMissing('invites', ['id' => $targetInvite->id]);
        $this->assertDatabaseMissing('notifications', [
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'group_id' => $targetGroup->id,
        ]);
        $this->assertDatabaseHas('invites', ['id' => $otherInvite->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);
    }

    public function test_event_notification_accepts_receiver_and_scopes_notification(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $targetEvent = $this->event($sender, 'Target event');
        $otherEvent = $this->event($sender, 'Other event');

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
            'type' => 'event_invitation',
            'event_id' => $targetEvent->id,
            'status' => 0,
            'view' => 0,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'event_invitation',
            'event_id' => $otherEvent->id,
            'status' => 0,
            'view' => 0,
        ]);

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.events.accept', [$sender->id, $targetEvent->id]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'event invite request accept',
            ]);

        $this->assertDatabaseHas('invites', ['id' => $targetInvite->id, 'is_accepted' => 1]);
        $this->assertDatabaseHas('invites', ['id' => $otherInvite->id, 'is_accepted' => 0]);
        $this->assertSame([$receiver->id], $this->eventGoingUserIds($targetEvent));
        $this->assertSame([], $this->eventGoingUserIds($otherEvent));
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'event_id' => $targetEvent->id,
            'status' => 1,
            'view' => 1,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification->id,
            'status' => 0,
            'view' => 0,
        ]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $receiver->id,
            'reciver_user_id' => $sender->id,
            'type' => 'event_invitation_accept',
            'event_id' => $targetEvent->id,
        ]);
    }

    public function test_event_notification_declines_only_the_target_invitation(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $targetEvent = $this->event($sender, 'Target event');
        $otherEvent = $this->event($sender, 'Other event');

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
            'type' => 'event_invitation',
            'event_id' => $targetEvent->id,
            'status' => 0,
            'view' => 0,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'event_invitation',
            'event_id' => $otherEvent->id,
            'status' => 0,
            'view' => 0,
        ]);

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.events.decline', [$sender->id, $targetEvent->id]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'event request decline',
            ]);

        $this->assertDatabaseMissing('invites', ['id' => $targetInvite->id]);
        $this->assertDatabaseMissing('notifications', [
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'event_id' => $targetEvent->id,
        ]);
        $this->assertDatabaseHas('invites', ['id' => $otherInvite->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);
    }

    public function test_fundraiser_notification_accepts_target_invitation(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $fundraiserId = 501;
        $otherFundraiserId = 502;

        $targetInvite = $this->invite([
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $receiver->id,
            'fundraiser_id' => $fundraiserId,
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
            'fundraiser_id' => $fundraiserId,
            'status' => 0,
            'view' => 0,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'fundraiser',
            'fundraiser_id' => $otherFundraiserId,
            'status' => 0,
            'view' => 0,
        ]);

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.fundraisers.accept', [$sender->id, $fundraiserId]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'fundraiser request accept',
            ]);

        $this->assertDatabaseHas('invites', ['id' => $targetInvite->id, 'is_accepted' => 1]);
        $this->assertDatabaseHas('invites', ['id' => $otherInvite->id, 'is_accepted' => 0]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'fundraiser_id' => $fundraiserId,
            'status' => 1,
            'view' => 1,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification->id,
            'status' => 0,
            'view' => 0,
        ]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $receiver->id,
            'reciver_user_id' => $sender->id,
            'type' => 'fundraiser_request_accept',
            'fundraiser_id' => $fundraiserId,
        ]);
    }

    public function test_fundraiser_notification_declines_target_invitation(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $fundraiserId = 601;
        $otherFundraiserId = 602;

        $targetInvite = $this->invite([
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $receiver->id,
            'fundraiser_id' => $fundraiserId,
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
            'fundraiser_id' => $fundraiserId,
            'status' => 0,
            'view' => 0,
        ]);
        $otherNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'fundraiser',
            'fundraiser_id' => $otherFundraiserId,
            'status' => 0,
            'view' => 0,
        ]);

        $this->authenticateApiUser($receiver);

        $this->withToken($this->apiToken)
            ->postJson(route('api.notifications.fundraisers.decline', [$sender->id, $fundraiserId]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'fundraiser request decline',
            ]);

        $this->assertDatabaseMissing('invites', ['id' => $targetInvite->id]);
        $this->assertDatabaseMissing('notifications', [
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'fundraiser_id' => $fundraiserId,
        ]);
        $this->assertDatabaseHas('invites', ['id' => $otherInvite->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function notification(array $attributes): Notification
    {
        $notification = new Notification;
        $notification->forceFill($attributes);
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

    private function authenticateApiUser(User $user): void
    {
        $this->apiToken = $user->createToken('api-test')->plainTextToken;
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyAuthenticationPayload(): array
    {
        return [
            'success' => false,
            'message' => 'Unauthorized access',
            'error' => [
                'code' => 'AUTHENTICATION_ERROR',
                'category' => 'authentication',
                'message' => 'Unauthorized access',
                'http_status' => 401,
                'details' => [],
            ],
        ];
    }
}
