<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album_image extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'album_id' => 'integer',
            'user_id' => 'integer',
            'page_id' => 'integer',
            'group_id' => 'integer',
        ];
    }
}
