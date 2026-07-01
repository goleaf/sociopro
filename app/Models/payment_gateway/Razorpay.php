<?php

namespace App\Models\payment_gateway;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class Razorpay extends Model
{
    use HasFactory;

    public static function payment_status($identifier, $transaction_keys = [])
    {
        if ($transaction_keys != '') {
            array_shift($transaction_keys);
            session(['keys' => $transaction_keys]);
            return true;
        }return false;
    }
    public static function payment_create($identifier)
    {
        $payment_details = session('payment_details');
        $user = DB::table('users')->where('id', auth()->user()->id)->first();
        $model = $payment_details['success_method']['model_name'];

        if ($model == 'AuthorPayout' || $model == 'CampaignPayout') {
            $settings = DB::table('users')->where('id', $payment_details['custom_field']['user_id'])
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
            $payment_gateway = DB::table('payment_gateways')
                ->where('identifier', $identifier)
                ->first();
            $keys = json_decode($payment_gateway->keys, true);

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

        $order = $api->order->create(array(
            'receipt' => $receipt_id,
            'amount' => $payment_details['items'][0]['price'] * 100,
            'currency' => 'USD',
        ));

        $page_data = [
            'order_id' => $order['id'],
            'razorpay_id' => $public_key,
            'amount' => $payment_details['items'][0]['price'] * 100,

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
