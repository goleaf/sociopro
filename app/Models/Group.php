<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    public function getMember(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    public function getUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function followedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'group_id', 'user_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')
            ->withPivot('id', 'is_accepted', 'role')
            ->withTimestamps();
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saveforlaters', 'group_id', 'user_id')
            ->withPivot('id')
            ->withTimestamps();
    }
}
