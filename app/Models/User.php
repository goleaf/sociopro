<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'user_name',
        'nickname',
        'username',
        'gender',
        'studied_at',
        'profession',
        'job',
        'marital_status',
        'date_of_birth',
        'photo',
        'about',
        'phone',
        'address',
        'cover_photo',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'integer',
            'email_verified_at' => 'datetime',
            'lastActive' => 'datetime',
        ];
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('user_role', UserRole::Admin->value);
    }

    public function scopeNonAdmins(Builder $query): Builder
    {
        return $query->where('user_role', '!=', UserRole::Admin->value);
    }

    public function isOnline(): bool
    {
        return Cache::has('user-is-online-'.$this->id);
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'block_users', 'user_id', 'block_user')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function blockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'block_users', 'block_user', 'user_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function followingUsers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'followers', 'user_id', 'follow_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function followedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'followers', 'follow_id', 'user_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function followedPages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'followers', 'user_id', 'page_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function followedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'followers', 'user_id', 'group_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function joinedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id')
            ->withPivot('id', 'is_accepted', 'role')
            ->withTimestamps();
    }

    public function likedPages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_likes', 'user_id', 'page_id')
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    public function savedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Marketplace::class, 'saved_products', 'user_id', 'product_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function savedVideos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'saveforlaters', 'user_id', 'video_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function savedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'saveforlaters', 'user_id', 'group_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function savedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Posts::class, 'saveforlaters', 'user_id', 'post_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function savedMarketplaceItems(): BelongsToMany
    {
        return $this->belongsToMany(Marketplace::class, 'saveforlaters', 'user_id', 'marketplace_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function savedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'saveforlaters', 'user_id', 'event_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function savedBlogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'saveforlaters', 'user_id', 'blog_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public static function get_user_image($file_name = '', $optimized = '')
    {
        $optimized = $optimized.'/';
        if (base_path('public/storage/userimage/'.$optimized.$file_name) && is_file('public/storage/userimage/'.$optimized.$file_name)) {
            return asset('storage/userimage/'.$optimized.$file_name);
        } else {
            return asset('storage/userimage/default.png');
        }
    }
}
