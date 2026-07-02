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
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'chat_center' => 'chat',
        ];
    }

    public function between(User $sender, User $receiver): static
    {
        return $this->state([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'chat_center' => 'chat',
        ]);
    }
}
