<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Group_member extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('is_accepted', '1');
    }

    public function getGroup(): BelongsTo
    {
        return $this->group();
    }

    public function getUser(): BelongsTo
    {
        return $this->user();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
