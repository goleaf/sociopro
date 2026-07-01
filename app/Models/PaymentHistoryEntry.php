<?php

namespace App\Models;

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
        'transaction_keys',
        'order_id',
        'status',
        'transaction_id',
    ];
}
