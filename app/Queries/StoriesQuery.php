<?php

namespace App\Queries;

use App\Models\Stories;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StoriesQuery
{
    public static function visibleFor(User|int $viewer, int $seconds = 86400): Builder
    {
        $viewerId = $viewer instanceof User ? (int) $viewer->id : (int) $viewer;

        return self::withOwnerColumns()
            ->where(function (Builder $query) use ($viewerId): void {
                $query->where(function (Builder $query) use ($viewerId): void {
                    $query->whereJsonContains('users.friends', [$viewerId])
                        ->where('stories.privacy', '!=', 'private');
                })->orWhere('stories.user_id', $viewerId);
            })
            ->where('stories.status', 'active')
            ->where('stories.created_at', '>=', time() - $seconds)
            ->orderByDesc('stories.story_id');
    }

    public static function findWithOwner(int|string $storyId): ?Stories
    {
        return self::withOwnerColumns()
            ->where('stories.story_id', $storyId)
            ->first();
    }

    private static function withOwnerColumns(): Builder
    {
        return Stories::query()
            ->join('users', 'stories.user_id', '=', 'users.id')
            ->select([
                'stories.*',
                'users.name',
                'users.photo',
                'users.friends',
                'stories.created_at as created_at',
            ]);
    }
}
