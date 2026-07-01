<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaidContentCreator extends Model
{
    protected $table = 'paid_content_creators';

    protected $fillable = [
        'user_id',
        'description',
        'bio',
        'social_accounts',
    ];
}
