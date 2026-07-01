<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fundraiser_category extends Model
{
    use HasFactory;

    protected $table = 'fundraiser_categories';

    protected $fillable = [
        'name',
    ];
}
