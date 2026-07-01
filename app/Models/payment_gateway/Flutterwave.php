<?php

namespace App\Models\payment_gateway;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flutterwave extends Model
{
    use HasFactory;

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
