<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'english',
            'phrase' => ucfirst($this->faker->unique()->words(3, true)),
            'translated' => ucfirst($this->faker->words(3, true)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
