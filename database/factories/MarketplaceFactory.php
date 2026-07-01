<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Marketplace>
 */
class MarketplaceFactory extends Factory
{
    /**
     * @return array{user_id: Factory<User>, title: string, currency_id: Factory<Currency>, price: string, location: string, category: Factory<Category>, status: string, condition: string, brand: Factory<Brand>, buy_link: string, description: string, image: string}
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucfirst($this->faker->words(3, true)),
            'currency_id' => Currency::factory(),
            'price' => number_format($this->faker->randomFloat(2, 5, 2500), 2, '.', ''),
            'location' => $this->faker->city(),
            'category' => Category::factory(),
            'status' => '1',
            'condition' => $this->faker->randomElement(['new', 'used']),
            'brand' => Brand::factory(),
            'buy_link' => $this->faker->url(),
            'description' => $this->faker->paragraph(),
            'image' => 'marketplace/'.$this->faker->uuid().'.jpg',
        ];
    }

    public function forOwner(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state([
            'category' => (string) $category->id,
        ]);
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state([
            'brand' => (string) $brand->id,
        ]);
    }

    public function forCurrency(Currency $currency): static
    {
        return $this->state([
            'currency_id' => $currency->id,
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => '1',
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'status' => '0',
        ]);
    }

    public function newCondition(): static
    {
        return $this->state([
            'condition' => 'new',
        ]);
    }

    public function used(): static
    {
        return $this->state([
            'condition' => 'used',
        ]);
    }
}
