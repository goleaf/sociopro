<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Friendships;
use App\Models\Group;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\Page;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function notifications(Request $request)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $date = Carbon::today();
            $new_notification = Notification::where('reciver_user_id', $user_id)->where('status', '0')
                ->orderBy('id', 'DESC')->get();
            $newnoti = [];
            foreach ($new_notification as $post) {
                $user = User::find($post->sender_user_id);

                $createdDate = Carbon::createFromTimestamp(strtotime($post->created_at));
                $formattedDate = $createdDate->diffForHumans();
                $eventName = '';

                if (! is_null($post->event_id)) {
                    $event = Event::find($post->event_id);
                    $eventName = $event ? $event->title : '';
                }
                $groupName = '';

                if (! is_null($post->group_id)) {
                    $group = Group::find($post->group_id);
                    $groupName = $group ? $group->title : '';
                }
                $pageName = '';

                if (! is_null($post->page_id)) {
                    $page = Page::find($post->page_id);
                    $pageName = $page ? $page->title : '';
                }
                $newnoti[] = [
                    'id' => $post->id,
                    'sender_user_id' => $post->sender_user_id,
                    'reciver_user_id' => $post->reciver_user_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->id),
                    'type' => $post->type,
                    'event_id' => $post->event_id,
                    'event_name' => $eventName,
                    'page_id' => $post->page_id,
                    'pageName' => $pageName,
                    'group_id' => $post->group_id,
                    'groupName' => $groupName,
                    'status' => $post->status,
                    'view' => $post->view,
                    'created_at' => $formattedDate,
                ];
            }
            $older_notification = Notification::where('reciver_user_id', $user_id)->where('created_at', '<', $date)->orderBy('id', 'DESC')->get();
            $oldnoti = [];
            foreach ($older_notification as $post) {
                $user = User::find($post->sender_user_id);

                $createdDate = Carbon::createFromTimestamp(strtotime($post->created_at));
                $formattedDate = $createdDate->diffForHumans();

                $eventName = '';

                if (! is_null($post->event_id)) {
                    $event = Event::find($post->event_id);
                    $eventName = $event ? $event->title : '';
                }
                $groupName = '';

                if (! is_null($post->group_id)) {
                    $group = Group::find($post->group_id);
                    $groupName = $group ? $group->title : '';
                }
                $pageName = '';

                if (! is_null($post->page_id)) {
                    $page = Page::find($post->page_id);
                    $pageName = $page ? $page->title : '';
                }

                $oldnoti[] = [
                    'id' => $post->id,
                    'sender_user_id' => $post->sender_user_id,
                    'reciver_user_id' => $post->reciver_user_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->id),
                    'type' => $post->type,
                    'event_id' => $post->event_id,
                    'event_name' => $eventName,
                    'page_id' => $post->page_id,
                    'pageName' => $pageName,
                    'group_id' => $post->group_id,
                    'groupName' => $groupName,
                    'status' => $post->status,
                    'view' => $post->view,
                    'created_at' => $formattedDate,
                ];
            }

            $response['new_notifications'] = $newnoti;
            $response['older_notifications'] = $oldnoti;
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }

    public function accept_friend_notification(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $is_updated = Friendships::where('requester', $id)->where('accepter', $user_id)->update(['is_accepted' => '1']);
            Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->update(['status' => '1', 'view' => '1']);

            if ($is_updated == 1) {
                $my_friends = User::where('id', $user_id)->value('friends');
                $my_friends = json_decode($my_friends);
                if (is_array($my_friends)) {
                    array_push($my_friends, (int) $id);
                } else {
                    $my_friends = [(int) $id];
                }
                $my_friends = json_encode($my_friends);

                User::where('id', $user_id)->update(['friends' => $my_friends]);

                $my_friends_of_friends = User::where('id', $id)->value('friends');
                $my_friends_of_friends = json_decode($my_friends_of_friends);

                if (is_array($my_friends_of_friends)) {
                    array_push($my_friends_of_friends, (int) $user_id);
                } else {
                    $my_friends_of_friends = [(int) $user_id];
                }
                $my_friends_of_friends = json_encode($my_friends_of_friends);

                User::where('id', $id)->update(['friends' => $my_friends_of_friends]);
            }

            $notify = new Notification;
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $id;
            $notify->type = 'friend_request_accept';
            $save = $notify->save();
            if ($save) {
                $response['success'] = true;
                $response['message'] = 'Friend request accept';
            } else {
                $response['success'] = false;
                $response['message'] = 'not found request';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }

    public function decline_friend_notification(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            Friendships::where('requester', $id)->where('accepter', $user_id)->delete();
            Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->delete();
            $response['success'] = true;
            $response['message'] = 'successfully decline';
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }

    public function accept_group_notification(Request $request, $id, $group_id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('group_id', $group_id)->update(['is_accepted' => '1']);
            Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->update(['status' => '1', 'view' => '1']);

            $notify = new Notification;
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $id;
            $notify->type = 'group_invitation_accept';
            $save = $notify->save();
            if ($save) {
                $response['success'] = true;
                $response['message'] = 'Group request accept';
            } else {
                $response['success'] = false;
                $response['message'] = 'not found request';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }

    public function decline_group_notification(Request $request, $id, $group_id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('group_id', $group_id)->delete();
            Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->delete();

            $response['success'] = true;
            $response['message'] = 'group notification decline';
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }

    public function accept_event_notification(Request $request, $id, $event_id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('event_id', $event_id)->update(['is_accepted' => '1']);
            Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->update(['status' => '1', 'view' => '1']);

            if ($is_updated == '1') {
                $going_users_id = Event::where('id', $event_id)->value('going_users_id');
                $going_users_id = json_decode($going_users_id);
                array_push($going_users_id, (int) $id);
                $going_users_id = json_encode($going_users_id);

                Event::where('id', $event_id)->update(['going_users_id' => $going_users_id]);
            }

            $notify = new Notification;
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $id;
            $notify->type = 'event_invitation_accept';
            $save = $notify->save();
            if ($save) {
                $response['success'] = true;
                $response['message'] = 'event invite request accept';
            } else {
                $response['success'] = false;
                $response['message'] = 'not request found ';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }

    public function decline_event_notification(Request $request, $id, $event_id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('event_id', $event_id)->delete();
            $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->delete();
            if ($notify) {
                $response['success'] = true;
                $response['message'] = 'event request decline';
            } else {
                $response['success'] = false;
                $response['message'] = 'not found request';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }

    public function mark_as_read(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $done = Notification::where('id', $id)->update(['status' => '1', 'view' => '1']);
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'mark as read';
            } else {
                $response['success'] = false;
                $response['message'] = 'not found';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }

    public function accept_fundraiser_notification(Request $request, $id, $fundraiser_id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('fundraiser_id', $fundraiser_id)->update(['is_accepted' => '1']);
            Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->update(['status' => '1', 'view' => '1']);

            $notify = new Notification;
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $id;
            $notify->type = 'fundraiser_request_accept';
            $notify->save();
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }

    public function decline_fundraiser_notification(Request $request, $id, $fundraiser_id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('fundraiser_id', $fundraiser_id)->delete();
            Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->delete();
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }
}
