<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentGatewayIdentifier;
use App\Exceptions\Payments\PaymentGatewayException;
use App\Models\Payment_gateway;
use App\Support\Money\Money;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Throwable;

class StripePay
{
    private const SUCCEEDED_STATUS = 'succeeded';

    public static function payment_status(mixed $identifier, mixed $transaction_keys = []): bool
    {
        $payment_gateway = Payment_gateway::query()->where('identifier', $identifier)->first();
        $keys = $payment_gateway->decodedKeys();

        if ($payment_gateway->isInTestMode()) {
            $stripeSecretKey = $keys['secret_key'];
        } else {
            $stripeSecretKey = $keys['secret_live_key'];
        }

        $session_id = $transaction_keys['session_id'];
        if ($session_id != '') {
            Stripe::setApiKey($stripeSecretKey);

            try {
                $checkout_session = CheckoutSession::retrieve($session_id);
            } catch (Throwable $throwable) {
                report(PaymentGatewayException::transportFailure(PaymentGatewayIdentifier::Stripe->value, $throwable));

                return false;
            }

            try {
                $intent = PaymentIntent::retrieve($checkout_session->payment_intent);
            } catch (ApiErrorException $throwable) {
                report(PaymentGatewayException::transportFailure(PaymentGatewayIdentifier::Stripe->value, $throwable));

                return false;
            }

            return $intent->status == self::SUCCEEDED_STATUS;
        } else {
            return false;
        }

        return false;
    }

    public static function payment_create(mixed $identifier)
    {
        $payment_gateway = Payment_gateway::query()->where('identifier', $identifier)->first();
        $payment_details = session('payment_details');
        $keys = $payment_gateway->decodedKeys();

        $products_name = '';
        foreach ($payment_details['items'] as $key => $value) {
            if ($key == 0) {
                $products_name .= $value['title'];
            } else {
                $products_name .= ', '.$value['title'];
            }
        }

        if ($payment_gateway->isInTestMode()) {
            $stripeSecretKey = $keys['secret_key'];
        } else {
            $stripeSecretKey = $keys['secret_live_key'];
        }

        Stripe::setApiKey($stripeSecretKey);
        header('Content-Type: application/json');

        $checkout_session = CheckoutSession::create([
            'line_items' => [
                [
                    'price_data' => [
                        'product_data' => [
                            'name' => get_phrase('Purchasing').' '.$products_name,
                        ],
                        'unit_amount' => Money::toMinorUnits($payment_details['payable_amount']),
                        'currency' => $payment_gateway->currency,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $payment_details['success_url'].'/'.$identifier.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $payment_details['cancel_url'],
        ]);

        return $checkout_session->url;
    }
}
