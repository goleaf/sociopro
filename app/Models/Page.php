<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Page extends Model
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
            'category_id' => 'integer',
        ];
    }

    public function getCategory(): BelongsTo
    {
        return $this->belongsTo(Pagecategory::class, 'category_id');
    }

    public function getUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function followedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'page_id', 'user_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'page_likes', 'page_id', 'user_id')
            ->withPivot('id', 'role')
            ->withTimestamps();
    }
}
