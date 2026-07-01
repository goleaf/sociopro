<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Marketplace;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Local demo seeder may only run in local or testing environments.');
        }

        $user = $this->demoUser();
        $category = $this->demoCategory();
        $brand = $this->demoBrand();
        $currency = $this->demoCurrency();

        if (Marketplace::query()
            ->where('user_id', $user->id)
            ->where('title', 'Local demo marketplace product')
            ->exists()
        ) {
            return;
        }

        Marketplace::factory()
            ->forOwner($user)
            ->forCategory($category)
            ->forBrand($brand)
            ->forCurrency($currency)
            ->used()
            ->active()
            ->create([
                'title' => 'Local demo marketplace product',
                'price' => '25.00',
                'location' => 'Example City',
                'description' => 'Factory-generated local marketplace sample for development and tests only.',
            ]);
    }

    private function demoUser(): User
    {
        $user = User::query()->where('email', 'local-demo@example.test')->first();

        if ($user instanceof User) {
            return $user;
        }

        $user = User::factory()->make([
            'name' => 'Local Demo User',
            'email' => 'local-demo@example.test',
            'username' => 'local-demo-user',
            'password' => Hash::make(Str::password(32)),
        ]);
        $user->save();

        return $user;
    }

    private function demoCategory(): Category
    {
        return Category::query()->where('name', 'Local Demo Electronics')->first()
            ?? Category::factory()->create([
                'name' => 'Local Demo Electronics',
            ]);
    }

    private function demoBrand(): Brand
    {
        return Brand::query()->where('name', 'Local Demo Brand')->first()
            ?? Brand::factory()->create([
                'name' => 'Local Demo Brand',
            ]);
    }

    private function demoCurrency(): Currency
    {
        return Currency::query()->where('code', 'USD')->first()
            ?? Currency::factory()->usd()->create();
    }
}
