<?php

namespace App\Actions\Friends;

use App\Models\Follower;
use App\Models\Friendships;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class AcceptFriendRequestAction
{
    /**
     * @throws Throwable
     */
    public function acceptFromProfile(User $accepter, int $requesterId): bool
    {
        return $this->accept(
            accepter: $accepter,
            requesterId: $requesterId,
            ensureFollower: true,
            markInboundNotificationWhenMissing: false,
            createAcceptanceNotificationWhenMissing: false
        );
    }

    /**
     * @throws Throwable
     */
    public function acceptFromNotification(User $accepter, int $requesterId): bool
    {
        return $this->accept(
            accepter: $accepter,
            requesterId: $requesterId,
            ensureFollower: false,
            markInboundNotificationWhenMissing: true,
            createAcceptanceNotificationWhenMissing: true
        );
    }

    /**
     * @throws Throwable
     */
    private function accept(
        User $accepter,
        int $requesterId,
        bool $ensureFollower,
        bool $markInboundNotificationWhenMissing,
        bool $createAcceptanceNotificationWhenMissing
    ): bool {
        try {
            return DB::transaction(function () use (
                $accepter,
                $requesterId,
                $ensureFollower,
                $markInboundNotificationWhenMissing,
                $createAcceptanceNotificationWhenMissing
            ): bool {
                $accepted = $this->markFriendshipAccepted($accepter, $requesterId);

                if ($accepted) {
                    if ($ensureFollower) {
                        $this->createFollowerIfMissing($accepter, $requesterId);
                    }

                    $this->appendFriendId((int) $accepter->id, $requesterId);
                    $this->appendFriendId($requesterId, (int) $accepter->id);
                }

                if ($accepted || $markInboundNotificationWhenMissing) {
                    $this->markInboundNotificationRead($requesterId, (int) $accepter->id);
                }

                if ($accepted || $createAcceptanceNotificationWhenMissing) {
                    $this->createAcceptanceNotification($accepter, $requesterId);
                }

                return $accepted;
            });
        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }
    }

    protected function createFollowerIfMissing(User $accepter, int $requesterId): Follower
    {
        $existingFollower = Follower::where('follow_id', $requesterId)
            ->where('user_id', $accepter->id)
            ->first();

        if ($existingFollower instanceof Follower) {
            return $existingFollower;
        }

        $follower = new Follower;
        $follower->follow_id = $requesterId;
        $follower->user_id = $accepter->id;
        $follower->save();

        return $follower;
    }

    protected function markFriendshipAccepted(User $accepter, int $requesterId): bool
    {
        return Friendships::where('accepter', $accepter->id)
            ->where('requester', $requesterId)
            ->where('is_accepted', '!=', 1)
            ->update(['is_accepted' => '1']) === 1;
    }

    protected function markInboundNotificationRead(int $requesterId, int $accepterId): void
    {
        Notification::where('sender_user_id', $requesterId)
            ->where('reciver_user_id', $accepterId)
            ->update(['status' => '1', 'view' => '1']);
    }

    protected function createAcceptanceNotification(User $sender, int $receiverId): Notification
    {
        $notification = new Notification;
        $notification->sender_user_id = $sender->id;
        $notification->reciver_user_id = $receiverId;
        $notification->type = 'friend_request_accept';
        $notification->save();

        return $notification;
    }

    protected function appendFriendId(int $userId, int $friendId): void
    {
        $friends = json_decode((string) User::where('id', $userId)->value('friends'), true);

        if (! is_array($friends)) {
            $friends = [];
        }

        $friendIds = [];
        foreach ($friends as $existingFriendId) {
            if (! is_numeric($existingFriendId)) {
                continue;
            }

            $existingFriendId = (int) $existingFriendId;
            if (! in_array($existingFriendId, $friendIds, true)) {
                $friendIds[] = $existingFriendId;
            }
        }

        if (! in_array($friendId, $friendIds, true)) {
            $friendIds[] = $friendId;
        }

        User::where('id', $userId)->update(['friends' => json_encode($friendIds)]);
    }
}
