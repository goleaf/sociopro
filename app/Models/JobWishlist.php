<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobWishlist extends Model
{
    use HasFactory;

    protected $table = 'job_wishlists';

    protected $fillable = [
        'user_id',
        'job_id',
    ];
}
