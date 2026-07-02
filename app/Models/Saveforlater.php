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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getVideo(): BelongsTo
    {
        return $this->video();
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Posts::class, 'post_id', 'post_id');
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class, 'marketplace_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class, 'blog_id');
    }
}
