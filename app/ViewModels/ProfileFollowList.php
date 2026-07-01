<?php

namespace App\ViewModels;

use App\Models\Follower;
use App\Models\User;
use Illuminate\Support\Collection;

final class ProfileFollowList
{
    public static function forUser(User $user): array
    {
        return [
            'followers' => self::build($user, 'follow_id', 'user_id'),
            'following' => self::build($user, 'user_id', 'follow_id'),
        ];
    }

    private static function build(User $user, string $ownerColumn, string $profileColumn): array
    {
        $rows = Follower::query()
            ->select(['id', 'user_id', 'follow_id', 'created_at'])
            ->where($ownerColumn, $user->id)
            ->orderByDesc('created_at')
            ->get();

        $profileIds = $rows
            ->pluck($profileColumn)
            ->filter(fn ($profileId): bool => (int) $profileId !== (int) $user->id)
            ->unique()
            ->values();

        $profiles = self::profiles($profileIds);
        $followedProfileIds = self::followedProfileIds($user, $profileIds);
        $viewerFriendIds = self::friendIds($user->friends);

        return [
            'count' => $rows->count(),
            'items' => $profileIds
                ->map(fn ($profileId) => $profiles->get((int) $profileId))
                ->filter()
                ->map(fn (User $profile): array => [
                    'user' => $profile,
                    'is_following' => $followedProfileIds->contains((int) $profile->id),
                    'mutual_friends' => self::friendIds($profile->friends)->intersect($viewerFriendIds)->count(),
                ])
                ->values(),
        ];
    }

    private static function profiles(Collection $profileIds): Collection
    {
        if ($profileIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->select(['id', 'name', 'photo', 'friends'])
            ->whereIn('id', $profileIds)
            ->get()
            ->keyBy(fn (User $profile): int => (int) $profile->id);
    }

    private static function followedProfileIds(User $user, Collection $profileIds): Collection
    {
        if ($profileIds->isEmpty()) {
            return collect();
        }

        return Follower::query()
            ->select(['follow_id'])
            ->where('user_id', $user->id)
            ->whereIn('follow_id', $profileIds)
            ->pluck('follow_id')
            ->map(fn ($profileId): int => (int) $profileId);
    }

    private static function friendIds(mixed $friends): Collection
    {
        $decodedFriends = is_string($friends) ? json_decode($friends, true) : $friends;

        return collect(is_array($decodedFriends) ? $decodedFriends : [])
            ->map(fn ($friendId): int => (int) $friendId)
            ->filter()
            ->values();
    }
}
