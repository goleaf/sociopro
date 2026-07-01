<?php

namespace App\Http\Controllers;

use App\Enums\MediaFileType;
use App\Enums\MembershipRole;
use App\Models\Albums;
use App\Models\Media_files;
use App\Models\Page;
use App\Models\Page_like;
use App\Models\Posts;
use App\Queries\FriendshipsQuery;
use App\Support\Files\FileUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Image;
use Session;

class PageController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth()->user();

            return $next($request);
        });
    }

    public function pages()
    {
        $pageLiked = [];
        $likepages = Page_like::where('user_id', auth()->user()->id)->get();
        foreach ($likepages as $likepage) {
            $likepageid = $likepage->page_id;
            array_push($pageLiked, $likepageid);
        }
        $page_data['mypages'] = Page::where('user_id', auth()->user()->id)->orderBy('id', 'DESC')->limit('5')->get();
        $page_data['suggestedpages'] = Page::whereNotIn('id', $pageLiked)->get();
        $page_data['likedpage'] = Page_like::where('user_id', auth()->user()->id)->orderBy('id', 'DESC')->limit('10')->get();
        $page_data['view_path'] = 'frontend.pages.pages';

        return view('frontend.index', $page_data);
    }

    public function store(Request $request)
    {
        $rules = [
            'image' => 'mimes:jpeg,jpg,png,gif|nullable',
            'name' => 'required|max:255',
            'category' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return json_encode(['validationError' => $validator->getMessageBag()->toArray()]);
        }

        $file_name = null;
        if ($request->image && ! empty($request->image)) {
            $file_name = FileUploader::upload($request->image, 'public/storage/pages/logo', 250);
        }

        $page = new Page;
        $page->user_id = auth()->user()->id;
        $page->title = $request->name;
        $page->category_id = $request->category;
        $page->description = $request->description;
        if ($file_name !== null) {
            $page->logo = $file_name;
        }
        $done = $page->save();
        if ($done) {
            // $pagelike = new Page_like();
            // $pagelike->page_id = $page->id;
            // $pagelike->user_id = auth()->user()->id;
            // $pagelike->role = 'admin';
            // $done = $pagelike->save();
            if ($done) {
                Session::flash('success_message', get_phrase('Page Created Successfully'));

                return json_encode(['reload' => 1]);
            }
        }
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'image' => 'mimes:jpeg,jpg,png,gif|nullable',
            'name' => 'required|max:255',
            'category' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return json_encode(['validationError' => $validator->getMessageBag()->toArray()]);
        }

        $page = Page::find($id);
        // previous image name
        $imagename = $page->logo;
        $file_name = null;
        if ($request->image && ! empty($request->image)) {
            $file_name = FileUploader::upload($request->image, 'public/storage/pages/logo', 250);
        }

        $page->user_id = auth()->user()->id;
        $page->title = $request->name;
        $page->category_id = $request->category;
        $page->description = $request->description;
        if ($file_name !== null) {
            $page->logo = $file_name;
        }
        $done = $page->save();
        if ($done) {
            // just put the file name and folder name nothing more :)
            if (! empty($request->image)) {
                if (File::exists(public_path('storage/pages/logo/'.$imagename))) {
                    File::delete(public_path('storage/pages/logo/'.$imagename));
                }
            }
        }
        Session::flash('success_message', get_phrase('Page Updated Successfully'));

        return json_encode(['reload' => 1]);
    }

    public function updatecoverphoto(Request $request, $id)
    {
        $page = Page::find($id);
        $imagename = $page->coverphoto;

        if ($request->cover_photo && ! empty($request->cover_photo)) {
            $file_name = FileUploader::upload($request->cover_photo, 'public/storage/pages/coverphoto', 1120);

            $page->coverphoto = $file_name;
        }
        $done = $page->save();
        if ($done) {
            // just put the file name and folder name nothing more :)
            if (! empty($request->cover_photo)) {
                if (File::exists(public_path('storage/pages/coverphoto/'.$imagename))) {
                    File::delete(public_path('storage/pages/coverphoto/'.$imagename));
                }
            }
        }
        Session::flash('success_message', get_phrase('Cover Photo Updated Successfully'));

        return json_encode(['reload' => 1]);
    }

    public function updateinfo(Request $request, $id)
    {
        $page = Page::find($id);
        $page->job = $request->job;
        $page->lifestyle = $request->lifestyle;
        $page->location = $request->location;
        $page->save();
        Session::flash('success_message', get_phrase('Info Updated Successfully'));

        return redirect()->back();
    }

    // load event on scroll

    public function load_page_by_scrolling(Request $request)
    {
        $mypages = Page::where('user_id', auth()->user()->id)->skip($request->offset)->take(6)->orderBy('id', 'DESC')->get();
        $page_data['mypages'] = $mypages;

        return view('frontend.pages.single-page', $page_data);
    }

    public function single_page($id)
    {
        $friendsid = FriendshipsQuery::acceptedFriendIdsForUser(auth()->user());

        $all_videos = Media_files::where('page_id', $id)
            ->ofType(MediaFileType::Video)
            ->take(20)->orderBy('id', 'DESC')->get();

        $page_data['all_videos'] = $all_videos;

        $all_photos = Media_files::where('page_id', $id)
            ->take(30)->orderBy('id', 'DESC')->get();
        $page_data['all_photos'] = $all_photos;

        $posts = Posts::notPrivate()
            ->where('posts.publisher', 'page')
            ->where('posts.publisher_id', $id)
            ->active()
            ->join('pages', 'posts.publisher_id', '=', 'pages.id')
            ->select('posts.*', 'pages.id', 'pages.title', 'pages.logo', 'posts.created_at as created_at')
            ->orderBy('posts.post_id', 'DESC')->get();

        $page_data['posts'] = $posts;
        $page_data['suggestedpages'] = Page_like::whereIn('user_id', $friendsid)->where('user_id', '!=', auth()->user()->id)->limit('1')->get();
        $page_data['page'] = Page::find($id);

        $page_data['friendships'] = FriendshipsQuery::importantForUser(auth()->user())
            ->take(15)->get();

        $page_data['view_path'] = 'frontend.pages.page-timeline';

        return view('frontend.index', $page_data);
    }

    public function page_photos($id)
    {
        $friendsid = FriendshipsQuery::acceptedFriendIdsForUser(auth()->user());

        $all_photos = Media_files::where('page_id', $id)
            ->ofType(MediaFileType::Image)
            ->take(20)->orderBy('id', 'DESC')->get();

        $all_albums = Albums::where('page_id', $id)
            ->take(6)->orderBy('id', 'DESC')->get();

        $page_data['all_videos'] = Media_files::where('page_id', $id)
            ->ofType(MediaFileType::Video)
            ->take(20)->orderBy('id', 'DESC')->get();

        $page_data['all_photos'] = $all_photos;
        $page_data['all_albums'] = $all_albums;
        $page_data['page_identifire'] = 'page';
        $page_data['page'] = Page::find($id);

        $page_data['suggestedpages'] = Page_like::whereIn('user_id', $friendsid)->where('user_id', '!=', auth()->user()->id)->limit('1')->get();
        $page_data['view_path'] = 'frontend.pages.photos';

        return view('frontend.index', $page_data);
    }

    public function videos($id)
    {
        $friendsid = FriendshipsQuery::acceptedFriendIdsForUser(auth()->user());

        $all_videos = Media_files::where('page_id', $id)
            ->ofType(MediaFileType::Video)
            ->take(20)->orderBy('id', 'DESC')->get();

        $page_data['all_videos'] = $all_videos;

        $page_data['page'] = Page::find($id);
        $all_photos = Media_files::where('page_id', $id)
            ->ofType(MediaFileType::Image)
            ->take(20)->orderBy('id', 'DESC')->get();
        $page_data['all_photos'] = $all_photos;

        $page_data['suggestedpages'] = Page_like::whereIn('user_id', $friendsid)->where('user_id', '!=', auth()->user()->id)->limit('1')->get();
        $page_data['view_path'] = 'frontend.pages.video';

        return view('frontend.index', $page_data);
    }

    public function load_videos(Request $request)
    {
        $all_videos = Media_files::where('user_id', $this->user->id)
            ->ofType(MediaFileType::Video)
            ->skip($request->offset)->take(12)->orderBy('id', 'DESC')->get();

        $page_data['all_videos'] = $all_videos;
        $page_data['user_info'] = $this->user;

        return view('frontend.profile.video_single', $page_data);
    }

    public function like($id)
    {
        $response = [];
        $pagelike = new Page_like;
        $pagelike->page_id = $id;
        $pagelike->user_id = auth()->user()->id;
        $pagelike->role = MembershipRole::General->value;
        $pagelike->save();
        Session::flash('success_message', get_phrase('Page Liked Successfully'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function dislike($id)
    {
        $response = [];
        $user_id = auth()->user()->id;
        $pagelike = Page_like::where('page_id', $id)->where('user_id', $user_id)->first();

        if ($pagelike) {
            $pagelike->delete();
            Session::flash('success_message', get_phrase('Page Disliked'));
            $response = ['reload' => 1];
        } else {
            $response = ['reload' => 0];
        }

        return json_encode($response);
    }
}
