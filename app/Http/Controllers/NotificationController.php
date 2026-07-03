<?php

namespace App\Http\Controllers;

use App\Actions\Friends\AcceptFriendRequestAction;
use App\Actions\Notifications\RespondToInvitationNotificationAction;
use App\Models\Friendships;
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
            ->where('status', '1')
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

    public function accept_group_notification(RespondToInvitationNotificationAction $respondToInvitation, $id, $group_id)
    {
        $response = [];

        $respondToInvitation->acceptGroup(auth()->user(), (int) $id, (int) $group_id);
        Session::flash('success_message', get_phrase('Group Invitation Accepted'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function decline_group_notification(RespondToInvitationNotificationAction $respondToInvitation, $id, $group_id)
    {
        $response = [];

        $respondToInvitation->declineGroup(auth()->user(), (int) $id, (int) $group_id);
        Session::flash('success_message', get_phrase('Group Invitation Canceled'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function accept_event_notification(RespondToInvitationNotificationAction $respondToInvitation, $id, $event_id)
    {
        $response = [];

        $respondToInvitation->acceptEvent(auth()->user(), (int) $id, (int) $event_id);
        Session::flash('success_message', get_phrase('Event Invitation Accepted'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function decline_event_notification(RespondToInvitationNotificationAction $respondToInvitation, $id, $event_id)
    {
        $response = [];

        $respondToInvitation->declineEvent(auth()->user(), (int) $id, (int) $event_id);
        Session::flash('success_message', get_phrase('Event Invitation Canceled'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function mark_as_read($id)
    {
        $response = [];
        Notification::where('id', $id)
            ->where('reciver_user_id', auth()->user()->id)
            ->update(['status' => '1', 'view' => '1']);

        Session::flash('success_message', get_phrase('Marked As Read'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    // fundraiser................

    public function accept_fundraiser_notification(RespondToInvitationNotificationAction $respondToInvitation, $id, $fundraiser_id)
    {
        $response = [];

        $respondToInvitation->acceptFundraiser(auth()->user(), (int) $id, (int) $fundraiser_id);
        Session::flash('success_message', get_phrase('Fundraiser Invitation Accepted'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function decline_fundraiser_notification(RespondToInvitationNotificationAction $respondToInvitation, $id, $fundraiser_id)
    {
        $response = [];

        $respondToInvitation->declineFundraiser(auth()->user(), (int) $id, (int) $fundraiser_id);
        Session::flash('success_message', get_phrase('Fundraiser Invitation Canceled'));
        $response = ['reload' => 1];

        return json_encode($response);
    }
}
