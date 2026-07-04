<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Chat;
use App\Models\MediaFile;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LegacyApiChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_chat_list_requires_token_and_returns_current_shape(): void
    {
        $sender = $this->activeUser(['name' => 'API Sender']);
        $receiver = $this->activeUser(['name' => 'API Receiver']);
        $thread = $this->createThread($sender, $receiver);

        $this->createChat($thread, $receiver, $sender, 'API latest chat message');

        $this->getJson(route('api.chat.index'))
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'AUTHENTICATION_ERROR');

        $this->getJson(route('api.chat.index'), $this->apiHeaders($sender))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $thread->id,
                'receiver_id' => $receiver->id,
                'reciver_id' => $receiver->id,
                'sender_id' => $sender->id,
                'profile_id' => $receiver->id,
                'profile_name' => 'API Receiver',
                'last_msg' => 'API latest chat message',
            ])
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'receiver_id',
                    'reciver_id',
                    'sender_id',
                    'profile_id',
                    'profile_name',
                    'profile_photo',
                    'msg_sender',
                    'last_msg',
                    'last_thumbs',
                    'msg_time',
                    'read',
                ],
            ]);
    }

    public function test_api_chat_messages_route_returns_messages_for_current_thread_participant(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser(['name' => 'Thread Receiver']);
        $thread = $this->createThread($sender, $receiver);
        $chat = $this->createChat($thread, $receiver, $sender, 'Legacy API thread message');

        $this->getJson(route('api.chat.messages.index', $thread->id), $this->apiHeaders($sender))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $chat->id,
                'message_thread_id' => $thread->id,
                'message_thrade' => $thread->id,
                'receiver_id' => $sender->id,
                'reciver_id' => $sender->id,
                'sender_id' => $receiver->id,
                'profile_name' => 'Thread Receiver',
                'message' => 'Legacy API thread message',
            ])
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'message_thread_id',
                    'message_thrade',
                    'receiver_id',
                    'reciver_id',
                    'sender_id',
                    'sender',
                    'profile_name',
                    'profile_photo',
                    'message',
                    'thumbs',
                    'react',
                    'msg_time',
                    'read',
                ],
            ]);
    }

    public function test_api_chat_messages_route_denies_unrelated_authenticated_user(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $unrelatedUser = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);

        $this->createChat($thread, $receiver, $sender, 'Protected API thread message');

        $this->getJson(route('api.chat.messages.index', $thread->id), $this->apiHeaders($unrelatedUser))
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
    }

    public function test_api_chat_save_creates_then_reuses_legacy_thread_and_preserves_response_shapes(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();

        $firstResponse = $this->postJson(route('api.chat.messages.store'), [
            'reciver_id' => $receiver->id,
            'message' => 'API first message',
            'messagecenter' => 'chat',
            'thumbsup' => 0,
        ], $this->apiHeaders($sender));

        $firstResponse
            ->assertOk()
            ->assertJsonStructure([
                'appendElement',
                'content',
                'clickTo',
            ]);

        $thread = MessageThread::query()->firstOrFail();

        $this->assertDatabaseHas('message_thrades', [
            'id' => $thread->id,
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'chatcenter' => 'chat',
        ]);

        $this->assertDatabaseHas('chats', [
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'message' => 'API first message',
            'message_thrade' => $thread->id,
            'chatcenter' => 'chat',
            'file' => '1',
        ]);

        auth()->forgetGuards();

        $this->postJson(route('api.chat.messages.store'), [
            'reciver_id' => $sender->id,
            'message' => 'API reverse message',
            'messagecenter' => 'chat',
            'thumbsup' => 0,
        ], $this->apiHeaders($receiver))
            ->assertOk()
            ->assertExactJson([]);

        $this->assertSame(1, MessageThread::query()->count());
        $this->assertDatabaseHas('chats', [
            'sender_id' => $receiver->id,
            'reciver_id' => $sender->id,
            'message' => 'API reverse message',
            'message_thrade' => $thread->id,
        ]);
    }

    public function test_api_thread_save_creates_a_new_legacy_thread_each_time_currently(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();

        $this->postJson(route('api.chat.threads.store'), [
            'reciver_id' => $receiver->id,
            'messagecenter' => 'chat',
        ], $this->apiHeaders($sender))
            ->assertOk()
            ->assertExactJson([]);

        $this->postJson(route('api.chat.threads.store'), [
            'reciver_id' => $receiver->id,
            'messagecenter' => 'chat',
        ], $this->apiHeaders($sender))
            ->assertOk()
            ->assertExactJson([]);

        $this->assertSame(2, MessageThread::query()->count());
        $this->assertDatabaseHas('message_thrades', [
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'chatcenter' => 'chat',
        ]);
    }

    public function test_api_remove_chat_deletes_message_for_participant(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);
        $chat = $this->createChat($thread, $sender, $receiver, 'API removable message');

        $this->postJson(route('api.chat.messages.destroy', $chat->id), [], $this->apiHeaders($sender))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Delete successfully',
            ]);

        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
    }

    public function test_api_remove_chat_denies_unrelated_authenticated_user(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $unrelatedUser = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);
        $chat = $this->createChat($thread, $sender, $receiver, 'API protected removable message');

        $this->postJson(route('api.chat.messages.destroy', $chat->id), [], $this->apiHeaders($unrelatedUser))
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

        $this->assertDatabaseHas('chats', ['id' => $chat->id]);
    }

    public function test_api_chat_read_option_marks_only_auth_users_unread_messages(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $otherUser = $this->activeUser();

        $thread = $this->createThread($sender, $receiver);
        $unrelatedThread = $this->createThread($receiver, $otherUser);

        $incoming = $this->createChat($thread, $receiver, $sender, 'API unread for sender');
        $outgoing = $this->createChat($thread, $sender, $receiver, 'API unread for receiver');
        $unrelated = $this->createChat($unrelatedThread, $receiver, $otherUser, 'API unrelated unread');

        $this->postJson(route('api.chat.read.store', $receiver->id), [], $this->apiHeaders($sender))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Messages marked as read successfully',
            ]);

        $this->assertDatabaseHas('chats', ['id' => $incoming->id, 'read_status' => 1]);
        $this->assertDatabaseHas('chats', ['id' => $outgoing->id, 'read_status' => 0]);
        $this->assertDatabaseHas('chats', ['id' => $unrelated->id, 'read_status' => 0]);
    }

    public function test_api_react_chat_updates_reaction_and_returns_empty_array_contract(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);
        $chat = $this->createChat($thread, $sender, $receiver, 'API reaction message');

        $this->postJson(route('api.chat.reactions.store'), [
            'messageId' => $chat->id,
            'react' => 'haha',
        ], $this->apiHeaders($sender))
            ->assertOk()
            ->assertExactJson([]);

        $this->assertDatabaseHas('chats', ['id' => $chat->id, 'react' => 'haha']);
    }

    public function test_api_react_chat_denies_unrelated_authenticated_user(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $unrelatedUser = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);
        $chat = $this->createChat($thread, $sender, $receiver, 'API protected reaction message');

        $this->postJson(route('api.chat.reactions.store'), [
            'messageId' => $chat->id,
            'react' => 'haha',
        ], $this->apiHeaders($unrelatedUser))
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

        $this->assertDatabaseHas('chats', ['id' => $chat->id, 'react' => null]);
    }

    public function test_api_chat_save_rejects_invalid_upload_extension_without_creating_media(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();

        $this->post(route('api.chat.messages.store'), [
            'reciver_id' => $receiver->id,
            'message' => 'API invalid upload message',
            'messagecenter' => 'chat',
            'thumbsup' => 0,
            'multiple_files' => [
                UploadedFile::fake()->create('payload.php', 1, 'application/x-php'),
            ],
        ], $this->apiHeaders($sender))
            ->assertOk()
            ->assertJsonStructure([
                'validationError',
            ]);

        $this->assertSame(0, MediaFile::query()->count());
        $this->assertSame(1, Chat::query()->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
            'timezone' => 'UTC',
            'lastActive' => now()->subMinute(),
        ], $attributes));
    }

    /**
     * @return array<string, string>
     */
    private function apiHeaders(User $user): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$user->createToken('legacy-api-chat-test')->plainTextToken,
        ];
    }

    private function createThread(User $sender, User $receiver, string $chatCenter = 'chat'): MessageThread
    {
        return MessageThread::factory()
            ->between($sender, $receiver)
            ->create(['chat_center' => $chatCenter]);
    }

    private function createChat(
        MessageThread $thread,
        User $sender,
        User $receiver,
        string $message,
        int $readStatus = 0
    ): Chat {
        return Chat::factory()
            ->forThread($thread)
            ->fromTo($sender, $receiver)
            ->create([
                'message' => $message,
                'read_status' => $readStatus,
            ]);
    }
}
