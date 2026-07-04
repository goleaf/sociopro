<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogCategory>
 */
class BlogCategoryFactory extends Factory
{
    protected $model = BlogCategory::class;

    /**
     * @return array{name: string}
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->words(2, true)),
        ];
    }
}
