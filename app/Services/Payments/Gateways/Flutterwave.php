<?php

namespace App\Services\Payments\Gateways;

class Flutterwave
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
}
