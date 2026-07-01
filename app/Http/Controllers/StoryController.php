<?php

namespace App\Http\Controllers;

use App\Models\FileUploader;
use App\Models\Media_files;
use App\Models\Stories;
use App\Queries\StoriesQuery;
use Illuminate\Http\Request;
use Session;

class StoryController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth()->user();

            return $next($request);
        });
    }

    public function stories($offset = 0, $limit = 5)
    {
        $stories = StoriesQuery::visibleFor($this->user)
            ->skip($offset)
            ->take($limit)
            ->get();

        $page_data['stories'] = $stories;

        return view('frontend.story.single_story', $page_data);
    }

    public function story_details($story_id = '', $offset = 0, $limit = 10)
    {
        $story_details = StoriesQuery::findWithOwner($story_id);

        abort_if($story_details === null, 404);

        $stories = StoriesQuery::visibleFor($this->user)
            ->where('stories.privacy', '!=', 'private')
            ->whereNotIn('stories.story_id', [$story_id])
            ->get();

        $page_data['stories'] = $stories;
        $page_data['story_details'] = $story_details;

        return view('frontend.story.story_details', $page_data);
    }

    public function single_story_details($story_id = '')
    {
        $story_details = StoriesQuery::findWithOwner($story_id);

        abort_if($story_details === null, 404);

        $page_data['story_details'] = $story_details;

        return view('frontend.story.single_story_details', $page_data);
    }

    public function create_story(Request $request)
    {
        $all_data = $request->all();

        $data['publisher'] = $all_data['publisher'];
        $data['content_type'] = $all_data['content_type'];

        if ($request->publisher == 'user') {
            $data['publisher_id'] = $this->user->id;
        } else {
            $data['publisher_id'] = $this->user->id;
        }

        if ($request->content_type == 'text') {
            if (! empty($request->description)) {
                $data['description'] = json_encode(
                    ['color' => $all_data['color'], 'bg-color' => $all_data['bg-color'], 'text' => $all_data['description']]
                );
            } else {
                return redirect()->route('timeline');
            }
        }

        $data['privacy'] = $request->privacy;
        $data['created_at'] = time();
        $data['updated_at'] = $data['created_at'];
        $data['user_id'] = $this->user->id;
        $data['status'] = 'active';
        $story_id = Stories::insertGetId($data);

        if ($request->content_type != 'text') {
            if ($request->story_files == '') {
                Session::flash('error_message', get_phrase('Please Select atlist one image!'));

                return redirect()->route('timeline');
            }

            // add media files
            foreach ($request->story_files as $key => $media_file) {
                if (! empty($media_file)) {
                    $file_extention = $media_file->getClientOriginalExtension();
                    if ($file_extention == 'avi' || $file_extention == 'mp4' || $file_extention == 'webm' || $file_extention == 'mov' || $file_extention == 'wmv' || $file_extention == 'mkv') {
                        $file_name = FileUploader::upload($media_file, 'public/storage/story/videos');
                        $file_type = 'video';
                    } else {
                        $file_name = FileUploader::upload($media_file, 'public/storage/story/images', 800);
                        $file_type = 'image';
                    }

                    $media_file_data = ['user_id' => $this->user->id, 'story_id' => $story_id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => $request->privacy];
                    $media_file_data['created_at'] = time();
                    $media_file_data['updated_at'] = $media_file_data['created_at'];
                    Media_files::create($media_file_data);
                }
            }
        }

        return redirect()->route('timeline');
    }
}
