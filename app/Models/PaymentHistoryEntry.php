<?php

namespace App\Models;

use App\Enums\PaytmTransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistoryEntry extends Model
{
    use HasFactory;

    protected $table = 'payment_histories';

    protected $fillable = [
        'item_type',
        'item_id',
        'user_id',
        'amount',
        'currency',
        'identifier',
    ];

    protected $hidden = [
        'transaction_keys',
        'order_id',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaytmTransactionStatus::class,
        ];
    }
}
