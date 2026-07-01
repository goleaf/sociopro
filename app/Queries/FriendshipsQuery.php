<?php

namespace App\Queries;

use App\Models\Friendships;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class FriendshipsQuery
{
    public static function acceptedForUser(User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Friendships::query()
            ->select(['id', 'requester', 'accepter', 'importance', 'is_accepted', 'accepted_at', 'created_at', 'updated_at'])
            ->where(function (Builder $query) use ($userId): void {
                $query->where('accepter', $userId)
                    ->orWhere('requester', $userId);
            })
            ->where('is_accepted', 1);
    }

    public static function importantForUser(User|int $user): Builder
    {
        return self::acceptedForUser($user)
            ->orderBy('friendships.importance', 'desc');
    }

    public static function recentForUser(User|int $user): Builder
    {
        return self::acceptedForUser($user)
            ->orderByDesc('friendships.id');
    }
}
