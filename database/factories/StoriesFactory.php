<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\Visibility;
use App\Models\Stories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stories>
 */
class StoriesFactory extends Factory
{
    protected $model = Stories::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'publisher' => 'user',
            'publisher_id' => User::factory(),
            'privacy' => Visibility::Public->value,
            'content_type' => 'text',
            'description' => json_encode([
                'color' => '636363',
                'bg-color' => 'fafafa',
                'text' => $this->faker->sentence(),
            ]),
            'status' => ContentStatus::Active->value,
            'created_at' => time(),
            'updated_at' => time(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
            'publisher_id' => $user->id,
        ]);
    }

    public function text(string $text): static
    {
        return $this->state([
            'content_type' => 'text',
            'description' => json_encode([
                'color' => '636363',
                'bg-color' => 'fafafa',
                'text' => $text,
            ]),
        ]);
    }
}
