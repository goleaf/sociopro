<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApply extends Model
{
    use HasFactory;

    protected $table = 'job_applies';

    protected $fillable = [
        'job_id',
        'owner_id',
        'user_id',
        'email',
        'phone',
        'attachment',
    ];
}
