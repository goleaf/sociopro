<?php

namespace Database\Factories;

use App\Models\Friendships;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Friendships>
 */
class FriendshipsFactory extends Factory
{
    protected $model = Friendships::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester' => User::factory(),
            'accepter' => User::factory(),
            'importance' => 0,
            'is_accepted' => 0,
            'accepted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function accepted(): static
    {
        return $this->state([
            'is_accepted' => 1,
            'accepted_at' => now()->toDateTimeString(),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'is_accepted' => 0,
            'accepted_at' => null,
        ]);
    }

    public function requester(User $user): static
    {
        return $this->state([
            'requester' => $user->id,
        ]);
    }

    public function accepter(User $user): static
    {
        return $this->state([
            'accepter' => $user->id,
        ]);
    }
}
