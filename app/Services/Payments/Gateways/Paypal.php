<?php

namespace App\Services\Payments\Gateways;

use App\Exceptions\Payments\PaymentGatewayException;
use App\Models\Payment_gateway;
use Illuminate\Support\Facades\Http;
use Throwable;

class Paypal
{
    public static function payment_status(mixed $identifier, mixed $transaction_keys = []): bool
    {
        $paymentGateway = Payment_gateway::where('identifier', $identifier)->first();

        if (! $paymentGateway || empty($transaction_keys['payment_id'])) {
            return false;
        }

        $keys = json_decode($paymentGateway->keys, true) ?: [];
        [$clientId, $secretKey, $paypalUrl] = self::gatewayCredentials($paymentGateway, $keys);

        if (! $clientId || ! $secretKey) {
            report(PaymentGatewayException::missingCredentials('paypal'));

            return false;
        }

        try {
            $tokenResponse = Http::asForm()
                ->acceptJson()
                ->withBasicAuth($clientId, $secretKey)
                ->timeout(10)
                ->post($paypalUrl.'oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            $accessToken = $tokenResponse->json('access_token');

            if (! $accessToken) {
                return false;
            }

            $paymentResponse = Http::acceptJson()
                ->withToken($accessToken)
                ->timeout(10)
                ->get($paypalUrl.'payments/payment/'.$transaction_keys['payment_id']);
        } catch (Throwable $throwable) {
            report(PaymentGatewayException::transportFailure('paypal', $throwable));

            return false;
        }

        return $paymentResponse->json('state') === 'approved';
    }

    private static function gatewayCredentials(Payment_gateway $paymentGateway, array $keys): array
    {
        if ((int) $paymentGateway->test_mode === 1) {
            return [
                $keys['sandbox_client_id'] ?? null,
                $keys['sandbox_secret_key'] ?? null,
                'https://api.sandbox.paypal.com/v1/',
            ];
        }

        return [
            $keys['production_client_id'] ?? null,
            $keys['production_secret_key'] ?? null,
            'https://api.paypal.com/v1/',
        ];
    }
}
