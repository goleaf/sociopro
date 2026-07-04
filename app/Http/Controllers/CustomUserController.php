<?php

namespace App\Http\Controllers;

use App\Actions\Friends\SendFriendRequestAction;
use App\Enums\MediaFileType;
use App\Enums\Visibility;
use App\Models\Albums;
use App\Models\Follower;
use App\Models\Friendships;
use App\Models\MediaFile;
use App\Models\Notification;
use App\Models\Posts;
use App\Models\User;
use App\Queries\FriendshipsQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Session;

class CustomUserController extends Controller
{
    // change pass
    public function changepass()
    {
        return view('frontend.user.change-password');
    }

    public function updatepass(Request $request)
    {
        $request->validate([
            'prevpass' => 'required',
            'password' => 'required|confirmed|min:8|different:prevpass',
        ]);
        if (Hash::check($request->prevpass, auth()->user()->password)) {
            $user = User::find(auth()->user()->id);
            $user->password = Hash::make($request->password);
            $user->save();
            Session::flash('success_message', get_phrase('Password Changed Successfully'));

            return redirect()->route('timeline');
        } else {
            Session::flash('success_message', get_phrase('Previous Password does not Match, Try Again'));

            return redirect()->route('timeline');
        }
    }

    public function view_profile_data($id)
    {
        $posts = Posts::forUser($id)
            ->where('publisher', 'post')
            ->publiclyVisible()
            ->orderBy('post_id', 'DESC')
            ->limit('10')
            ->get();

        $page_data['friendships'] = FriendshipsQuery::importantForUser(auth()->user())->get();

        $page_data['posts'] = $posts;
        $page_data['user_data'] = User::find($id);
        $page_data['view_path'] = 'frontend.user.single_user.user_view';

        return view('frontend.index', $page_data);
    }

    public function load_post_by_scrolling(Request $request)
    {
        $friendships = FriendshipsQuery::importantForUser(auth()->user())->get();

        $posts = Posts::forUser($request->id)
            ->where('publisher', 'post')
            ->publiclyVisible()
            ->skip($request->offset)
            ->take(3)
            ->orderBy('post_id', 'DESC')
            ->get();

        $page_data['friendships'] = $friendships;
        $page_data['posts'] = $posts;
        $page_data['user_info'] = User::find($request->id);

        return view('frontend.main_content.posts', $page_data);
    }

