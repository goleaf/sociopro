<?php

namespace Tests\Feature;

use App\Enums\PaymentGatewayIdentifier;
use App\Models\Payment_gateway;
use App\Services\Payments\Gateways\Paypal;
use App\Services\Payments\Gateways\Paystack;
use App\Services\Payments\PaymentGatewayResolver;
use Tests\TestCase;

class PaymentGatewayResolverTest extends TestCase
{
    public function test_known_gateway_identifier_maps_to_enum_service_class(): void
    {
        $gateway = new Payment_gateway([
            'identifier' => PaymentGatewayIdentifier::Paypal->value,
            'model_name' => 'LegacyPaypal',
        ]);

        $resolver = app(PaymentGatewayResolver::class);

        $this->assertSame(Paypal::class, $resolver->serviceClass($gateway));
    }

    public function test_unknown_gateway_identifier_preserves_legacy_model_name_resolution(): void
    {
        $gateway = new Payment_gateway([
            'identifier' => 'custom_gateway',
            'model_name' => 'Custom Gateway',
        ]);

        $resolver = app(PaymentGatewayResolver::class);

        $this->assertSame(
            'App\Services\Payments\Gateways\CustomGateway',
            $resolver->serviceClass($gateway),
        );
    }

    public function test_gateway_methods_are_called_on_container_resolved_services(): void
    {
        FakeContainerResolvedPaymentGateway::$createCalls = [];

        $fakeGateway = new FakeContainerResolvedPaymentGateway;
        $this->app->instance(Paystack::class, $fakeGateway);

        $gateway = new Payment_gateway([
            'identifier' => PaymentGatewayIdentifier::Paystack->value,
            'model_name' => 'Paystack',
        ]);

        $resolver = app(PaymentGatewayResolver::class);

        $this->assertTrue($resolver->paymentStatus($gateway, PaymentGatewayIdentifier::Paystack->value, [
            'reference' => 'PSK-123',
        ]));
        $this->assertSame('https://payments.test/redirect', $resolver->createPayment(
            $gateway,
            PaymentGatewayIdentifier::Paystack->value,
        ));
        $this->assertSame([
            [
                'identifier' => PaymentGatewayIdentifier::Paystack->value,
                'transaction_keys' => ['reference' => 'PSK-123'],
            ],
        ], $fakeGateway->statusCalls);
        $this->assertSame([PaymentGatewayIdentifier::Paystack->value], FakeContainerResolvedPaymentGateway::$createCalls);
    }
}

class FakeContainerResolvedPaymentGateway
{
    /**
     * @var list<array{identifier: string, transaction_keys: array<string, mixed>}>
     */
    public array $statusCalls = [];

    /**
     * @var list<string>
     */
    public static array $createCalls = [];

    /**
     * @param  array<string, mixed>  $transactionKeys
     */
    public function payment_status(string $identifier, array $transactionKeys): bool
    {
        $this->statusCalls[] = [
            'identifier' => $identifier,
            'transaction_keys' => $transactionKeys,
        ];

        return true;
    }

    public static function payment_create(string $identifier): string
    {
        self::$createCalls[] = $identifier;

        return 'https://payments.test/redirect';
    }
}
