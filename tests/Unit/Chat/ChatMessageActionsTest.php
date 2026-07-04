<?php

declare(strict_types=1);

namespace Tests\Unit\Chat;

use App\Actions\Chat\FindOrCreateMessageThreadAction;
use App\Actions\Chat\StoreChatMessageAction;
use App\Models\Chat;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ChatMessageActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_or_create_message_thread_action_reuses_existing_thread_in_either_direction_without_auth(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $existingThread = MessageThread::factory()
            ->between($receiver, $sender)
            ->create(['chat_center' => 'chat']);

        $this->assertFalse(Auth::check());

        $messageThread = app(FindOrCreateMessageThreadAction::class)
            ->execute($sender, $receiver, 'chat');

        $this->assertTrue($existingThread->is($messageThread));
        $this->assertSame(1, MessageThread::query()->count());
    }

    public function test_find_or_create_message_thread_action_creates_legacy_thread_without_auth(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $this->assertFalse(Auth::check());

        $messageThread = app(FindOrCreateMessageThreadAction::class)
            ->execute($sender, $receiver, 'marketplace');

        $this->assertDatabaseHas(MessageThread::TABLE, [
            'id' => $messageThread->id,
            MessageThread::SENDER_ID_COLUMN => $sender->id,
            MessageThread::LEGACY_RECEIVER_ID_COLUMN => $receiver->id,
            MessageThread::LEGACY_CHAT_CENTER_COLUMN => 'marketplace',
        ]);
    }

    public function test_store_chat_message_action_creates_legacy_chat_row_without_auth(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $messageThread = MessageThread::factory()
            ->between($sender, $receiver)
            ->create(['chat_center' => 'chat']);

        $this->assertFalse(Auth::check());

        $chat = app(StoreChatMessageAction::class)->execute(
            sender: $sender,
            receiver: $receiver,
            messageThread: $messageThread,
            message: 'Action-created legacy message',
            chatCenter: 'chat',
            thumbsup: 1,
            file: '1',
        );

        $this->assertDatabaseHas('chats', [
            'id' => $chat->id,
            Chat::SENDER_ID_COLUMN => $sender->id,
            Chat::LEGACY_RECEIVER_ID_COLUMN => $receiver->id,
            Chat::LEGACY_MESSAGE_THREAD_ID_COLUMN => $messageThread->id,
            Chat::LEGACY_CHAT_CENTER_COLUMN => 'chat',
            'message' => 'Action-created legacy message',
            'thumbsup' => 1,
            'file' => '1',
        ]);
    }

    public function test_message_thread_between_users_scope_matches_both_directions(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $otherUser = User::factory()->create();

        $expectedThread = MessageThread::factory()->between($sender, $receiver)->create();
        MessageThread::factory()->between($sender, $otherUser)->create();

        $this->assertTrue(
            MessageThread::query()
                ->betweenUsers($receiver->id, $sender->id)
                ->first()
                ?->is($expectedThread)
        );
    }

    public function test_chat_scopes_filter_thread_unread_receiver_and_participants(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $otherUser = User::factory()->create();
        $messageThread = MessageThread::factory()->between($sender, $receiver)->create();
        $otherThread = MessageThread::factory()->between($sender, $otherUser)->create();

        $expectedChat = Chat::factory()
            ->forThread($messageThread)
            ->fromTo($sender, $receiver)
            ->unread()
            ->create(['message' => 'Expected unread chat']);

        Chat::factory()
            ->forThread($messageThread)
            ->fromTo($receiver, $sender)
            ->unread()
            ->create(['message' => 'Wrong receiver']);

        Chat::factory()
            ->forThread($otherThread)
            ->fromTo($sender, $otherUser)
            ->unread()
            ->create(['message' => 'Wrong thread']);

        $this->assertTrue(
            Chat::query()
                ->forThread($messageThread->id)
                ->unreadForReceiver($receiver->id)
                ->betweenUsers($receiver->id, $sender->id)
                ->first()
                ?->is($expectedChat)
        );
    }
}
