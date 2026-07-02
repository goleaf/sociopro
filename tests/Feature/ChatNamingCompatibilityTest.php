<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Chat;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ChatNamingCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_thread_accepts_canonical_receiver_and_chat_center_names(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();

        $messageThread = MessageThread::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'chat_center' => 'marketplace',
        ]);

        $this->assertSame('message_thrades', $messageThread->getTable());
        $this->assertSame($receiver->id, $messageThread->receiver_id);
        $this->assertSame('marketplace', $messageThread->chat_center);
        $this->assertDatabaseHas('message_thrades', [
            'id' => $messageThread->id,
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'chatcenter' => 'marketplace',
        ]);
        $this->assertTrue(
            MessageThread::query()
                ->betweenParticipants($sender->id, $receiver->id)
                ->first()
                ?->is($messageThread)
        );
    }

    public function test_chat_accepts_canonical_message_thread_receiver_and_chat_center_names(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $messageThread = MessageThread::factory()
            ->between($sender, $receiver)
            ->create();

        $chat = new Chat;
        $chat->message_thread_id = $messageThread->id;
        $chat->receiver_id = $receiver->id;
        $chat->sender_id = $sender->id;
        $chat->chat_center = 'marketplace';
        $chat->message = 'Canonical naming works';
        $chat->read_status = 0;
        $chat->save();

        $this->assertSame($messageThread->id, $chat->message_thread_id);
        $this->assertSame($receiver->id, $chat->receiver_id);
        $this->assertSame('marketplace', $chat->chat_center);
        $this->assertDatabaseHas('chats', [
            'id' => $chat->id,
            'message_thrade' => $messageThread->id,
            'reciver_id' => $receiver->id,
            'chatcenter' => 'marketplace',
        ]);
        $this->assertTrue(
            Chat::query()
                ->forMessageThread($messageThread->id)
                ->unreadForReceiver($receiver->id)
                ->first()
                ?->is($chat)
        );
    }

    public function test_api_chat_routes_use_canonical_message_thread_parameter_name(): void
    {
        $route = Route::getRoutes()->getByName('api.chat.messages.index');

        $this->assertNotNull($route);
        $this->assertSame(['message_thread'], $route->parameterNames());
    }

    public function test_api_chat_save_accepts_canonical_receiver_input_while_persisting_legacy_columns(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();

        $this
            ->withToken($sender->createToken('chat-naming-test')->plainTextToken)
            ->postJson(route('api.chat.messages.store'), [
                'receiver_id' => $receiver->id,
                'messagecenter' => 'chat',
                'message' => 'Hello with canonical receiver id',
                'thumbsup' => 0,
            ])
            ->assertOk();

        $this->assertDatabaseHas('message_thrades', [
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'chatcenter' => 'chat',
        ]);
        $this->assertDatabaseHas('chats', [
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'chatcenter' => 'chat',
            'message' => 'Hello with canonical receiver id',
        ]);
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'user_role' => UserRole::General->value,
        ]);
    }
}
