<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Chat;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chat>
 */
class ChatFactory extends Factory
{
    protected $model = Chat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Chat::MESSAGE_THREAD_ID_COLUMN => MessageThread::factory(),
            Chat::RECEIVER_ID_COLUMN => User::factory(),
            Chat::SENDER_ID_COLUMN => User::factory(),
            'message' => $this->faker->sentence(),
            'thumbsup' => 0,
            'file' => '0',
            'react' => null,
            'reply_id' => null,
            Chat::CHAT_CENTER_COLUMN => 'chat',
            Chat::READ_STATUS_COLUMN => 0,
        ];
    }

    public function forThread(MessageThread $thread): static
    {
        return $this->state(fn (): array => [
            Chat::MESSAGE_THREAD_ID_COLUMN => $thread->id,
            Chat::CHAT_CENTER_COLUMN => $thread->chat_center,
        ]);
    }

    public function fromTo(User $sender, User $receiver): static
    {
        return $this->state(fn (): array => [
            Chat::SENDER_ID_COLUMN => $sender->id,
            Chat::RECEIVER_ID_COLUMN => $receiver->id,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            Chat::READ_STATUS_COLUMN => 1,
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (): array => [
            Chat::READ_STATUS_COLUMN => 0,
        ]);
    }

    public function withAttachment(): static
    {
        return $this->state(fn (): array => [
            'file' => '1',
        ]);
    }
}
