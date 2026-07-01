<?php

namespace App\Models\payment_gateway;

use DB;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePay extends Model
{
    use HasFactory;

    public static function payment_status(mixed $identifier, mixed $transaction_keys = []): bool
    {
        $payment_gateway = DB::table('payment_gateways')->where('identifier', $identifier)->first();
        $keys = json_decode($payment_gateway->keys, true);

        if ($payment_gateway->test_mode == 1) {
            $stripeSecretKey = $keys['secret_key'];
        } else {
            $stripeSecretKey = $keys['secret_live_key'];
        }

        $session_id = $transaction_keys['session_id'];
        if ($session_id != '') {
            Stripe::setApiKey($stripeSecretKey);
            $api_error = null;
            $checkout_session = null;

            try {
                $checkout_session = CheckoutSession::retrieve($session_id);
            } catch (Exception $e) {
                $api_error = $e->getMessage();
            }

            if (empty($api_error) && $checkout_session) {
                $intent = null;

                try {
                    $intent = PaymentIntent::retrieve($checkout_session->payment_intent);
                } catch (ApiErrorException $e) {
                    $api_error = $e->getMessage();
                }

                if ($intent) {
                    if ($intent->status == 'succeeded') {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        return false;
    }

    public static function payment_create(mixed $identifier)
    {
        $payment_gateway = DB::table('payment_gateways')->where('identifier', $identifier)->first();
        $payment_details = session('payment_details');
        $keys = json_decode($payment_gateway->keys, true);

        $products_name = '';
        foreach ($payment_details['items'] as $key => $value) {
            if ($key == 0) {
                $products_name .= $value['title'];
            } else {
                $products_name .= ', '.$value['title'];
            }
        }

        if ($payment_gateway->test_mode == 1) {
            $stripeSecretKey = $keys['secret_key'];
        } else {
            $stripeSecretKey = $keys['secret_live_key'];
        }

        Stripe::setApiKey($stripeSecretKey);
        header('Content-Type: application/json');

        $YOUR_DOMAIN = 'http://localhost:4242';

        $checkout_session = CheckoutSession::create([
            'line_items' => [
                [
                    'price_data' => [
                        'product_data' => [
                            'name' => get_phrase('Purchasing').' '.$products_name,
                        ],
                        'unit_amount' => round($payment_details['payable_amount'] * 100, 2),
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
