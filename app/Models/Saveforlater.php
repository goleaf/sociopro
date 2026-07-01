<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Saveforlater extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'video_id' => 'integer',
            'group_id' => 'integer',
            'post_id' => 'integer',
            'marketplace_id' => 'integer',
            'event_id' => 'integer',
            'blog_id' => 'integer',
        ];
    }

    public function getVideo(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'video_id');
    }
}
