<?php

namespace Database\Factories;

use App\Enums\PaymentGatewayIdentifier;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    protected $model = PaymentGateway::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' => PaymentGatewayIdentifier::Stripe->value,
            'currency' => 'USD',
            'title' => 'Stripe',
            'description' => 'Stripe payment gateway.',
            'keys' => [],
            'model_name' => 'StripePay',
            'test_mode' => 1,
            'status' => 1,
            'is_addon' => 0,
        ];
    }

    public function stripe(): static
    {
        return $this->state([
            'identifier' => PaymentGatewayIdentifier::Stripe->value,
            'title' => 'Stripe',
            'model_name' => 'StripePay',
        ]);
    }

    public function razorpay(): static
    {
        return $this->state([
            'identifier' => PaymentGatewayIdentifier::Razorpay->value,
            'title' => 'Razorpay',
            'model_name' => 'Razorpay',
        ]);
    }

    public function flutterwave(): static
    {
        return $this->state([
            'identifier' => PaymentGatewayIdentifier::Flutterwave->value,
            'title' => 'Flutterwave',
            'model_name' => 'Flutterwave',
        ]);
    }

    public function paypal(): static
    {
        return $this->state([
            'identifier' => PaymentGatewayIdentifier::Paypal->value,
            'title' => 'Paypal',
            'model_name' => 'Paypal',
        ]);
    }

    public function paystack(): static
    {
        return $this->state([
            'identifier' => PaymentGatewayIdentifier::Paystack->value,
            'title' => 'Paystack',
            'model_name' => 'Paystack',
        ]);
    }

    public function paytm(): static
    {
        return $this->state([
            'identifier' => PaymentGatewayIdentifier::Paytm->value,
            'title' => 'Paytm',
            'model_name' => 'Paytm',
        ]);
    }
}
