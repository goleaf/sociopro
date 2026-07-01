<?php

namespace App\ViewModels;

use App\Models\Comments;
use App\Models\Feeling_and_activities;
use App\Models\Friendships;
use App\Models\Album_image;
use App\Models\Albums;
use App\Models\BlockUser;
use App\Models\Blog;
use App\Models\Event;
use App\Models\Follower;
use App\Models\Group;
use App\Models\Group_member;
use App\Models\Invite;
use App\Models\Chat;
use App\Models\Media_files;
use App\Models\Page;
use App\Models\PaidContentCreator;
use App\Models\Post_share;
use App\Models\Posts;
use App\Models\Saveforlater;
use App\Models\Setting;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class BladeViewData
{
    private array $cache = [];

    public function postCommentCount(Model $post, string $type = 'post'): int
    {
        $contentId = $this->commentContentId($post, $type);

        return $this->remember("comment-count:{$type}:{$contentId}", fn (): int => Comments::query()
            ->where('is_type', $type)
            ->where('id_of_type', $contentId)
            ->count());
    }

    public function rootComments(Model $post, string $type = 'post'): Collection
    {
        $contentId = $this->commentContentId($post, $type);

        return $this->remember("root-comments:{$type}:{$contentId}", fn (): Collection => $this->withCommentUsers(
            Comments::query()
                ->select(['comment_id', 'user_id', 'parent_id', 'is_type', 'id_of_type', 'description', 'user_reacts', 'created_at', 'updated_at'])
                ->where('is_type', $type)
                ->where('id_of_type', $contentId)
                ->where('parent_id', 0)
                ->orderByDesc('comment_id')
                ->take(1)
                ->get()
        ));
    }

    public function childCommentCount(Model $comment, string $type): int
    {
        return $this->remember("child-comment-count:{$type}:{$comment->comment_id}", fn (): int => Comments::query()
            ->where('is_type', $type)
            ->where('parent_id', $comment->comment_id)
            ->count());
    }

    public function childComments(Model $comment): Collection
    {
        return $this->remember("child-comments:{$comment->comment_id}", fn (): Collection => $this->withCommentUsers(
            Comments::query()
                ->select(['comment_id', 'user_id', 'parent_id', 'is_type', 'id_of_type', 'description', 'user_reacts', 'created_at', 'updated_at'])
                ->where('parent_id', $comment->comment_id)
                ->orderByDesc('comment_id')
                ->take(1)
                ->get()
        ));
    }

    public function reacts(Model $model): array
    {
        return json_decode($model->user_reacts ?? '[]', true) ?: [];
    }

    public function taggedUserIds(Model $post): array
    {
        return json_decode($post->tagged_user_ids ?? '[]', true) ?: [];
    }

    public function userName(int|string|null $userId): string
    {
        if (! $userId) {
            return '';
        }

        return $this->remember("user-name:{$userId}", fn (): string => (string) User::query()
            ->where('id', $userId)
            ->value('name'));
    }

    public function user(int|string|null $userId): ?User
    {
        if (! $userId) {
            return null;
        }

        return $this->remember("user:{$userId}", fn (): ?User => User::query()
            ->select(['id', 'name', 'photo', 'lastActive'])
            ->find($userId));
    }

    public function feelingActivity(int|string|null $activityId): ?Feeling_and_activities
    {
        if (! $activityId) {
            return null;
        }

        return $this->remember("feeling-activity:{$activityId}", fn (): ?Feeling_and_activities => Feeling_and_activities::query()
            ->select(['feeling_and_activity_id', 'type', 'title', 'icon'])
            ->where('feeling_and_activity_id', $activityId)
            ->first());
    }

    public function postMediaFiles(Model $post): Collection
    {
        return $this->remember("post-media:{$post->post_id}", fn (): Collection => Media_files::query()
            ->select(['id', 'post_id', 'file_name', 'file_type', 'album_image_id'])
            ->where('post_id', $post->post_id)
            ->get());
    }

    public function postMediaFileCount(Model $post): int
    {
        return $this->postMediaFiles($post)->count();
    }

    public function moreUnloadedImages(Model $post): int
    {
        return $this->postMediaFileCount($post) - 5;
    }

    public function locationVisitCount(?string $location): int
    {
        if (! $location) {
            return 0;
        }

        return $this->remember("location-visits:{$location}", fn (): int => Posts::query()
            ->where('location', $location)
            ->count());
    }

    public function profileMediaFiles(?int $userId): Collection
    {
        if (! $userId) {
            return collect();
        }

        return $this->remember("profile-media:{$userId}", fn (): Collection => Media_files::query()
            ->select(['id', 'user_id', 'post_id', 'file_name', 'file_type'])
            ->where('user_id', $userId)
            ->whereNull('story_id')
            ->whereNull('product_id')
            ->whereNull('page_id')
            ->whereNull('group_id')
            ->whereNull('chat_id')
            ->orderByDesc('id')
            ->take(9)
            ->get());
    }

    public function profileFriends(User $user, int $limit = 6): array
    {
        return $this->remember("profile-friends:{$user->id}:{$limit}", function () use ($user, $limit): array {
            $friendships = Friendships::query()
                ->select(['id', 'requester', 'accepter', 'is_accepted', 'importance'])
                ->where(function ($query) use ($user): void {
                    $query->where('accepter', $user->id)->orWhere('requester', $user->id);
                })
                ->where('is_accepted', 1)
                ->orderByDesc('importance')
                ->get();

            $friendIds = $friendships
                ->map(fn (Friendships $friendship): int => (int) ($friendship->requester == $user->id ? $friendship->accepter : $friendship->requester))
                ->unique()
                ->values();

            $friends = User::query()
                ->select(['id', 'name', 'photo'])
                ->whereIn('id', $friendIds)
                ->limit($limit)
                ->get();

            return [
                'count' => $friendships->count(),
                'items' => $friends,
            ];
        });
    }

    public function friendshipRows(iterable $friendships, User $owner, ?User $viewer = null, bool $skipBlocked = false): Collection
    {
        $friendships = collect($friendships);
        $friendIds = $friendships
            ->map(fn (Friendships $friendship): int => (int) ($friendship->requester == $owner->id ? $friendship->accepter : $friendship->requester))
            ->unique()
            ->values();

        $users = User::query()
            ->select(['id', 'name', 'photo', 'friends', 'lastActive'])
            ->whereIn('id', $friendIds)
            ->get()
            ->keyBy('id');

        return $friendships
            ->map(function (Friendships $friendship) use ($owner, $viewer, $users): ?array {
                $friendId = (int) ($friendship->requester == $owner->id ? $friendship->accepter : $friendship->requester);
                $user = $users->get($friendId);

                if (! $user || ($viewer && $user->id === $viewer->id)) {
                    return null;
                }

                return [
                    'friendship' => $friendship,
                    'user' => $user,
                    'mutual_count' => $this->mutualFriendCount($user, $viewer),
                    'is_following' => $this->isFollowing($user->id, $viewer),
                    'is_blocked' => $this->isBlockedUserId($user->id, $viewer),
                ];
            })
            ->filter(fn (?array $row): bool => $row !== null && (! $skipBlocked || ! $row['is_blocked']))
            ->values();
    }

    public function friendRequestRows(iterable $friendRequests, ?User $viewer): Collection
    {
        $friendRequests = collect($friendRequests);
        $requesterIds = $friendRequests->pluck('requester')->unique()->values();
        $users = User::query()
            ->select(['id', 'name', 'photo', 'friends'])
            ->whereIn('id', $requesterIds)
            ->get()
            ->keyBy('id');

        return $friendRequests
            ->map(function (Friendships $friendRequest) use ($viewer, $users): ?array {
                $user = $users->get($friendRequest->requester);

                if (! $user) {
                    return null;
                }

                return [
                    'request' => $friendRequest,
                    'user' => $user,
                    'mutual_count' => $this->mutualFriendCount($user, $viewer),
                ];
            })
            ->filter()
            ->values();
    }

    public function suggestedFriendRows(iterable $users, ?User $viewer, int|string|null $currentProfileId = null): Collection
    {
        if (! $viewer) {
            return collect();
        }

        return collect($users)
            ->filter(fn (User $user): bool => $user->id !== $viewer->id)
            ->filter(fn (User $user): bool => ! $this->isFriendId($user->id, $viewer))
            ->filter(fn (User $user): bool => ! $this->hasFriendshipRequest($viewer->id, $user->id))
            ->filter(fn (User $user): bool => ! $this->hasFriendshipRequest($user->id, $viewer->id))
            ->filter(fn (User $user): bool => ! $currentProfileId || $user->id != $currentProfileId)
            ->values();
    }

    public function blockedUserRows(?User $viewer): Collection
    {
        if (! $viewer) {
            return collect();
        }

        $blocks = BlockUser::query()
            ->where('user_id', $viewer->id)
            ->get();

        $users = User::query()
            ->select(['id', 'name', 'photo'])
            ->whereIn('id', $blocks->pluck('block_user')->unique())
            ->get()
            ->keyBy('id');

        return $blocks
            ->map(function (BlockUser $block) use ($users): ?array {
                $user = $users->get($block->block_user);

                return $user ? ['block' => $block, 'user' => $user] : null;
            })
            ->filter()
            ->values();
    }

    public function acceptedFriends(User $user, int $limit = 6): array
    {
        return $this->profileFriends($user, $limit);
    }

    public function shareFriendRows(?User $viewer): Collection
    {
        if (! $viewer) {
            return collect();
        }

        $friendships = Friendships::query()
            ->where(function ($query) use ($viewer): void {
                $query->where('accepter', $viewer->id)->orWhere('requester', $viewer->id);
            })
            ->where('is_accepted', 1)
            ->orderByDesc('importance')
            ->get();

        return $this->friendshipRows($friendships, $viewer, $viewer);
    }

    public function shareGroupRows(?User $viewer): Collection
    {
        if (! $viewer) {
            return collect();
        }

        return $this->remember("share-groups:{$viewer->id}", fn (): Collection => Group_member::query()
            ->with('getGroup:id,title,logo')
            ->where('user_id', $viewer->id)
            ->where('is_accepted', '1')
            ->get());
    }

    public function friendshipStatus(User $target, ?User $viewer): array
    {
        if (! $viewer || $target->id === $viewer->id) {
            return [
                'exists' => false,
                'accepted' => false,
                'requester_id' => null,
                'is_following' => false,
            ];
        }

        $friendship = $this->remember("friendship-status:{$viewer->id}:{$target->id}", fn (): ?Friendships => Friendships::query()
            ->where(function ($query) use ($viewer, $target): void {
                $query->where('requester', $viewer->id)->where('accepter', $target->id);
            })
            ->orWhere(function ($query) use ($viewer, $target): void {
                $query->where('accepter', $viewer->id)->where('requester', $target->id);
            })
            ->first());

        return [
            'exists' => $friendship !== null,
            'accepted' => (bool) ($friendship?->is_accepted == 1),
            'requester_id' => $friendship?->requester,
            'is_following' => $this->isFollowing($target->id, $viewer),
        ];
    }

    public function profileIdentifier(?string $pageIdentifier): string
    {
        return $pageIdentifier ?: 'user';
    }

    public function profilePronouns(User $user): array
    {
        $gender = $user->gender === 'female' ? 'her' : 'his';

        return [
            'gender' => $gender,
            'pronoun' => $gender === 'his' ? 'he' : 'she',
        ];
    }

    public function canViewProfile(User $profile, ?User $viewer): bool
    {
        return empty($profile->profile_status)
            || $profile->profile_status === 'unlock'
            || $viewer?->id === $profile->id
            || $this->isFriendId($profile->id, $viewer);
    }

    public function mutualFriendCount(?User $user, ?User $viewer): int
    {
        if (! $user || ! $viewer) {
            return 0;
        }

        $friendFriends = json_decode($user->friends ?? '[]', true);
        $viewerFriends = json_decode($viewer->friends ?? '[]', true);

        return count(array_intersect(
            is_array($friendFriends) ? $friendFriends : [],
            is_array($viewerFriends) ? $viewerFriends : []
        ));
    }

    public function isFollowing(int|string|null $targetId, ?User $viewer): bool
    {
        if (! $targetId || ! $viewer) {
            return false;
        }

        return $this->remember("following:{$viewer->id}:{$targetId}", fn (): bool => Follower::query()
            ->where('user_id', $viewer->id)
            ->where('follow_id', $targetId)
            ->exists());
    }

    public function rightSidebarHour(?User $user): int
    {
        return (int) now($user?->timezone ?: config('app.timezone'))->format('H');
    }

    public function activeSponsors(int $limit = 6): Collection
    {
        return $this->remember("active-sponsors:{$limit}", fn (): Collection => Sponsor::query()
            ->where('status', 1)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderByDesc('id')
            ->limit($limit)
            ->get());
    }

    public function onlineFriendRows(?User $viewer): Collection
    {
        if (! $viewer) {
            return collect();
        }

        return $this->shareFriendRows($viewer)
            ->filter(fn (array $row): bool => ! $row['is_blocked'] && method_exists($row['user'], 'isOnline') && $row['user']->isOnline())
            ->values();
    }

    public function eventGuestStats(Model $event, int $invitedFriendGoing = 0): array
    {
        $goingUsers = json_decode($event->going_users_id ?? '[]', true);
        $interestedUsers = json_decode($event->interested_users_id ?? '[]', true);

        return [
            'going' => count(is_array($goingUsers) ? $goingUsers : []) + $invitedFriendGoing,
            'interested' => count(is_array($interestedUsers) ? $interestedUsers : []),
        ];
    }

    public function eventInviteRows(iterable $friendships, Model $event, User $viewer): Collection
    {
        $rows = $this->friendshipRows($friendships, $viewer, $viewer, true);
        $invites = Invite::query()
            ->where('event_id', $event->id)
            ->whereIn('invite_reciver_id', $rows->pluck('user.id'))
            ->get()
            ->keyBy('invite_reciver_id');

        return $rows->map(function (array $row) use ($invites): array {
            $row['invite'] = $invites->get($row['user']->id);

            return $row;
        });
    }

    public function blogCategoryPostCount(int|string $categoryId): int
    {
        return $this->remember("blog-category-post-count:{$categoryId}", fn (): int => Blog::query()
            ->where('category_id', $categoryId)
            ->count());
    }

    public function paidContentCreator(?User $viewer): array
    {
        if (! $viewer) {
            return [
                'creator' => null,
                'social' => (object) [],
            ];
        }

        $creator = $this->remember("paid-content-creator:{$viewer->id}", fn (): ?PaidContentCreator => PaidContentCreator::query()
            ->where('user_id', $viewer->id)
            ->first());

        return [
            'creator' => $creator,
            'social' => (object) (json_decode($creator?->social_accounts ?? '{}', true) ?: []),
        ];
    }

    public function fundraiserInvitedCount(Model $fundraiser): int
    {
        $invite = json_decode($fundraiser->invited ?? '[]', true);

        return count(is_array($invite) ? $invite : []);
    }

    public function fundraiserShareCount(?Model $sharecount): int
    {
        if (! $sharecount) {
            return 0;
        }

        return $this->remember("fundraiser-share-count:{$sharecount->post_id}", fn (): int => Post_share::query()
            ->where('post_id', $sharecount->post_id)
            ->count());
    }

    public function fundraiserInviteRows(iterable $friendships, Model $fundraiser, User $viewer): Collection
    {
        $rows = $this->friendshipRows($friendships, $viewer, $viewer, true);
        $invites = Invite::query()
            ->where('fundraiser_id', $fundraiser->id)
            ->whereIn('invite_reciver_id', $rows->pluck('user.id'))
            ->get()
            ->keyBy('invite_reciver_id');

        return $rows->map(function (array $row) use ($invites): array {
            $row['invite'] = $invites->get($row['user']->id);

            return $row;
        });
    }

    public function videoPost(Model $video): ?Posts
    {
        return $this->remember("video-post:{$video->id}", fn (): ?Posts => Posts::query()
            ->where('privacy', '!=', 'private')
            ->where('publisher', 'video_and_shorts')
            ->where('publisher_id', $video->id)
            ->first());
    }

    public function isVideoSaved(Model|int|string $video, ?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        $videoId = $video instanceof Model ? $video->id : $video;

        return $this->remember("video-saved:{$viewer->id}:{$videoId}", fn (): bool => Saveforlater::query()
            ->where('video_id', $videoId)
            ->where('user_id', $viewer->id)
            ->exists());
    }

    public function videoCommentCount(Model $video): int
    {
        $post = $this->videoPost($video);

        return $post ? $this->postCommentCount($post) : 0;
    }

    public function videoRootComments(Model $video): Collection
    {
        $post = $this->videoPost($video);

        return $post ? $this->rootComments($post) : collect();
    }

    public function videoReactCount(Model $video): int
    {
        $post = $this->videoPost($video);

        return $post ? count($this->reacts($post)) : 0;
    }

    public function videoViewCount(Model $video): int
    {
        $views = json_decode($video->view ?? '[]', true);

        return count(is_array($views) ? $views : []);
    }

    public function firstExternalUrl(?string $text): ?string
    {
        preg_match('/\bhttps?:\/\/\S+\b/', $text ?? '', $matches);
        $url = $matches[0] ?? null;

        return $url && ! str_contains($url, request()->getHttpHost()) ? $url : null;
    }

    public function postShareCount(Model $post): int
    {
        return $this->remember("post-share-count:{$post->post_id}", fn (): int => \App\Models\Post_share::query()
            ->where('post_id', $post->post_id)
            ->count());
    }

    public function isBlockedPost(Model $post, ?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        return $this->remember("blocked-post:{$viewer->id}:{$post->user_id}", fn (): bool => BlockUser::query()
            ->where(function ($query) use ($viewer, $post): void {
                $query->where('user_id', $viewer->id)->where('block_user', $post->user_id);
            })
            ->orWhere(function ($query) use ($viewer, $post): void {
                $query->where('user_id', $post->user_id)->where('block_user', $viewer->id);
            })
            ->exists());
    }

    public function setting(string $type, mixed $default = ''): mixed
    {
        return $this->remember("setting:{$type}", fn (): mixed => Setting::query()
            ->where('type', $type)
            ->value('description') ?? $default);
    }

    public function backendFolder(?string $commonPath, ?User $user): string
    {
        if ($commonPath === 'global') {
            return 'global';
        }

        return $user?->user_role === 'admin' ? 'admin' : 'user';
    }

    public function pendingAccountActivationCount(): int
    {
        return $this->remember('pending-account-activation-count', fn (): int => \App\Models\Account_active_request::query()
            ->where('status', 'pending')
            ->count());
    }

    public function pendingPaidContentPayoutCount(): int
    {
        return $this->remember('pending-paid-content-payout-count', fn (): int => \App\Models\PaidContentPayout::query()
            ->where('status', false)
            ->count());
    }

    public function pendingFundraiserPayoutCount(): int
    {
        return $this->remember('pending-fundraiser-payout-count', fn (): int => \App\Models\Fundraiser_payout::query()
            ->where('status', false)
            ->count());
    }

    public function pendingJobCount(): int
    {
        return $this->remember('pending-job-count', fn (): int => \App\Models\Job::query()
            ->where('status', 0)
            ->count());
    }

    public function groupAcceptedMemberCount(Model $group): int
    {
        return $this->remember("group-accepted-members:{$group->id}", fn (): int => Group_member::query()
            ->where('group_id', $group->id)
            ->where('is_accepted', '1')
            ->count());
    }

    public function userJoinedGroup(Model $group, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->remember("group-joined:{$group->id}:{$user->id}", fn (): bool => Group_member::query()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->exists());
    }

    public function groupMediaFiles(Model $group, int $limit = 10): Collection
    {
        return $this->remember("group-media:{$group->id}:{$limit}", fn (): Collection => Media_files::query()
            ->select(['id', 'group_id', 'file_name', 'file_type'])
            ->where('group_id', $group->id)
            ->whereNull('album_id')
            ->whereNull('product_id')
            ->whereNull('page_id')
            ->orderByDesc('id')
            ->take($limit)
            ->get());
    }

    public function recentGroupMembers(Model $group, int $limit = 8): Collection
    {
        return $this->remember("recent-group-members:{$group->id}:{$limit}", fn (): Collection => Group_member::query()
            ->with('getUser:id,name,photo')
            ->where('group_id', $group->id)
            ->where('is_accepted', '1')
            ->orderByDesc('id')
            ->limit($limit)
            ->get());
    }

    public function page(int|string|null $pageId): ?Page
    {
        if (! $pageId) {
            return null;
        }

        return $this->remember("page:{$pageId}", fn (): ?Page => Page::query()->find($pageId));
    }

    public function group(int|string|null $groupId): ?Group
    {
        if (! $groupId) {
            return null;
        }

        return $this->remember("group:{$groupId}", fn (): ?Group => Group::query()->find($groupId));
    }

    public function albumsFor(string $ownerType, int|string|null $ownerId): Collection
    {
        if (! $ownerId) {
            return collect();
        }

        return $this->remember("albums:{$ownerType}:{$ownerId}", function () use ($ownerType, $ownerId): Collection {
            $query = Albums::query()->select(['id', 'title', 'user_id', 'page_id', 'group_id']);

            return match ($ownerType) {
                'group' => $query->where('group_id', $ownerId)->get(),
                'page' => $query->where('page_id', $ownerId)->get(),
                'profile' => $query
                    ->where('user_id', $ownerId)
                    ->whereNull('page_id')
                    ->whereNull('group_id')
                    ->get(),
                default => collect(),
            };
        });
    }

    public function albumImages(int|string|null $albumId): Collection
    {
        if (! $albumId) {
            return collect();
        }

        return $this->remember("album-images:{$albumId}", fn (): Collection => Album_image::query()
            ->where('album_id', $albumId)
            ->get());
    }

    public function isGroupInviteSent(Model $user, int|string|null $groupId): bool
    {
        if (! $groupId) {
            return false;
        }

        return $this->remember("group-invite:{$groupId}:{$user->id}", fn (): bool => Invite::query()
            ->where('invite_reciver_id', $user->id)
            ->where('group_id', $groupId)
            ->exists());
    }

    public function recentUsers(int $limit = 7): Collection
    {
        return $this->remember("recent-users:{$limit}", fn (): Collection => User::query()
            ->select(['id', 'name', 'photo'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get());
    }

    public function upcomingPublicGroupEvents(Model $group): Collection
    {
        return $this->remember("upcoming-public-group-events:{$group->id}", fn (): Collection => Event::query()
            ->where('group_id', $group->id)
            ->where('privacy', 'public')
            ->whereDate('event_date', '>', now())
            ->orderBy('event_date')
            ->get());
    }

    public function areFriends(?User $viewer, Model $user): bool
    {
        if (! $viewer) {
            return false;
        }

        return $this->remember("are-friends:{$viewer->id}:{$user->id}", fn (): bool => Friendships::query()
            ->where(function ($query) use ($viewer, $user): void {
                $query->where('requester', $viewer->id)->where('accepter', $user->id);
            })
            ->orWhere(function ($query) use ($viewer, $user): void {
                $query->where('accepter', $viewer->id)->where('requester', $user->id);
            })
            ->exists());
    }

    public function chatThreadUser(Model $thread, User $viewer): ?User
    {
        $profileId = $thread->sender_id == $viewer->id ? $thread->reciver_id : $thread->sender_id;

        if ($profileId == $viewer->id) {
            return null;
        }

        return $this->remember("chat-thread-user:{$thread->id}:{$viewer->id}", fn (): ?User => User::query()
            ->select(['id', 'name', 'photo', 'lastActive'])
            ->find($profileId));
    }

    public function chatLastMessage(Model $thread): ?Chat
    {
        return $this->remember("chat-last-message:{$thread->id}", fn (): ?Chat => Chat::query()
            ->where('message_thrade', $thread->id)
            ->orderByDesc('id')
            ->first());
    }

    public function chatUnreadCount(Model $thread, ?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return $this->remember("chat-unread-count:{$thread->id}:{$user->id}", fn (): int => Chat::query()
            ->where('message_thrade', $thread->id)
            ->where('reciver_id', $user->id)
            ->where('read_status', '0')
            ->count());
    }

    public function chatFiles(Model $message): Collection
    {
        return $this->remember("chat-files:{$message->id}", fn (): Collection => Media_files::query()
            ->where('chat_id', $message->id)
            ->get());
    }

    public function storyTextInfo(Model $story): array
    {
        return array_merge([
            'color' => 'ffffff',
            'bg-color' => '000000',
            'text' => '',
        ], json_decode($story->description ?? '[]', true) ?: []);
    }

    public function storyMediaFiles(Model $story): Collection
    {
        return $this->remember("story-media:{$story->story_id}", fn (): Collection => Media_files::query()
            ->where('story_id', $story->story_id)
            ->get());
    }

    public function sharedTargetId(string $url): string
    {
        return basename(parse_url($url, PHP_URL_PATH) ?: '');
    }

    public function sharedTargetType(string $url): string
    {
        $segments = explode('/', trim((string) parse_url($url, PHP_URL_PATH), '/'));

        return $segments[count($segments) - 2] ?? '';
    }

    private function withCommentUsers(Collection $comments): Collection
    {
        $users = User::query()
            ->select(['id', 'name', 'photo'])
            ->whereIn('id', $comments->pluck('user_id')->unique())
            ->get()
            ->keyBy('id');

        return $comments->each(function (Comments $comment) use ($users): void {
            $user = $users->get($comment->user_id);
            $comment->name = $user?->name;
            $comment->photo = $user?->photo;
        });
    }

    private function remember(string $key, callable $callback): mixed
    {
        return $this->cache[$key] ??= $callback();
    }

    private function commentContentId(Model $model, string $type): int|string|null
    {
        return $type === 'post' ? $model->post_id : $model->id;
    }

    private function isBlockedUserId(int|string|null $userId, ?User $viewer): bool
    {
        if (! $userId || ! $viewer) {
            return false;
        }

        return $this->remember("blocked-user:{$viewer->id}:{$userId}", fn (): bool => BlockUser::query()
            ->where('user_id', $viewer->id)
            ->where('block_user', $userId)
            ->exists());
    }

    private function isFriendId(int|string|null $userId, ?User $viewer): bool
    {
        if (! $userId || ! $viewer) {
            return false;
        }

        $friends = json_decode($viewer->friends ?? '[]', true);

        return in_array((string) $userId, array_map('strval', is_array($friends) ? $friends : []), true);
    }

    private function hasFriendshipRequest(int|string $requesterId, int|string $accepterId): bool
    {
        return $this->remember("friendship-request:{$requesterId}:{$accepterId}", fn (): bool => Friendships::query()
            ->where('requester', $requesterId)
            ->where('accepter', $accepterId)
            ->exists());
    }
}
