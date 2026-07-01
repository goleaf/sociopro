<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    public function getMember(): HasMany
    {
        return $this->hasMany(Group_member::class, 'group_id');
    }

    public function getUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
