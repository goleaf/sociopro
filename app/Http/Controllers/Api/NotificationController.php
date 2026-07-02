<?php

namespace App\Http\Controllers\Api;

use App\Actions\Friends\AcceptFriendRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificationCollection;
use App\Models\Event;
use App\Models\Friendships;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\User;
use App\Support\Api\ApiErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    private const PER_PAGE = 25;

    private const MAX_PER_PAGE = 50;

    /**
     * @var list<string>
     */
    private const RELATIONS = ['getUserData', 'getEventData', 'getGroupData', 'getPageData'];

    public function notifications(Request $request)
    {
        $token = $request->bearerToken();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $date = Carbon::today();
            $perPage = $this->perPage($request);
            $new_notification = Notification::with(self::RELATIONS)
                ->where('reciver_user_id', $user_id)
                ->where('status', '0')
                ->orderBy('id', 'DESC')
                ->simplePaginate($perPage, ['*'], 'new_page');
            $older_notification = Notification::with(self::RELATIONS)
                ->where('reciver_user_id', $user_id)
                ->where('created_at', '<', $date)
                ->orderBy('id', 'DESC')
                ->simplePaginate($perPage, ['*'], 'older_page');

            return new NotificationCollection($new_notification->getCollection(), $older_notification->getCollection());
        } else {
            return $this->legacyAuthenticationError();
        }
    }

    private function perPage(Request $request): int
    {
        $perPage = $request->integer('per_page', self::PER_PAGE);

        if ($perPage < 1) {
            return self::PER_PAGE;
        }

        return min($perPage, self::MAX_PER_PAGE);
    }

    public function accept_friend_notification(Request $request, AcceptFriendRequestAction $acceptFriendRequest, $id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $acceptFriendRequest->acceptFromNotification(auth('sanctum')->user(), (int) $id);
            $response['success'] = true;
            $response['message'] = 'Friend request accept';
        } else {
            return $this->legacyAuthenticationError();
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
            return $this->legacyAuthenticationError();
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
                return ApiErrorResponse::notFound('not found request', Response::HTTP_OK);
            }
        } else {
            return $this->legacyAuthenticationError();
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
            return $this->legacyAuthenticationError();
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
                return ApiErrorResponse::notFound('not request found ', Response::HTTP_OK);
            }
        } else {
            return $this->legacyAuthenticationError();
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
                return ApiErrorResponse::notFound('not found request', Response::HTTP_OK);
            }
        } else {
            return $this->legacyAuthenticationError();
        }

        return $response;
    }

    public function mark_as_read(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user = auth('sanctum')->user();
            if (! $user instanceof User) {
                return $this->legacyAuthenticationError();
            }

            $notification = Notification::query()->whereKey($id)->first();
            if (! $notification instanceof Notification) {
                return ApiErrorResponse::notFound('not found', Response::HTTP_OK);
            }

            if ((int) $notification->reciver_user_id !== (int) $user->id) {
                return ApiErrorResponse::authorization(transportStatus: Response::HTTP_OK);
            }

            $notification->status = '1';
            $notification->view = '1';
            $notification->save();

            $response['success'] = true;
            $response['message'] = 'mark as read';
        } else {
            return $this->legacyAuthenticationError();
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
            return $this->legacyAuthenticationError();
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
            return $this->legacyAuthenticationError();
        }

        return $response;
    }

    private function legacyAuthenticationError(): JsonResponse
    {
        return ApiErrorResponse::authentication(transportStatus: Response::HTTP_OK);
    }
}
