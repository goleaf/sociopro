<?php

namespace App\Http\Controllers;

use App\Models\Posts;
use App\Models\Friendships;
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

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }
    public function memories()
    {
        $memories_by_post = Posts::join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name', 'users.photo', 'users.friends')
            ->whereDay('posts.posted_on', date('d', time()))
            ->whereMonth('posts.posted_on', date('m', time()))
            ->whereYear('posts.posted_on', '!=', date('Y', time()))
            ->where('posts.user_id', auth()->user()->id)
            ->where('posts.status', 'active')
            ->where('posts.privacy', '!=', 'private')
            ->where('posts.report_status', '0')
            ->where('posts.publisher', ['post', 'video_and_shorts'])
            ->orderBy('posts.post_id', 'desc')->take(5)->get();

        $page_data['posts'] = $memories_by_post;

        // New
        $friendships = Friendships::where(function ($query) {
            $query->where('accepter', auth()->user()->id)
                ->orWhere('requester', auth()->user()->id);
        })
            ->where('is_accepted', 1)
            ->orderBy('friendships.importance', 'desc')->get();
        $page_data['friendships'] = $friendships;
      //new

        $page_data['has_memories'] = $memories_by_post->count();
        $page_data['view_path'] = 'frontend.main_content.memories';
        return view('frontend.index', $page_data);
    }

    function load_memories(Request $request)
    {
        $memories_by_post = Posts::join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name', 'users.photo', 'users.friends')
            ->whereDay('posts.posted_on', date('d', time()))
            ->whereMonth('posts.posted_on', date('m', time()))
            ->whereYear('posts.posted_on', '!=', date('Y', time()))
            ->where('posts.user_id', auth()->user()->id)
            ->where('posts.status', 'active')
            ->where('posts.privacy', '!=', 'private')
            ->where('posts.report_status', '0')
            ->where('posts.publisher', ['post', 'video_and_shorts'])
            ->orderBy('posts.post_id', 'desc')
            ->skip($request->offset)->take(3)->get();

           // New
        $friendships = Friendships::where(function ($query) {
            $query->where('accepter', auth()->user()->id)
                ->orWhere('requester', auth()->user()->id);
        })
            ->where('is_accepted', 1)
            ->orderBy('friendships.importance', 'desc')->get();
        $page_data['friendships'] = $friendships;
      //new
         
        $page_data['posts'] = $memories_by_post;
        $page_data['has_memories'] = $memories_by_post->count();
        $page_data['user_info'] = $this->user;
        $page_data['type'] = 'user_post';
        return view('frontend.main_content.posts', $page_data);
    }
}
