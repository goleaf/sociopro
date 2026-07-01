<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fundraiser_payout extends Model
{
    use HasFactory;

    protected $table = 'fundraiser_payouts';

    protected $fillable = [
        'user_id',
        'amount',
        'status',
    ];
}
