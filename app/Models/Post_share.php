<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post_share extends Model
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
            'post_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Posts, Post_share>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Posts::class, 'post_id', 'post_id');
    }
}
