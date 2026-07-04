<?php

namespace Database\Factories;

use App\Models\Follower;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Follower>
 */
class FollowerFactory extends Factory
{
    protected $model = Follower::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'follow_id' => User::factory(),
            'page_id' => null,
            'group_id' => null,
        ];
    }

    public function forPair(User $user, User $target): static
    {
        return $this->state([
            'user_id' => $user->id,
            'follow_id' => $target->id,
        ]);
    }

    public function forFollower(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function following(User $target): static
    {
        return $this->state([
            'follow_id' => $target->id,
        ]);
    }
}
