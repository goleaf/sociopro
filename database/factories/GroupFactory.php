<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'subtitle' => $this->faker->sentence(),
            'privacy' => 'public',
            'location' => $this->faker->city(),
            'group_type' => 'general',
            'logo' => null,
            'banner' => null,
            'about' => $this->faker->paragraph(),
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
