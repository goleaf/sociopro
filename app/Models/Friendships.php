<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendships extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'requester', 'accepter', 'importance', 'is_accepted', 'accepted_at', 'created_at', 'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requester' => 'integer',
            'accepter' => 'integer',
            'importance' => 'integer',
            'is_accepted' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, Friendships>
     */
    public function getFriend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester');
    }

    /**
     * @return BelongsTo<User, Friendships>
     */
    public function getFriendAccepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepter');
    }

    public function otherUserId(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        $accepterId = (int) $this->getAttribute('accepter');

        return $accepterId === (int) $userId
            ? (int) $this->getAttribute('requester')
            : $accepterId;
    }
}
