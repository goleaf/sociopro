<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaidContentPackages extends Model
{
    use HasFactory;

    protected $table = 'paid_content_packages';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'price',
    ];
}
