<?php

namespace App\Services\Payments\Gateways;

use App\Models\PaymentGateway;
use App\Models\Users;
use App\Support\Money\Money;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class Razorpay
{
    public static function payment_status(mixed $identifier, mixed $transaction_keys = []): bool
    {
        if ($transaction_keys != '') {
            array_shift($transaction_keys);
            session(['keys' => $transaction_keys]);

            return true;
        }

        return false;
    }

    public static function payment_create(mixed $identifier): array
    {
        $payment_details = session('payment_details');
        $user = Users::query()->where('id', auth()->id())->first();
        $model = $payment_details['success_method']['model_name'];
        $description = '';

        if ($model == 'AuthorPayout' || $model == 'CampaignPayout') {
            $settings = Users::query()
                ->where('id', $payment_details['custom_field']['user_id'])
                ->value('payment_settings');
            $keys = json_decode($settings);

            $public_key = $keys->raz_key_id;
            $secret_key = $keys->raz_secret_key;
            $color = $keys->theme_color;

            if ($model == 'AuthorPayout') {
                $description = 'Authors payment.';
            } elseif ($model == 'CampaignPayout') {
                $description = 'Campaign payment.';
            }
        } elseif ($model == 'Subscription' || $model == 'Sponsor' || $model == 'Donation' || $model == 'Job' || 'Badge') {
            $payment_gateway = PaymentGateway::query()
                ->where('identifier', $identifier)
                ->first();
            $keys = $payment_gateway->decodedKeys();

            $public_key = $keys['public_key'];
            $secret_key = $keys['secret_key'];
            $color = '';

            if ($model == 'Sponsor') {
                $description = 'Ads payment.';
            } elseif ($model == 'Subscription') {
                $description = 'Author Subscription.';
            } elseif ($model == 'Donation') {
                $description = 'Donation on a campaign.';
            } elseif ($model == 'Job') {
                $description = 'Job Payment.';
            } elseif ($model == 'Badge') {
                $description = 'Badge Payment.';
            }
        }

        $receipt_id = Str::random(20);
        $api = new Api($public_key, $secret_key);
        $amountMinorUnits = Money::toMinorUnits($payment_details['items'][0]['price']);

        $order = $api->order->create([
            'receipt' => $receipt_id,
            'amount' => $amountMinorUnits,
            'currency' => 'USD',
        ]);

        $page_data = [
            'order_id' => $order['id'],
            'razorpay_id' => $public_key,
            'amount' => $amountMinorUnits,

            'name' => $user->name,
            'currency' => 'USD',
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'description' => $description,
        ];

        $data = [
            'page_data' => $page_data,
            'color' => $color,
            'payment_details' => $payment_details,
        ];

        return $data;
    }
}
