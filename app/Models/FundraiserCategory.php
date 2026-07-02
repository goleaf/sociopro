<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundraiserCategory extends Model
{
    use HasFactory;

    protected $table = 'fundraiser_categories';

    protected $fillable = [
        'name',
    ];
}
