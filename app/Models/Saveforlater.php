<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saveforlater extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    public function getVideo()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }
}
