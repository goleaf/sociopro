<?php

namespace App\Http\Controllers;

use App\Models\Follower;
use Session;

class FollowController extends Controller
{
    public function follow($id)
    {
        $response = [];
        $follwer = new Follower();
        $follwer->follow_id = $id;
        $follwer->user_id = auth()->user()->id;
        $follwer->save();
        Session::flash('success_message', get_phrase('You are now following'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function unfollow($id)
    {
        $response = [];
        $follwer = Follower::where('follow_id', $id)->delete();

        Session::flash('success_message', get_phrase('Removed from followed list'));
        $response = ['reload' => 1];

        return json_encode($response);
    }
}
