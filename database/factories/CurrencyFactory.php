<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterMaking(function (Currency $currency): void {
            $currency->timestamps = false;
        });
    }

    /**
     * @return array{name: string, code: string, symbol: string, paypal_supported: bool, stripe_supported: bool}
     */
    public function definition(): array
    {
        $code = $this->faker->unique()->currencyCode();

        $name = [
            'AUD' => 'Dollars',
            'EUR' => 'Euro',
            'GBP' => 'Pounds',
            'JPY' => 'Yen',
            'USD' => 'Dollars',
        ][$code] ?? $code;

        return [
            'name' => $name,
            'code' => $code,
            'symbol' => $code,
            'paypal_supported' => $this->faker->boolean(70),
            'stripe_supported' => $this->faker->boolean(90),
        ];
    }

    public function euro(): static
    {
        return $this->state([
            'name' => 'Euro',
            'code' => 'EUR',
            'symbol' => 'EUR',
            'paypal_supported' => true,
            'stripe_supported' => true,
        ]);
    }

    public function usd(): static
    {
        return $this->state([
            'name' => 'Dollars',
            'code' => 'USD',
            'symbol' => '$',
            'paypal_supported' => true,
            'stripe_supported' => true,
        ]);
    }
}
