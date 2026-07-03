<?php

namespace App\Actions\Friends;

use App\Models\Follower;
use App\Models\Friendships;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendFriendRequestAction
{
    /**
     * @throws Throwable
     */
    public function handle(User $requester, int $accepterId): void
    {
        try {
            DB::transaction(function () use ($requester, $accepterId): void {
                $this->createFriendship($requester, $accepterId);
                $this->createNotification($requester, $accepterId);
                $this->createFollower($requester, $accepterId);
            });
        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }
    }

    protected function createFriendship(User $requester, int $accepterId): Friendships
    {
        $existingFriendship = Friendships::where('accepter', $accepterId)
            ->where('requester', $requester->id)
            ->first();

        if ($existingFriendship instanceof Friendships) {
            return $existingFriendship;
        }

        $friendship = new Friendships;
        $friendship->accepter = $accepterId;
        $friendship->requester = $requester->id;
        $friendship->is_accepted = '0';
        $friendship->save();

        return $friendship;
    }

    protected function createNotification(User $requester, int $accepterId): Notification
    {
        $existingNotification = Notification::where('sender_user_id', $requester->id)
            ->where('reciver_user_id', $accepterId)
            ->where('type', 'profile')
            ->first();

        if ($existingNotification instanceof Notification) {
            return $existingNotification;
        }

        $notification = new Notification;
        $notification->sender_user_id = $requester->id;
        $notification->reciver_user_id = $accepterId;
        $notification->type = 'profile';
        $notification->save();

        return $notification;
    }

    protected function createFollower(User $requester, int $accepterId): Follower
    {
        $existingFollower = Follower::where('follow_id', $accepterId)
            ->where('user_id', $requester->id)
            ->first();

        if ($existingFollower instanceof Follower) {
            return $existingFollower;
        }

        $follower = new Follower;
        $follower->follow_id = $accepterId;
        $follower->user_id = $requester->id;
        $follower->save();

        return $follower;
    }
}
