<?php

namespace Database\Factories;

use App\Models\Pagecategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pagecategory>
 */
class PagecategoryFactory extends Factory
{
    protected $model = Pagecategory::class;

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
