<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Blog>
 */
class BlogFactory extends Factory
{
    protected $model = Blog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => BlogCategory::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'tag' => json_encode([]),
            'thumbnail' => null,
            'view' => json_encode([]),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    public function forOwner(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function forCategory(BlogCategory $category): static
    {
        return $this->state([
            'category_id' => $category->id,
        ]);
    }
}
