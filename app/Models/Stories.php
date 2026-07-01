<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stories extends Model
{
    use HasFactory;

    /** @var string */
    protected $primaryKey = 'story_id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'publisher', 'publisher_id', 'privacy', 'content_type', 'description', 'created_at', 'updated_at', 'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'publisher_id' => 'integer',
        ];
    }
}
