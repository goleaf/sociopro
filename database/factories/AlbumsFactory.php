<?php

namespace Database\Factories;

use App\Models\Albums;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Albums>
 */
class AlbumsFactory extends Factory
{
    protected $model = Albums::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'page_id' => null,
            'group_id' => null,
            'title' => ucfirst($this->faker->words(3, true)),
            'sub_title' => $this->faker->sentence(),
            'thumbnail' => null,
            'privacy' => 'public',
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
        ];
    }
}
