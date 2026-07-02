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
            'message_thrade' => MessageThread::factory(),
            'reciver_id' => User::factory(),
            'sender_id' => User::factory(),
            'message' => $this->faker->sentence(),
            'thumbsup' => 0,
            'file' => '0',
            'react' => null,
            'reply_id' => null,
            'chatcenter' => 'chat',
            'read_status' => 0,
        ];
    }

    public function forThread(MessageThread $thread): static
    {
        return $this->state(fn (): array => [
            'message_thrade' => $thread->id,
            'chatcenter' => $thread->chat_center,
        ]);
    }

    public function fromTo(User $sender, User $receiver): static
    {
        return $this->state(fn (): array => [
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            'read_status' => 1,
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (): array => [
            'read_status' => 0,
        ]);
    }

    public function withAttachment(): static
    {
        return $this->state(fn (): array => [
            'file' => '1',
        ]);
    }
}
