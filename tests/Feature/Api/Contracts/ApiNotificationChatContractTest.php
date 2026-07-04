<?php

namespace Tests\Feature\Api\Contracts;

use App\Models\Friendships;
use App\Models\MessageThread;

class ApiNotificationChatContractTest extends ApiContractTestCase
{
    public function test_notification_routes_reject_missing_token_before_side_effects(): void
    {
        foreach ([
            ['GET', route('api.notifications.index')],
            ['POST', route('api.notifications.friends.accept', 1)],
            ['POST', route('api.notifications.friends.decline', 1)],
            ['POST', route('api.notifications.groups.accept', [1, 10])],
            ['POST', route('api.notifications.groups.decline', [1, 10])],
            ['POST', route('api.notifications.events.accept', [1, 20])],
            ['POST', route('api.notifications.events.decline', [1, 20])],
            ['POST', route('api.notifications.read', 30)],
            ['POST', route('api.notifications.fundraisers.accept', [1, 40])],
            ['POST', route('api.notifications.fundraisers.decline', [1, 40])],
            ['GET', route('api.notifications.count')],
        ] as [$method, $url]) {
            $this->json($method, $url)
                ->assertUnauthorized()
                ->assertJson($this->legacyAuthenticationPayload());
        }
    }

    public function test_notifications_list_and_mark_as_read_keep_current_contract(): void
    {
        $receiver = $this->activeApiUser();
        $sender = $this->activeApiUser(['name' => 'Contract Sender']);
        $notification = $this->notificationFor($receiver, $sender);
        $headers = $this->apiHeaders($receiver);

        $this->getJson(route('api.notifications.index'), $headers)
            ->assertOk()
            ->assertJsonStructure([
                'new_notifications' => [
                    '*' => [
                        'id',
                        'sender_user_id',
                        'reciver_user_id',
                        'name',
                        'photo',
                        'type',
                        'status',
                        'view',
                        'created_at',
                    ],
                ],
                'older_notifications',
            ])
            ->assertJsonPath('new_notifications.0.id', $notification->id)
            ->assertJsonPath('new_notifications.0.name', 'Contract Sender');

        auth()->forgetGuards();

        $this->postJson(route('api.notifications.read', $notification->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'mark as read',
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 1,
            'view' => 1,
        ]);
    }

    public function test_friend_notification_accept_and_decline_smoke_contracts(): void
    {
        $requester = $this->activeApiUser();
        $receiver = $this->activeApiUser();

        Friendships::query()->create([
            'requester' => $requester->id,
            'accepter' => $receiver->id,
            'is_accepted' => 0,
        ]);
        $this->notificationFor($receiver, $requester);

        $this->postJson(
            route('api.notifications.friends.accept', $requester->id),
            [],
            $this->apiHeaders($receiver)
        )
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Friend request accept',
            ]);

        $this->assertDatabaseHas('friendships', [
            'requester' => $requester->id,
            'accepter' => $receiver->id,
            'is_accepted' => 1,
        ]);

        $declineRequester = $this->activeApiUser();
        $declineReceiver = $this->activeApiUser();
        Friendships::query()->create([
            'requester' => $declineRequester->id,
            'accepter' => $declineReceiver->id,
            'is_accepted' => 0,
        ]);
        $this->notificationFor($declineReceiver, $declineRequester, [
            'type' => 'profile',
        ]);

        auth()->forgetGuards();

        $this->postJson(
            route('api.notifications.friends.decline', $declineRequester->id),
            [],
            $this->apiHeaders($declineReceiver)
        )
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'successfully decline',
            ]);

        $this->assertDatabaseMissing('friendships', [
            'requester' => $declineRequester->id,
            'accepter' => $declineReceiver->id,
        ]);
    }

    public function test_chat_routes_reject_missing_token_before_legacy_controller_methods(): void
    {
        foreach ([
            ['GET', route('api.chat.index')],
            ['GET', route('api.chat.messages.index', 1)],
            ['POST', route('api.chat.messages.store')],
            ['POST', route('api.chat.threads.store')],
            ['POST', route('api.chat.messages.destroy', 1)],
            ['POST', route('api.chat.read.store', 1)],
            ['POST', route('api.chat.reactions.store')],
        ] as [$method, $url]) {
            $this->json($method, $url)
                ->assertUnauthorized()
                ->assertJson($this->legacyAuthenticationPayload());
        }
    }

    public function test_chat_index_messages_and_thread_save_preserve_legacy_key_names(): void
    {
        $sender = $this->activeApiUser(['name' => 'Contract Chat Sender']);
        $receiver = $this->activeApiUser(['name' => 'Contract Chat Receiver']);
        $thread = $this->chatThreadBetween($sender, $receiver);
        $chat = $this->chatMessage($thread, $receiver, $sender);
        $headers = $this->apiHeaders($sender);

        $this->getJson(route('api.chat.index'), $headers)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $thread->id,
                'receiver_id' => $receiver->id,
                'reciver_id' => $receiver->id,
                'sender_id' => $sender->id,
                'profile_name' => 'Contract Chat Receiver',
            ]);

        auth()->forgetGuards();

        $this->getJson(route('api.chat.messages.index', $thread->id), $headers)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $chat->id,
                'message_thread_id' => $thread->id,
                'message_thrade' => $thread->id,
                'receiver_id' => $sender->id,
                'reciver_id' => $sender->id,
                'sender_id' => $receiver->id,
                'message' => 'API contract chat message',
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.chat.threads.store'), [
            'reciver_id' => $receiver->id,
            'messagecenter' => 'chat',
        ], $headers)
            ->assertOk()
            ->assertExactJson([]);

        $this->assertSame(2, MessageThread::query()->count());
    }
}
