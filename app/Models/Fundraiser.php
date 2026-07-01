<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fundraiser extends Model
{
    use HasFactory;

    protected $table = 'fundraisers';

    protected $fillable = [
        'user_id',
        'categories_id',
        'title',
        'description',
        'image',
        'goal_amount',
        'raised_amount',
        'status',
        'invited',
    ];
}
