<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
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
            'group_id' => 'integer',
            'publisher_id' => 'integer',
        ];
    }

    public function getUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inviteEvent(): HasMany
    {
        return $this->hasMany(Invite::class, 'event_id');
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saveforlaters', 'event_id', 'user_id')
            ->withPivot('id')
            ->withTimestamps();
    }
}
