<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentGatewayIdentifier;
use App\Exceptions\Payments\PaymentGatewayException;
use App\Models\Payment_gateway;
use Illuminate\Support\Facades\Http;
use Throwable;

class Paystack
{
    private const SUCCESS_STATUS = 'success';

    public function payment_status(mixed $identifier = '', array $transaction_keys = []): bool
    {
        $reference = trim((string) ($transaction_keys['reference'] ?? ''));

        if ($reference === '') {
            return false;
        }

        $paymentGateway = Payment_gateway::where('identifier', $identifier)->first();

        if (! $paymentGateway) {
            return false;
        }

        $keys = $paymentGateway->decodedKeys();
        $secretKey = $this->secretKey($paymentGateway, $keys);

        if (! $secretKey) {
            report(PaymentGatewayException::missingCredentials(PaymentGatewayIdentifier::Paystack->value));

            return false;
        }

        try {
            $response = Http::acceptJson()
                ->withToken($secretKey)
                ->timeout(10)
                ->get('https://api.paystack.co/transaction/verify/'.rawurlencode($reference));
        } catch (Throwable $throwable) {
            report(PaymentGatewayException::transportFailure(PaymentGatewayIdentifier::Paystack->value, $throwable));

            return false;
        }

        return $response->json('status') === true
            && $response->json('data.status') === self::SUCCESS_STATUS;
    }

    private function secretKey(Payment_gateway $paymentGateway, array $keys): ?string
    {
        if ($paymentGateway->isInTestMode()) {
            return $keys['secret_test_key'] ?? null;
        }

        return $keys['secret_live_key'] ?? null;
    }
}