    public function friend(Request $request, SendFriendRequestAction $sendFriendRequest, $id)
    {
        $response = [];
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $sendFriendRequest->handle($user, (int) $id);

        Session::flash('success_message', get_phrase('Friend Request Sent Successfully'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function unfriend($id)
    {
        $response = [];

        // Remove the friendship from the friendships table
        Friendships::where(function ($query) use ($id) {
            $query->where('accepter', $id)
                ->where('requester', auth()->user()->id);
        })->orWhere(function ($query) use ($id) {
            $query->where('requester', $id)
                ->where('accepter', auth()->user()->id);
        })->delete();

        // Update the unfriended user's friends list
        $unfriended_user_friends = User::where('id', $id)->value('friends');
        $unfriended_user_friends = json_decode($unfriended_user_friends, true);

        if (is_array($unfriended_user_friends)) {
            $array_key = array_search(auth()->user()->id, $unfriended_user_friends, true);
            if ($array_key !== false) {
                unset($unfriended_user_friends[$array_key]);
            }
            // Reindex the array to maintain sequential keys
            $unfriended_user_friends = array_values($unfriended_user_friends);
        } else {
            $unfriended_user_friends = [];
        }
        $unfriended_user_friends = json_encode($unfriended_user_friends);
        User::where('id', $id)->update(['friends' => $unfriended_user_friends]);

        // Update my friends list
        $my_friends = User::where('id', auth()->user()->id)->value('friends');
        $my_friends = json_decode($my_friends, true);

        if (is_array($my_friends)) {
            $array_key = array_search($id, $my_friends, true);
            if ($array_key !== false) {
                unset($my_friends[$array_key]);
            }
            // Reindex the array to maintain sequential keys
            $my_friends = array_values($my_friends);
        } else {
            $my_friends = [];
        }
        $my_friends = json_encode($my_friends);
        User::where('id', auth()->user()->id)->update(['friends' => $my_friends]);

        // Remove notifications between these users
        Notification::where('sender_user_id', auth()->user()->id)
            ->where('reciver_user_id', $id)
            ->delete();

        // Optionally, you might want to remove notifications the other way as well
        Notification::where('sender_user_id', $id)
            ->where('reciver_user_id', auth()->user()->id)
            ->delete();

        $follwer = Follower::where('follow_id', $id)
            ->where('user_id', auth()->id())
            ->delete();
        // Provide feedback to the user
        Session::flash('success_message', get_phrase('Removed from friend list'));

        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function friends($id)
    {
        $friendships = FriendshipsQuery::importantForUser((int) $id)->get();

        $friend_requests = Friendships::where('accepter', $id)
            ->where('is_accepted', '!=', 1)
            ->orderByDesc('id')
            ->take(15)->get();

        $page_data['friendships'] = $friendships;
        $page_data['friend_requests'] = $friend_requests;

        $page_data['user_data'] = User::find($id);
        $page_data['view_path'] = 'frontend.user.single_user.user_view';

        return view('frontend.index', $page_data);
    }

    public function photos($id)
    {
        $all_photos = MediaFile::where('user_id', $id)
            ->ofType(MediaFileType::Image)
            ->whereNull('page_id')
            ->whereNull('story_id')
            ->whereNull('product_id')
            ->whereNull('group_id')
            ->whereNull('chat_id')
            ->orderBy('id', 'DESC')->get();

        $all_albums = Albums::where('user_id', $id)
            ->whereNull('page_id')
            ->whereNull('group_id')
            ->take(6)->orderBy('id', 'DESC')->get();

        $page_data['all_photos'] = $all_photos;
        $page_data['all_albums'] = $all_albums;
        $page_data['user_data'] = User::find($id);

        $page_data['page_identifire'] = 'customer';

        $page_data['view_path'] = 'frontend.user.single_user.user_view';

        return view('frontend.index', $page_data);
    }

    public function videos($id)
    {
        $all_videos = MediaFile::where('user_id', $id)
            ->ofType(MediaFileType::Video)
            ->whereNull('story_id')
            ->whereNull('page_id')
            ->whereNull('album_id')
            ->whereNull('product_id')
            ->whereNull('chat_id')
            ->orderBy('id', 'DESC')->get();

        $page_data['all_videos'] = $all_videos;
        $page_data['user_data'] = User::find($id);
        $page_data['view_path'] = 'frontend.user.single_user.user_view';

        return view('frontend.index', $page_data);
    }

    public function delete_mediafile($id)
    {
        $response = [];
        $media_file = MediaFile::find($id);
        if (! $media_file instanceof MediaFile) {
            abort(404);
        }

        abort_unless($this->canManageMediaFile($media_file), 403);

        $mediaType = MediaFileType::tryFrom((string) $media_file->file_type);
        $filePath = $mediaType instanceof MediaFileType
            ? $this->mediaDownloadPath($media_file, $mediaType)
            : null;
        if ($filePath !== null) {
            File::delete($filePath);

            $optimizedPath = dirname($filePath).DIRECTORY_SEPARATOR.'optimized'.DIRECTORY_SEPARATOR.basename($filePath);
            if (is_file($optimizedPath)) {
                File::delete($optimizedPath);
            }
        }

        $media_file->delete();
        Session::flash('success_message', get_phrase('Deleted successfully'));
        $response = ['reload' => 1];

        return json_encode($response);
    }

    public function download_mediafile($id)
    {
        $media_file = MediaFile::find($id);

        return $this->downloadMediaFile($media_file, MediaFileType::Video);
    }

    public function download_mediafile_image($id)
    {
        $media_file = MediaFile::find($id);

        return $this->downloadMediaFile($media_file, MediaFileType::Image);
    }

    public function account_status($id)
    {
        $user = Auth::user();
        abort_unless($user instanceof User && (int) $user->id === (int) $id, 403);

        $user->deactivateAccount();

        Auth::logout();

        flash()->addSuccess('Your account has been deactivated. You have been logged out.');

        return json_encode(['url' => route('login')]);
    }

    private function downloadMediaFile(?MediaFile $mediaFile, MediaFileType $expectedType)
    {
        if (! $mediaFile instanceof MediaFile) {
            abort(404);
        }

        abort_unless($this->canDownloadMediaFile($mediaFile), 403);

        $filePath = $this->mediaDownloadPath($mediaFile, $expectedType);
        if ($filePath === null) {
            abort(404);
        }

        return Response::download($filePath, basename($filePath));
    }

    private function canDownloadMediaFile(MediaFile $mediaFile): bool
    {
        if ($this->canManageMediaFile($mediaFile)) {
            return true;
        }

        $post = $mediaFile->post;

        return $mediaFile->privacy === Visibility::Public->value
            || ($post instanceof Posts && $post->privacy === Visibility::Public->value);
    }

    private function canManageMediaFile(MediaFile $mediaFile): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        if ((int) $mediaFile->user_id === (int) $user->id) {
            return true;
        }

        $post = $mediaFile->post;

        return $post instanceof Posts && (int) $post->user_id === (int) $user->id;
    }

    private function mediaDownloadPath(MediaFile $mediaFile, MediaFileType $expectedType): ?string
    {
        if ($mediaFile->file_type !== $expectedType->value) {
            return null;
        }

        $fileName = (string) $mediaFile->file_name;
        if (! $this->isSafeMediaFileName($fileName)) {
            return null;
        }

        $directory = $expectedType === MediaFileType::Video ? 'post/videos' : 'post/images';
        $root = realpath(public_path('storage/'.$directory));
        $file = realpath(public_path('storage/'.$directory.'/'.$fileName));

        if ($root === false || $file === false || ! is_file($file)) {
            return null;
        }

        return str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
            ? $file
            : null;
    }

    private function isSafeMediaFileName(string $fileName): bool
    {
        if ($fileName === ''
            || str_contains($fileName, "\0")
            || str_starts_with($fileName, '/')
            || str_contains($fileName, '\\')
            || ! preg_match('/\A[A-Za-z0-9._\/-]+\z/', $fileName)) {
            return false;
        }

        foreach (explode('/', $fileName) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }
}
