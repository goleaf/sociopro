<?php

namespace Database\Factories;

use App\Models\Message_thrade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message_thrade>
 */
class Message_thradeFactory extends Factory
{
    protected $model = Message_thrade::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'reciver_id' => User::factory(),
            'chatcenter' => 'chat',
        ];
    }

    public function between(User $sender, User $receiver): static
    {
        return $this->state([
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'chatcenter' => 'chat',
        ]);
    }
}
