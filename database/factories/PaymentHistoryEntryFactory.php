<?php

namespace Database\Factories;

use App\Enums\PaymentGatewayIdentifier;
use App\Enums\PaytmTransactionStatus;
use App\Models\PaymentHistoryEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentHistoryEntry>
 */
class PaymentHistoryEntryFactory extends Factory
{
    protected $model = PaymentHistoryEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_type' => 'test',
            'item_id' => $this->faker->numberBetween(1, 1000),
            'user_id' => User::factory(),
            'amount' => '19.50',
            'currency' => 'USD',
            'identifier' => PaymentGatewayIdentifier::Paytm->value,
            'transaction_keys' => [],
            'order_id' => 'ORDER-'.Str::upper(Str::random(8)),
            'transaction_id' => null,
            'status' => PaytmTransactionStatus::Open->value,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
