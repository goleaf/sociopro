<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Chat;
use App\Models\MessageThread;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ChatControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-chat-sender@example.test',
        'dusk-chat-receiver@example.test',
        'dusk-chat-other@example.test',
    ];

    private const INITIAL_MESSAGE = 'Dusk initial incoming chat message';

    private const SENT_MESSAGE = 'Dusk browser sent chat message';

    private const READ_OPTION_MESSAGE = 'Dusk read option incoming chat message';

    private const OTHER_CONTACT_MESSAGE = 'Dusk other contact message';

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

    public function test_chat_page_sends_searches_reacts_loads_reads_and_removes_messages_in_browser(): void
    {
        $sender = $this->activeUser('dusk-chat-sender@example.test', 'Dusk Chat Sender');
        $receiver = $this->activeUser('dusk-chat-receiver@example.test', 'Dusk Chat Receiver');
        $other = $this->activeUser('dusk-chat-other@example.test', 'Dusk Chat Other Contact');

        $thread = $this->createThread($sender, $receiver);
        $otherThread = $this->createThread($sender, $other);
        $incoming = $this->createChat($thread, $receiver, $sender, self::INITIAL_MESSAGE);
        $this->createChat($otherThread, $other, $sender, self::OTHER_CONTACT_MESSAGE);

        $this->browse(function (Browser $browser) use ($incoming, $receiver, $sender) {
            $browser->loginAs($sender)
                ->visit('/chat/inbox/'.$receiver->id)
                ->assertSee('Dusk Chat Receiver')
                ->assertSee(self::INITIAL_MESSAGE);

            $this->setChatMessage($browser, self::SENT_MESSAGE);

            $browser->click('#ChatsentButton')
                ->waitForText(self::SENT_MESSAGE, 5)
                ->script("myMessageReact('love', 'update', {$incoming->id});");

            $browser->waitUntil(
                "document.querySelector('#ShowReactId_{$incoming->id} img[alt=\"Love\"]') !== null",
                5
            );
        });

        $incoming->refresh();
        $this->assertSame('love', $incoming->react);

        $sentChat = Chat::query()->where('message', self::SENT_MESSAGE)->firstOrFail();
        $this->assertSame($sender->id, (int) $sentChat->sender_id);
        $this->assertSame($receiver->id, (int) $sentChat->reciver_id);

        $this->browse(function (Browser $browser) use ($receiver, $sender, $sentChat) {
            $browser->loginAs($sender)
                ->visit('/chat/profile/search/?search=Dusk%20Chat%20Receiver')
                ->assertSourceHas('Dusk Chat Receiver')
                ->assertSourceMissing('Dusk Chat Other Contact')
                ->visit('/chat/inbox/load/data/ajax/?id='.$receiver->id)
                ->assertSourceHas(self::INITIAL_MESSAGE);

            $readOptionMessage = $this->createChat(
                MessageThread::betweenParticipants($sender->id, $receiver->id)->firstOrFail(),
                $receiver,
                $sender,
                self::READ_OPTION_MESSAGE
            );

            $browser->visit('/chat/inbox/read/message/ajax/?id='.$receiver->id);

            $this->assertSame(1, (int) $readOptionMessage->refresh()->read_status);

            $browser->visit('/chat/own/remove/'.$sentChat->id);
        });

        $this->assertDatabaseMissing('chats', ['id' => $sentChat->id]);
    }

    private function setChatMessage(Browser $browser, string $message): void
    {
        $encodedMessage = json_encode($message, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            const message = {$encodedMessage};
            const field = document.querySelector('#ChatmessageField');
            field.value = message;

            if (window.jQuery) {
                const emojiArea = window.jQuery(field).data('emojioneArea');
                if (emojiArea) {
                    emojiArea.setText(message);
                    field.value = message;
                }
            }
        JS);
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
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
        $user->save();

        return $user;
    }

    private function createThread(User $sender, User $receiver): MessageThread
    {
        $thread = new MessageThread;
        $thread->sender_id = $sender->id;
        $thread->receiver_id = $receiver->id;
        $thread->chat_center = 'chat';
        $thread->save();

        return $thread;
    }

    private function createChat(MessageThread $thread, User $sender, User $receiver, string $message): Chat
    {
        $chat = new Chat;
        $chat->message_thread_id = $thread->id;
        $chat->sender_id = $sender->id;
        $chat->receiver_id = $receiver->id;
        $chat->message = $message;
        $chat->chat_center = 'chat';
        $chat->thumbsup = 0;
        $chat->file = '1';
        $chat->read_status = 0;
        $chat->save();

        return $chat;
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        Chat::query()
            ->whereIn('sender_id', $userIds)
            ->orWhereIn('reciver_id', $userIds)
            ->delete();
        MessageThread::query()
            ->whereIn('sender_id', $userIds)
            ->orWhereIn('reciver_id', $userIds)
            ->delete();
        User::query()->whereIn('id', $userIds)->delete();
    }
}
