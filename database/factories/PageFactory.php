<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucfirst($this->faker->words(3, true)),
            'category_id' => PageCategory::factory(),
            'logo' => null,
            'coverphoto' => null,
            'description' => $this->faker->paragraph(),
            'status' => '1',
        ];
    }

    public function forOwner(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function forCategory(PageCategory $category): static
    {
        return $this->state([
            'category_id' => $category->id,
        ]);
    }
}
