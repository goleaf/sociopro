<?php

namespace Database\Factories;

use App\Models\Addon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Addon>
 */
class AddonFactory extends Factory
{
    protected $model = Addon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst($this->faker->words(2, true)),
            'parent_id' => null,
            'features' => null,
            'unique_identifier' => 'addon-'.Str::uuid()->toString(),
            'version' => '1.0',
            'status' => 1,
        ];
    }
}
