<?php

namespace Database\Factories;

use App\Models\Comments;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comments>
 */
class CommentsFactory extends Factory
{
    protected $model = Comments::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => 0,
            'user_id' => User::factory(),
            'is_type' => 'post',
            'id_of_type' => Posts::factory(),
            'description' => $this->faker->sentence(),
            'user_reacts' => json_encode([]),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    public function forOwner(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function forPost(Posts $post): static
    {
        return $this->state([
            'is_type' => 'post',
            'id_of_type' => $post->post_id,
        ]);
    }
}
