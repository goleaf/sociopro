<?php

namespace App\Http\Controllers;

use App\Enums\MediaFileType;
use App\Enums\MembershipRole;
use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdatePageCoverPhotoRequest;
use App\Http\Requests\Page\UpdatePageInfoRequest;
use App\Http\Requests\Page\UpdatePageRequest;
use App\Models\Albums;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\PageLike;
use App\Models\Posts;
use App\Queries\FriendshipsQuery;
use App\Support\Files\FileUploader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
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
        $userId = (int) auth()->id();
        $likedPageIds = PageLike::query()
            ->where('user_id', $userId)
            ->pluck('page_id');

        $page_data['mypages'] = $this->pageCardQuery($userId)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(5)
            ->get();
        $page_data['suggestedpages'] = $this->pageCardQuery($userId)
            ->whereNotIn('id', $likedPageIds)
            ->orderByDesc('id')
            ->limit(10)
            ->get();
        $page_data['likedpage'] = $this->pageCardQuery($userId)
            ->whereIn('id', $likedPageIds)
            ->orderByDesc('id')
            ->limit(10)
            ->get();
        $page_data['view_path'] = 'frontend.pages.pages';

        return view('frontend.index', $page_data);
    }

    public function store(StorePageRequest $request)
    {
        $validated = $request->validated();

        $file_name = null;
        if ($request->hasFile('image')) {
            $file_name = FileUploader::upload($request->file('image'), 'public/storage/pages/logo', 250);
        }

        $page = new Page;
        $page->user_id = auth()->id();
        $page->title = $validated['name'];
        $page->category_id = $validated['category'];
        $page->description = $validated['description'] ?? null;
        if ($file_name !== null) {
            $page->logo = $file_name;
        }
        $done = $page->save();
        if ($done) {
            // $pagelike = new PageLike();
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

    public function update(UpdatePageRequest $request, $id)
    {
        $validated = $request->validated();
        $page = Page::findOrFail($id);

        Gate::authorize('update', $page);

        $imagename = $page->logo;
        $file_name = null;
        if ($request->hasFile('image')) {
            $file_name = FileUploader::upload($request->file('image'), 'public/storage/pages/logo', 250);
        }

        $page->title = $validated['name'];
        $page->category_id = $validated['category'];
        $page->description = $validated['description'] ?? null;
        if ($file_name !== null) {
            $page->logo = $file_name;
        }
        $done = $page->save();
        if ($done) {
            if ($request->hasFile('image')) {
                if (File::exists(public_path('storage/pages/logo/'.$imagename))) {
                    File::delete(public_path('storage/pages/logo/'.$imagename));
                }
            }
        }
        Session::flash('success_message', get_phrase('Page Updated Successfully'));

        return json_encode(['reload' => 1]);
    }

    public function updatecoverphoto(UpdatePageCoverPhotoRequest $request, $id)
    {
        $page = Page::findOrFail($id);

        Gate::authorize('update', $page);

        $imagename = $page->coverphoto;

        if ($request->hasFile('cover_photo')) {
            $file_name = FileUploader::upload($request->file('cover_photo'), 'public/storage/pages/coverphoto', 1120);

            $page->coverphoto = $file_name;
        }
        $done = $page->save();
        if ($done) {
            if ($request->hasFile('cover_photo')) {
                if (File::exists(public_path('storage/pages/coverphoto/'.$imagename))) {
                    File::delete(public_path('storage/pages/coverphoto/'.$imagename));
                }
            }
        }
        Session::flash('success_message', get_phrase('Cover Photo Updated Successfully'));

        return json_encode(['reload' => 1]);
    }

    public function updateinfo(UpdatePageInfoRequest $request, $id)
    {
        $validated = $request->validated();
        $page = Page::findOrFail($id);

        Gate::authorize('update', $page);

        $page->job = $validated['job'] ?? null;
        $page->lifestyle = $validated['lifestyle'] ?? null;
        $page->location = $validated['location'] ?? null;
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

        $all_videos = MediaFile::where('page_id', $id)
            ->ofType(MediaFileType::Video)
            ->take(20)->orderBy('id', 'DESC')->get();

        $page_data['all_videos'] = $all_videos;

        $all_photos = MediaFile::where('page_id', $id)
            ->take(30)->orderBy('id', 'DESC')->get();
        $page_data['all_photos'] = $all_photos;

        $posts = Posts::notPrivate()
            ->forPublisher('page', $id)
            ->active()
            ->join('pages', 'posts.publisher_id', '=', 'pages.id')
            ->select('posts.*', 'pages.id', 'pages.title', 'pages.logo', 'posts.created_at as created_at')
            ->orderBy('posts.post_id', 'DESC')->get();

        $page_data['posts'] = $posts;
        $page_data['suggestedpages'] = PageLike::whereIn('user_id', $friendsid)->where('user_id', '!=', auth()->user()->id)->limit('1')->get();
        $page_data['page'] = Page::find($id);

        $page_data['friendships'] = FriendshipsQuery::importantForUser(auth()->user())
            ->take(15)->get();

        $page_data['view_path'] = 'frontend.pages.page-timeline';

        return view('frontend.index', $page_data);
    }

    public function page_photos($id)
    {
        $friendsid = FriendshipsQuery::acceptedFriendIdsForUser(auth()->user());

        $all_photos = MediaFile::where('page_id', $id)
            ->ofType(MediaFileType::Image)
            ->take(20)->orderBy('id', 'DESC')->get();

        $all_albums = Albums::where('page_id', $id)
            ->take(6)->orderBy('id', 'DESC')->get();

        $page_data['all_videos'] = MediaFile::where('page_id', $id)
            ->ofType(MediaFileType::Video)
            ->take(20)->orderBy('id', 'DESC')->get();

        $page_data['all_photos'] = $all_photos;
        $page_data['all_albums'] = $all_albums;
        $page_data['page_identifire'] = 'page';
        $page_data['page'] = Page::find($id);

        $page_data['suggestedpages'] = PageLike::whereIn('user_id', $friendsid)->where('user_id', '!=', auth()->user()->id)->limit('1')->get();
        $page_data['view_path'] = 'frontend.pages.photos';

        return view('frontend.index', $page_data);
    }

    public function videos($id)
    {
        $friendsid = FriendshipsQuery::acceptedFriendIdsForUser(auth()->user());

        $all_videos = MediaFile::where('page_id', $id)
            ->ofType(MediaFileType::Video)
            ->take(20)->orderBy('id', 'DESC')->get();

        $page_data['all_videos'] = $all_videos;

        $page_data['page'] = Page::find($id);
        $all_photos = MediaFile::where('page_id', $id)
            ->ofType(MediaFileType::Image)
            ->take(20)->orderBy('id', 'DESC')->get();
        $page_data['all_photos'] = $all_photos;

        $page_data['suggestedpages'] = PageLike::whereIn('user_id', $friendsid)->where('user_id', '!=', auth()->user()->id)->limit('1')->get();
        $page_data['view_path'] = 'frontend.pages.video';

        return view('frontend.index', $page_data);
    }

    public function load_videos(Request $request)
    {
        $all_videos = MediaFile::where('user_id', $this->user->id)
            ->ofType(MediaFileType::Video)
            ->skip($request->offset)->take(12)->orderBy('id', 'DESC')->get();

        $page_data['all_videos'] = $all_videos;
        $page_data['user_info'] = $this->user;

        return view('frontend.profile.video_single', $page_data);
    }

    private function pageCardQuery(int $userId): Builder
    {
        return Page::query()
            ->select([
                'id',
                'user_id',
                'title',
                'category_id',
                'logo',
                'coverphoto',
                'description',
                'job',
                'lifestyle',
                'location',
                'status',
                'created_at',
                'updated_at',
            ])
            ->withCount('likedByUsers')
            ->withExists([
                'likedByUsers as liked_by_current_user' => fn (Builder $query): Builder => $query
                    ->where('users.id', $userId),
            ]);
    }

    public function like($id)
    {
        $response = [];
        $userId = auth()->user()->id;

        if (! PageLike::where('page_id', $id)->where('user_id', $userId)->exists()) {
            $pagelike = new PageLike;
            $pagelike->page_id = $id;
            $pagelike->user_id = $userId;
            $pagelike->role = MembershipRole::General->value;
            $pagelike->save();
        }

        Session::flash('success_message', get_phrase('Page Liked Successfully'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function dislike($id)
    {
        $response = [];
        $user_id = auth()->user()->id;
        $pagelike = PageLike::where('page_id', $id)->where('user_id', $user_id)->first();

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
