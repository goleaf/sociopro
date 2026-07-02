<?php

namespace App\Http\Resources\Api;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationCollection extends ResourceCollection
{
    public static $wrap = null;

    /**
     * @param  EloquentCollection<int, Notification>  $resource
     * @param  EloquentCollection<int, Notification>  $olderNotifications
     */
    public function __construct($resource, private readonly EloquentCollection $olderNotifications)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{new_notifications: list<array<string, mixed>>, older_notifications: list<array<string, mixed>>}
     */
    public function toArray(Request $request): array
    {
        return [
            'new_notifications' => NotificationResource::collection($this->collection)->resolve($request),
            'older_notifications' => NotificationResource::collection($this->olderNotifications)->resolve($request),
        ];
    }
}
