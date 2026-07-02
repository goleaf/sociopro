<?php

namespace App\Http\Resources\Api;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/**
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $notification = $this->notification();
        $sender = $notification->getUserData;

        return [
            'id' => $notification->id,
            'sender_user_id' => $notification->sender_user_id,
            'reciver_user_id' => $notification->reciver_user_id,
            'name' => $sender?->name ?? '',
            'photo' => $sender ? get_user_images($sender->id) : get_user_images(),
            'type' => $notification->type,
            'event_id' => $notification->event_id,
            'event_name' => $notification->getEventData?->title ?? '',
            'page_id' => $notification->page_id,
            'pageName' => $notification->getPageData?->title ?? '',
            'group_id' => $notification->group_id,
            'groupName' => $notification->getGroupData?->title ?? '',
            'status' => $notification->status,
            'view' => $notification->view,
            'created_at' => Carbon::parse($notification->created_at)->diffForHumans(),
        ];
    }

    private function notification(): Notification
    {
        if (! $this->resource instanceof Notification) {
            throw new LogicException('NotificationResource must wrap a Notification model.');
        }

        return $this->resource;
    }
}
