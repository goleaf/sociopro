<?php

namespace Database\Factories;

use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Sponsor '.$this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'ext_url' => 'https://example.test/'.$this->faker->unique()->slug(),
            'image' => 'sponsor.jpg',
            'paid_amount' => '0.00',
            'status' => 1,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'start_date' => now()->subWeeks(2),
            'end_date' => now()->subWeek(),
        ]);
    }
}
