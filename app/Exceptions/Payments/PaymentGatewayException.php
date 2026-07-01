<?php

namespace App\Exceptions\Payments;

use RuntimeException;
use Throwable;

class PaymentGatewayException extends RuntimeException
{
    public function __construct(
        private readonly string $gateway,
        private readonly string $reason,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function missingCredentials(string $gateway): self
    {
        return new self(
            $gateway,
            'missing_credentials',
            sprintf('Payment gateway [%s] is missing required credentials.', $gateway)
        );
    }

    public static function transportFailure(string $gateway, Throwable $previous): self
    {
        return new self(
            $gateway,
            'transport_failure',
            sprintf('Payment gateway [%s] request failed.', $gateway),
            $previous
        );
    }

    public function gateway(): string
    {
        return $this->gateway;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
