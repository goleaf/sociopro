<?php

namespace App\Models;

use App\Enums\MediaFileType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaFile extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'post_id', 'story_id', 'album_id', 'file_name', 'product_id', 'page_id', 'group_id', 'chat_id', 'file_type', 'privacy', 'created_at', 'updated_at', 'album_image_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'post_id' => 'integer',
            'story_id' => 'integer',
            'album_id' => 'integer',
            'product_id' => 'integer',
            'page_id' => 'integer',
            'group_id' => 'integer',
            'chat_id' => 'integer',
            'album_image_id' => 'integer',
        ];
    }

    public function scopeOfType(Builder $query, string|MediaFileType $type): Builder
    {
        $type = $type instanceof MediaFileType ? $type->value : $type;

        return $query->where('file_type', $type);
    }

    /**
     * @return BelongsTo<Posts, MediaFile>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Posts::class, 'post_id', 'post_id');
    }
}
