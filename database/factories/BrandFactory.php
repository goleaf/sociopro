<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /**
     * @return array{name: string}
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
        ];
    }

    public function acme(): static
    {
        return $this->state([
            'name' => 'Acme',
        ]);
    }
}
