<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'user_id', 'publisher', 'publisher_id', 'post_type', 'privacy', 'tagged_user_ids', 'feel_and_activity', 'location', 'description', 'user_reacts', 'status', 'created_at', 'updated_at', 'album_image_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'publisher_id' => 'integer',
            'activity_id' => 'integer',
            'report_status' => 'integer',
            'posted_on' => 'datetime:Y-m-d H:i:s',
        ];
    }

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

    public function scopeForPublisher(Builder $query, string $publisher, int|string $publisherId): Builder
    {
        return $query
            ->where('posts.publisher', $publisher)
            ->where('posts.publisher_id', $publisherId);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('posts.privacy', Visibility::Public->value);
    }

    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('posts.user_id', $userId);
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

    /**
     * @return HasMany<Comments, Posts>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comments::class, 'id_of_type', 'post_id')
            ->where('is_type', 'post');
    }

    /**
     * @return HasMany<Report, Posts>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'post_id', 'post_id');
    }

    /**
     * @return HasMany<Post_share, Posts>
     */
    public function shares(): HasMany
    {
        return $this->hasMany(Post_share::class, 'post_id', 'post_id');
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saveforlaters', 'post_id', 'user_id')
            ->withPivot('id')
            ->withTimestamps();
    }
}
