<?php

namespace App\Services\Payments;

use App\Enums\PaymentGatewayIdentifier;
use App\Models\PaymentGateway;
use BadMethodCallException;
use Illuminate\Contracts\Container\Container;

class PaymentGatewayResolver
{
    public function __construct(private readonly Container $container) {}

    public function serviceClass(PaymentGateway $paymentGateway): string
    {
        $identifier = (string) $paymentGateway->getAttribute('identifier');

        return PaymentGatewayIdentifier::tryFrom($identifier)?->serviceClass()
            ?? $this->legacyServiceClass($paymentGateway);
    }

    public function service(PaymentGateway $paymentGateway): object
    {
        return $this->container->make($this->serviceClass($paymentGateway));
    }

    public function paymentStatus(PaymentGateway $paymentGateway, string $identifier, array $transactionKeys): bool
    {
        return (bool) $this->callGatewayMethod(
            $paymentGateway,
            'payment_status',
            [$identifier, $transactionKeys],
        );
    }

    public function createPayment(PaymentGateway $paymentGateway, string $identifier): mixed
    {
        return $this->callGatewayMethod(
            $paymentGateway,
            'payment_create',
            [$identifier],
        );
    }

    private function legacyServiceClass(PaymentGateway $paymentGateway): string
    {
        return 'App\Services\Payments\Gateways\\'.str_replace(
            ' ',
            '',
            (string) $paymentGateway->getAttribute('model_name'),
        );
    }

    private function callGatewayMethod(PaymentGateway $paymentGateway, string $method, array $parameters): mixed
    {
        $service = $this->service($paymentGateway);
        $callback = [$service, $method];

        if (! is_callable($callback)) {
            throw new BadMethodCallException(sprintf(
                'Payment gateway service [%s] does not define [%s].',
                $service::class,
                $method,
            ));
        }

        return $callback(...$parameters);
    }
}
