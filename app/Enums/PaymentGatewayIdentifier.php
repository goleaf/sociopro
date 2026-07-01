<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;
use App\Services\Payments\Gateways\Flutterwave;
use App\Services\Payments\Gateways\Paypal;
use App\Services\Payments\Gateways\Paystack;
use App\Services\Payments\Gateways\Paytm;
use App\Services\Payments\Gateways\Razorpay;
use App\Services\Payments\Gateways\StripePay;

enum PaymentGatewayIdentifier: string
{
    use HasValues;

    case Stripe = 'stripe';
    case Razorpay = 'razorpay';
    case Flutterwave = 'flutterwave';
    case Paypal = 'paypal';
    case Paystack = 'paystack';
    case Paytm = 'paytm';

    public function serviceClass(): string
    {
        return match ($this) {
            self::Stripe => StripePay::class,
            self::Razorpay => Razorpay::class,
            self::Flutterwave => Flutterwave::class,
            self::Paypal => Paypal::class,
            self::Paystack => Paystack::class,
            self::Paytm => Paytm::class,
        };
    }
}
