<?php

namespace App\Actions\Chat;

use App\Models\MessageThread;
use App\Models\User;

class FindOrCreateMessageThreadAction
{
    public function execute(User $sender, User $receiver, ?string $chatCenter = null): MessageThread
    {
        $messageThread = MessageThread::query()
            ->betweenUsers((int) $sender->id, (int) $receiver->id)
            ->first();

        if ($messageThread) {
            return $messageThread;
        }

        return MessageThread::create([
            MessageThread::SENDER_ID_COLUMN => $sender->id,
            MessageThread::LEGACY_RECEIVER_ID_COLUMN => $receiver->id,
            MessageThread::RECEIVER_ID_COLUMN => $receiver->id,
            MessageThread::LEGACY_CHAT_CENTER_COLUMN => $chatCenter,
            MessageThread::CHAT_CENTER_COLUMN => $chatCenter,
        ]);
    }
}
