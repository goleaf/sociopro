<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Live_streamings extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'streaming_id', 'publisher', 'publisher_id', 'user_id', 'details', 'created_at', 'updated_at',
    ];
}
