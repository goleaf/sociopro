<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Posts extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** @var string */
    protected $primaryKey = 'post_id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'post_id', 'user_id', 'publisher', 'publisher_id', 'post_type', 'privacy', 'tagged_user_ids', 'feel_and_activity', 'location', 'description', 'user_reacts', 'status', 'created_at', 'updated_at', 'album_image_id',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('posts.status', ContentStatus::Active->value);
    }

    public function scopeNotPrivate(Builder $query): Builder
    {
        return $query->where('posts.privacy', '!=', Visibility::Private->value);
    }

    public function scopeNotReported(Builder $query): Builder
    {
        return $query->where('posts.report_status', '0');
    }

    /**
     * @return BelongsTo<User, Posts>
     */
    public function getUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Media_files, Posts>
     */
    public function media_files(): HasMany
    {
        return $this->hasMany(Media_files::class, 'post_id', 'post_id');
    }
}
