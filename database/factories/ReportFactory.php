<?php

namespace Database\Factories;

use App\Models\Posts;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Posts::factory(),
            'report' => $this->faker->sentence(),
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
