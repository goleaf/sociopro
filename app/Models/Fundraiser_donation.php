<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fundraiser_donation extends Model
{
    use HasFactory;

    protected $table = 'fundraiser_donations';

    protected $fillable = [
        'fundraiser_id',
        'doner_id',
        'amount',
        'status',
    ];
}
