<?php

namespace Database\Factories;

use App\Models\Marketplace;
use App\Models\SavedProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedProduct>
 */
class SavedProductFactory extends Factory
{
    /**
     * @return array{user_id: Factory<User>, product_id: Factory<Marketplace>}
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Marketplace::factory(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function forProduct(Marketplace $product): static
    {
        return $this->state([
            'product_id' => $product->id,
        ]);
    }
}
