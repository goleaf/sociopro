<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\Visibility;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Posts>
 */
class PostsFactory extends Factory
{
    protected $model = Posts::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'publisher' => 'post',
            'publisher_id' => User::factory(),
            'post_type' => 'general',
            'privacy' => Visibility::Public->value,
            'description' => $this->faker->sentence(),
            'status' => ContentStatus::Active->value,
            'user_reacts' => json_encode([]),
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
        ];
    }

    public function forOwner(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
            'publisher_id' => $user->id,
        ]);
    }
}
