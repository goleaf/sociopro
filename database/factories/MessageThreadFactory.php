<?php

namespace Database\Factories;

use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageThread>
 */
class MessageThreadFactory extends Factory
{
    protected $model = MessageThread::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            MessageThread::SENDER_ID_COLUMN => User::factory(),
            MessageThread::LEGACY_RECEIVER_ID_COLUMN => User::factory(),
            MessageThread::RECEIVER_ID_COLUMN => fn (array $attributes): mixed => $attributes[MessageThread::LEGACY_RECEIVER_ID_COLUMN],
            MessageThread::LEGACY_CHAT_CENTER_COLUMN => 'chat',
            MessageThread::CHAT_CENTER_COLUMN => 'chat',
        ];
    }

    public function between(User $sender, User $receiver): static
    {
        return $this->state([
            MessageThread::SENDER_ID_COLUMN => $sender->id,
            MessageThread::LEGACY_RECEIVER_ID_COLUMN => $receiver->id,
            MessageThread::RECEIVER_ID_COLUMN => $receiver->id,
            MessageThread::LEGACY_CHAT_CENTER_COLUMN => 'chat',
            MessageThread::CHAT_CENTER_COLUMN => 'chat',
        ]);
    }
}
