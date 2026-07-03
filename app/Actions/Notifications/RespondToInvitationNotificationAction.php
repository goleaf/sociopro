<?php

namespace App\Actions\Notifications;

use App\Models\Event;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RespondToInvitationNotificationAction
{
    public function acceptGroup(User $receiver, int $senderId, int $groupId): bool
    {
        return $this->accept($receiver, $senderId, 'group_id', $groupId, 'group_invitation_accept');
    }

    public function declineGroup(User $receiver, int $senderId, int $groupId): bool
    {
        return $this->decline($receiver, $senderId, 'group_id', $groupId);
    }

    public function acceptEvent(User $receiver, int $senderId, int $eventId): bool
    {
        return $this->accept(
            $receiver,
            $senderId,
            'event_id',
            $eventId,
            'event_invitation_accept',
            fn () => $this->appendGoingUser($eventId, (int) $receiver->id)
        );
    }

    public function declineEvent(User $receiver, int $senderId, int $eventId): bool
    {
        return $this->decline($receiver, $senderId, 'event_id', $eventId);
    }

    public function acceptFundraiser(User $receiver, int $senderId, int $fundraiserId): bool
    {
        return $this->accept($receiver, $senderId, 'fundraiser_id', $fundraiserId, 'fundraiser_request_accept');
    }

    public function declineFundraiser(User $receiver, int $senderId, int $fundraiserId): bool
    {
        return $this->decline($receiver, $senderId, 'fundraiser_id', $fundraiserId);
    }

    private function accept(
        User $receiver,
        int $senderId,
        string $targetColumn,
        int $targetId,
        string $acceptanceType,
        ?callable $afterAccept = null
    ): bool {
        return DB::transaction(function () use ($receiver, $senderId, $targetColumn, $targetId, $acceptanceType, $afterAccept): bool {
            $updated = $this->inviteQuery($receiver, $senderId, $targetColumn, $targetId)
                ->where('is_accepted', '!=', 1)
                ->update(['is_accepted' => 1]);

            if ($updated < 1) {
                return false;
            }

            $this->notificationQuery($receiver, $senderId, $targetColumn, $targetId)
                ->update(['status' => 1, 'view' => 1]);

            if ($afterAccept !== null) {
                $afterAccept();
            }

            $this->createAcceptanceNotification($receiver, $senderId, $acceptanceType, $targetColumn, $targetId);

            return true;
        });
    }

    private function decline(User $receiver, int $senderId, string $targetColumn, int $targetId): bool
    {
        return DB::transaction(function () use ($receiver, $senderId, $targetColumn, $targetId): bool {
            $deleted = $this->inviteQuery($receiver, $senderId, $targetColumn, $targetId)->delete();

            if ($deleted < 1) {
                return false;
            }

            $this->notificationQuery($receiver, $senderId, $targetColumn, $targetId)->delete();

            return true;
        });
    }

    /**
     * @return Builder<Invite>
     */
    private function inviteQuery(User $receiver, int $senderId, string $targetColumn, int $targetId): Builder
    {
        return Invite::query()
            ->where('invite_sender_id', $senderId)
            ->where('invite_reciver_id', $receiver->id)
            ->where($targetColumn, $targetId);
    }

    /**
     * @return Builder<Notification>
     */
    private function notificationQuery(User $receiver, int $senderId, string $targetColumn, int $targetId): Builder
    {
        return Notification::query()
            ->where('sender_user_id', $senderId)
            ->where('reciver_user_id', $receiver->id)
            ->where($targetColumn, $targetId);
    }

    private function createAcceptanceNotification(
        User $receiver,
        int $senderId,
        string $type,
        string $targetColumn,
        int $targetId
    ): Notification {
        $notification = new Notification;
        $notification->sender_user_id = $receiver->id;
        $notification->reciver_user_id = $senderId;
        $notification->type = $type;
        $notification->{$targetColumn} = $targetId;
        $notification->save();

        return $notification;
    }

    private function appendGoingUser(int $eventId, int $userId): void
    {
        $event = Event::query()->whereKey($eventId)->first();

        if (! $event instanceof Event) {
            return;
        }

        $goingUsers = json_decode((string) $event->going_users_id, true);
        $goingUsers = is_array($goingUsers) ? $goingUsers : [];

        $ids = [];
        foreach ($goingUsers as $goingUserId) {
            if (! is_numeric($goingUserId)) {
                continue;
            }

            $goingUserId = (int) $goingUserId;
            if (! in_array($goingUserId, $ids, true)) {
                $ids[] = $goingUserId;
            }
        }

        if (! in_array($userId, $ids, true)) {
            $ids[] = $userId;
        }

        $event->going_users_id = json_encode($ids);
        $event->save();
    }
}
