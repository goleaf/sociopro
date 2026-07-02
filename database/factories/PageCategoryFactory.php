<?php

namespace Database\Factories;

use App\Models\PageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageCategory>
 */
class PageCategoryFactory extends Factory
{
    protected $model = PageCategory::class;

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
