<?php

namespace App\Http\Controllers;

use App\Models\Follower;
use Session;

class FollowController extends Controller
{
    public function follow($id)
    {
        $response = [];
        $userId = auth()->user()->id;

        if (! Follower::where('follow_id', $id)->where('user_id', $userId)->exists()) {
            $follwer = new Follower;
            $follwer->follow_id = $id;
            $follwer->user_id = $userId;
            $follwer->save();
        }

        Session::flash('success_message', get_phrase('You are now following'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function unfollow($id)
    {
        $response = [];
        Follower::where('follow_id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        Session::flash('success_message', get_phrase('Removed from followed list'));
        $response = ['reload' => 1];

        return json_encode($response);
    }
}
