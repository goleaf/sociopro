<?php

namespace App\Actions\Chat;

use App\Models\Chat;
use App\Models\MessageThread;
use App\Models\User;

class StoreChatMessageAction
{
    public function execute(
        User $sender,
        User $receiver,
        MessageThread $messageThread,
        ?string $message,
        ?string $chatCenter,
        int|string|null $thumbsup,
        string $file = '1',
    ): Chat {
        $chat = new Chat;
        $chat->setAttribute(Chat::SENDER_ID_COLUMN, $sender->id);
        $chat->setAttribute(Chat::LEGACY_RECEIVER_ID_COLUMN, $receiver->id);
        $chat->setAttribute(Chat::LEGACY_MESSAGE_THREAD_ID_COLUMN, $messageThread->id);
        $chat->setAttribute(Chat::LEGACY_CHAT_CENTER_COLUMN, $chatCenter);
        $chat->message = $message;
        $chat->thumbsup = $thumbsup;
        $chat->file = $file;
        $chat->save();

        return $chat;
    }
}
