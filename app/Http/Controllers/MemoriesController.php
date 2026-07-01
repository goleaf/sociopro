<?php

namespace App\Http\Controllers;

use App\Models\Posts;
use App\Queries\FriendshipsQuery;
use Illuminate\Http\Request;

class MemoriesController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth()->user();

            return $next($request);
        });
    }

    public function memories()
    {
        $memories_by_post = Posts::join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name', 'users.photo', 'users.friends')
            ->whereDay('posts.posted_on', date('d', time()))
            ->whereMonth('posts.posted_on', date('m', time()))
            ->whereYear('posts.posted_on', '!=', date('Y', time()))
            ->where('posts.user_id', auth()->user()->id)
            ->active()
            ->notPrivate()
            ->notReported()
            ->where('posts.publisher', ['post', 'video_and_shorts'])
            ->orderBy('posts.post_id', 'desc')->take(5)->get();

        $page_data['posts'] = $memories_by_post;

        $page_data['friendships'] = FriendshipsQuery::importantForUser(auth()->user())->get();

        $page_data['has_memories'] = $memories_by_post->count();
        $page_data['view_path'] = 'frontend.main_content.memories';

        return view('frontend.index', $page_data);
    }

    public function load_memories(Request $request)
    {
        $memories_by_post = Posts::join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name', 'users.photo', 'users.friends')
            ->whereDay('posts.posted_on', date('d', time()))
            ->whereMonth('posts.posted_on', date('m', time()))
            ->whereYear('posts.posted_on', '!=', date('Y', time()))
            ->where('posts.user_id', auth()->user()->id)
            ->active()
            ->notPrivate()
            ->notReported()
            ->where('posts.publisher', ['post', 'video_and_shorts'])
            ->orderBy('posts.post_id', 'desc')
            ->skip($request->offset)->take(3)->get();

        $page_data['friendships'] = FriendshipsQuery::importantForUser(auth()->user())->get();

        $page_data['posts'] = $memories_by_post;
        $page_data['has_memories'] = $memories_by_post->count();
        $page_data['user_info'] = $this->user;
        $page_data['type'] = 'user_post';

        return view('frontend.main_content.posts', $page_data);
    }
}
