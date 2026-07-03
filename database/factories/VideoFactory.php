<?php

namespace Database\Factories;

use App\Enums\VideoCategory;
use App\Enums\Visibility;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    protected $model = Video::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucfirst($this->faker->words(3, true)),
            'category' => VideoCategory::Video->value,
            'privacy' => Visibility::Public->value,
            'file' => 'video.mp4',
            'view' => json_encode([]),
            'mobile_app_image' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forOwner(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function private(): static
    {
        return $this->state([
            'privacy' => Visibility::Private->value,
        ]);
    }
}
