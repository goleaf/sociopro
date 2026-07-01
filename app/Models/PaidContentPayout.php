<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaidContentPayout extends Model
{
    use HasFactory;

    protected $table = 'paid_content_payouts';

    protected $fillable = [
        'user_id',
        'amount',
        'status',
    ];
}
