<?php

namespace App\Http\Controllers;

use App\Actions\Friends\AcceptFriendRequestAction;
use App\Models\Event;
use App\Models\Friendships;
use App\Models\Fundraiser;
use App\Models\Invite;
use App\Models\Notification;
use Carbon\Carbon;
use Session;

class NotificationController extends Controller
{
    private const PER_PAGE = 25;

    /**
     * @var list<string>
     */
    private const RELATIONS = ['getUserData', 'getEventData', 'getGroupData', 'getFundraiserData'];

    public function notifications()
    {
        $date = Carbon::today();
        $page_data['new_notification'] = Notification::with(self::RELATIONS)
            ->where('reciver_user_id', auth()->user()->id)
            ->where('status', '0')
            ->orderBy('id', 'DESC')
            ->paginate(self::PER_PAGE, ['*'], 'new_page');
        $page_data['older_notification'] = Notification::with(self::RELATIONS)
            ->where('reciver_user_id', auth()->user()->id)
            ->where('created_at', '<', $date)
            ->orderBy('id', 'DESC')
            ->paginate(self::PER_PAGE, ['*'], 'older_page');
        $page_data['view_path'] = 'frontend.notification.notification';

        return view('frontend.index', $page_data);
    }

    public function accept_friend_notification(AcceptFriendRequestAction $acceptFriendRequest, $id)
    {
        $response = [];

        $acceptFriendRequest->acceptFromNotification(auth()->user(), (int) $id);
        Session::flash('success_message', get_phrase('Friend Request Accepted'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function decline_friend_notification($id)
    {
        $response = [];
        $friendship = Friendships::where('requester', $id)->where('accepter', auth()->user()->id)->delete();
        $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', auth()->user()->id)->delete();

        Session::flash('success_message', get_phrase('Cancle Friend Request'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function accept_group_notification($id, $group_id)
    {
        $response = [];
        $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', auth()->user()->id)->where('group_id', $group_id)->update(['is_accepted' => '1']);
        $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', auth()->user()->id)->update(['status' => '1', 'view' => '1']);

        $notify = new Notification;
        $notify->sender_user_id = auth()->user()->id;
        $notify->reciver_user_id = $id;
        $notify->type = 'group_invitation_accept';
        $notify->save();
        Session::flash('success_message', get_phrase('Group Invitation Accepted'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function decline_group_notification($id, $group_id)
    {
        $response = [];
        $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', auth()->user()->id)->where('group_id', $group_id)->delete();
        $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', auth()->user()->id)->delete();

        Session::flash('success_message', get_phrase('Group Invitation Canceled'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function accept_event_notification($id, $event_id)
    {
        $response = [];
        $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', auth()->user()->id)->where('event_id', $event_id)->update(['is_accepted' => '1']);
        $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', auth()->user()->id)->update(['status' => '1', 'view' => '1']);

        if ($is_updated == '1') {
            // update my friends id to my friend list
            $going_users_id = Event::where('id', $event_id)->value('going_users_id');
            $going_users_id = json_decode($going_users_id);
            array_push($going_users_id, (int) $id);
            $going_users_id = json_encode($going_users_id);

            Event::where('id', $event_id)->update(['going_users_id' => $going_users_id]);
        }

        $notify = new Notification;
        $notify->sender_user_id = auth()->user()->id;
        $notify->reciver_user_id = $id;
        $notify->type = 'event_invitation_accept';
        $notify->save();

        Session::flash('success_message', get_phrase('Event Invitation Accepted'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function decline_event_notification($id, $event_id)
    {
        $response = [];
        $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', auth()->user()->id)->where('event_id', $event_id)->delete();
        $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', auth()->user()->id)->delete();

        Session::flash('success_message', get_phrase('Event Invitation Canceled'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function mark_as_read($id)
    {
        $response = [];
        Notification::where('id', $id)->update(['status' => '1', 'view' => '1']);

        Session::flash('success_message', get_phrase('Marked As Read'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    // fundraiser................

    public function accept_fundraiser_notification($id, $fundraiser_id)
    {
        $response = [];
        $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', auth()->user()->id)->where('fundraiser_id', $fundraiser_id)->update(['is_accepted' => '1']);
        $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', auth()->user()->id)->update(['status' => '1', 'view' => '1']);

        $notify = new Notification;
        $notify->sender_user_id = auth()->user()->id;
        $notify->reciver_user_id = $id;
        $notify->type = 'fundraiser_request_accept';
        $notify->save();

        Session::flash('success_message', get_phrase('Fundraiser Invitation Accepted'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function decline_fundraiser_notification($id, $fundraiser_id)
    {
        $response = [];
        $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', auth()->user()->id)->where('fundraiser_id', $fundraiser_id)->delete();
        $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', auth()->user()->id)->delete();

        Session::flash('success_message', get_phrase('Fundraiser Invitation Canceled'));
        $response = ['reload' => 1];

        return json_encode($response);
    }
}
