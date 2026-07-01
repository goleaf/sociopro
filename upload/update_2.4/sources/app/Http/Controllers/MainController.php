<?php

namespace App\Http\Controllers;

use App\Models\Comments;

//used models
use App\Models\Album_image;
use App\Models\FileUploader;
use App\Models\Live_streamings;
use App\Models\Media_files;
use App\Models\Posts;
use App\Models\Post_share;
use App\Models\Report;
use App\Models\Stories;
use App\Models\User;
use App\Models\BlockUser;
use App\Models\Friendships;
use App\Traits\ZoomMeetingTrait;
use DB;
use Illuminate\Database\Query\JoinClause;
use Str;

use Carbon\Carbon;

//For used ZOOM
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

//Used for Form data validation

class MainController extends Controller
{
    use ZoomMeetingTrait;

    const MEETING_TYPE_INSTANT = 1;
    const MEETING_TYPE_SCHEDULE = 2;
    const MEETING_TYPE_RECURRING = 3;
    const MEETING_TYPE_FIXED_RECURRING_FIXED = 8;

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

    public function timeline()
    {

        //First 10 stories
        $stories = Stories::where(function ($query) {
            $query->whereJsonContains('users.friends', [$this->user->id])
                ->where('stories.privacy', '!=', 'private')
                ->orWhere('stories.user_id', $this->user->id);
        })
            ->where('stories.status', 'active')
            ->where('stories.created_at', '>=', (time() - 86400))
            ->join('users', 'stories.user_id', '=', 'users.id')
            ->select('stories.*', 'users.name', 'users.photo', 'users.friends', 'stories.created_at as created_at')
            ->take(5)->orderBy('stories.story_id', 'DESC')->get();

        //First 10 posts
        $posts = Posts::where(function ($query) {
            $query->whereJsonContains('users.friends', [$this->user->id])
                ->where('posts.privacy', '!=', 'private')
                ->orWhere('posts.user_id', $this->user->id)



                //if folowing any users, pages, groups and others if not friend listed
                ->orWhere(function ($query3) {
                    $query3->where('posts.privacy', 'public')
                        ->where(function ($query4) {
                            $query4->where('posts.publisher', 'post')
                                ->join('followers', function (JoinClause $join) {
                                    $join->on('posts.publisher_id', '=', 'followers.follow_id')
                                        ->where('followers.user_id', auth()->user()->id);
                                });
                        })
                        ->orWhere(function ($query5) {
                            $query5->where('posts.publisher', 'profile_picture')
                                ->join('followers', function (JoinClause $join1) {
                                    $join1->on('posts.publisher_id', '=', 'followers.follow_id')
                                        ->where('followers.user_id', auth()->user()->id);
                                });
                        })
                        ->orWhere(function ($query6) {
                            $query6->where('posts.publisher', 'page')
                                ->join('followers', function (JoinClause $join2) {
                                    $join2->on('posts.publisher_id', '=', 'followers.page_id')
                                        ->where('followers.user_id', auth()->user()->id);
                                });
                        })
                        ->orWhere(function ($query7) {
                            $query7->where('posts.publisher', 'group')
                                ->join('followers', function (JoinClause $join3) {
                                    $join3->on('posts.publisher_id', '=', 'followers.group_id')
                                        ->where('followers.user_id', auth()->user()->id);
                                });
                        });
                });
        })
            ->where('posts.status', 'active')
            ->where('posts.report_status', '0')
            ->where('publisher', '!=', 'paid_content') // post type can not be paid content
            ->join('users', 'posts.user_id', '=', 'users.id')

            ->where(function ($query) {
                $query->where('posts.publisher', '!=', 'video_and_shorts')
                    ->orWhere(function ($query2) {
                        $query2->join('group_members', function (JoinClause $join) {
                            $join->on('posts.publisher_id', '=', 'group_members.group_id')
                                ->where('posts.publisher', '=', 'group')
                                ->where('group_members.user_id', '=', auth()->user()->id);
                        });
                    });
            })

            ->select('posts.*', 'users.name', 'users.photo', 'users.friends', 'posts.created_at as created_at')
            ->take(15)->orderBy('posts.post_id', 'DESC')->get();


            // New
            $friendships = Friendships::where(function ($query) {
                $query->where('accepter', auth()->user()->id)
                    ->orWhere('requester', auth()->user()->id);
            })
                ->where('is_accepted', 1)
                ->orderBy('friendships.importance', 'desc')
                ->get();

            $page_data['friendships'] = $friendships;
          //new
        
        $page_data['stories'] = $stories;
        $page_data['posts'] = $posts;
        $page_data['view_path'] = 'frontend.main_content.index';
        return view('frontend.index', $page_data);
    }

    public function load_post_by_scrolling(Request $request)
    {
     
        $posts = Posts::where(function ($query) {
            $query->whereJsonContains('users.friends', [$this->user->id])
                ->where('posts.privacy', '!=', 'private')
                ->orWhere('posts.user_id', $this->user->id)

                //if following any users, pages, groups and others if not friend listed
                ->orWhere(function ($query3) {
                    $query3->where('posts.privacy', 'public')
                        ->where(function ($query4) {
                            $query4->where('posts.publisher', 'post')
                                ->join('followers', function (JoinClause $join) {
                                    $join->on('posts.publisher_id', '=', 'followers.follow_id')
                                        ->where('followers.user_id', auth()->user()->id);
                                });
                        })
                        ->orWhere(function ($query5) {
                            $query5->where('posts.publisher', 'profile_picture')
                                ->join('followers', function (JoinClause $join1) {
                                    $join1->on('posts.publisher_id', '=', 'followers.follow_id')
                                        ->where('followers.user_id', auth()->user()->id);
                                });
                        })
                        ->orWhere(function ($query6) {
                            $query6->where('posts.publisher', 'page')
                                ->join('followers', function (JoinClause $join2) {
                                    $join2->on('posts.publisher_id', '=', 'followers.page_id')
                                        ->where('followers.user_id', auth()->user()->id);
                                });
                        })
                        ->orWhere(function ($query7) {
                            $query7->where('posts.publisher', 'group')
                                ->join('followers', function (JoinClause $join3) {
                                    $join3->on('posts.publisher_id', '=', 'followers.group_id')
                                        ->where('followers.user_id', auth()->user()->id);
                                });
                        });
                });
        })
            ->where('posts.status', 'active')
            ->where('posts.publisher', 'post')
            ->where('posts.report_status', '0')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name', 'users.photo', 'users.friends', 'posts.created_at as created_at')
            ->skip($request->offset)->take(3)->orderBy('posts.post_id', 'DESC')->get();
           // New
            $friendships = Friendships::where(function ($query) {
                $query->where('accepter', auth()->user()->id)
                    ->orWhere('requester', auth()->user()->id);
            })
                ->where('is_accepted', 1)
                ->orderBy('friendships.importance', 'desc')
                ->get();

            $page_data['friendships'] = $friendships;
          //new    
          
        $page_data['user_info'] = $this->user;
        $page_data['posts'] = $posts;
        $page_data['type'] = 'user_post';
        return view('frontend.main_content.posts', $page_data);
    }

    public function create_post(Request $request)
    {

        //Data validation

        $rules = array('privacy' => ['required', Rule::in(['private', 'public', 'friends'])]);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
        }

        if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
            //Data validation

            $rules = array('multiple_files.*' => 'mimes:jpeg,png,jpg,gif,svg,mp4,mov,wmv,avi,webm|max:500000');
            // $rules = array('multiple_files.*' => 'mimes:mp4,mov,wmv,avi,WEBM,mkv|max:20048');
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $validation_errors = $validator->getMessageBag()->toArray();
                foreach ($validation_errors as $key => $validation_error) {
                    $fileIndex = explode('.', $key);
                    if (array_key_exists('multiple_files.' . $fileIndex[1], $validation_errors)) {
                        $validation_errors['multiple_files'] = $validation_errors['multiple_files.' . $fileIndex[1]];
                    }
                    unset($validation_errors['multiple_files.' . $fileIndex[1]]);
                }

                return json_encode(array('validationError' => $validation_errors));
            }
        }

        $data['user_id'] = $this->user->id;
        $data['privacy'] = $request->privacy;

        if (isset($request->publisher) && !empty($request->publisher)) {
            $data['publisher'] = $request->publisher;
        } else {
            $data['publisher'] = 'post';
        }

        if (isset($request->event_id) && !empty($request->event_id)) {
            $data['publisher_id'] = $request->event_id;
        } elseif (isset($request->page_id) && !empty($request->page_id)) {
            $data['publisher_id'] = $request->page_id;
        } elseif (isset($request->group_id) && !empty($request->group_id)) {
            $data['publisher_id'] = $request->group_id;
        } else {
            $data['publisher_id'] = $this->user->id;
        }
        //post type
        if (isset($request->post_type) && !empty($request->post_type)) {
            $data['post_type'] = $request->post_type;
        } else {
            $data['post_type'] = 'general';
        }

        if (isset($request->tagged_users_id) && is_array($request->tagged_users_id)) {
            $tagged_users = $request->tagged_users_id;
        } else {
            $tagged_users = array();
        }
        $data['tagged_user_ids'] = json_encode($tagged_users);

        if (isset($request->feeling_and_activity_id) && !empty($request->feeling_and_activity_id)) {
            $data['activity_id'] = $request->feeling_and_activity_id;
        } else {
            $data['activity_id'] = 0;
        }

        if (isset($request->address) && !empty($request->address)) {
            $data['location'] = $request->address;
        } else {
            $data['location'] = '';
        }


        if (isset($request->description) && !empty($request->description)) {
            preg_match_all('/#(\w+)/', $request->description, $matchesHashtags); // Extract hashtags
            preg_match_all('/\b(?:https?|ftp):\/\/\S+/', $request->description, $matchesUrls); // Extract URLs
        
            $data['description'] = nl2br($request->description);
        
            
        
            if (!empty($matchesUrls[0])) {
                foreach ($matchesUrls[0] as $url) {
                    $urlLink = '<a href="' . $url . '" class="url-link hashtag-link" target="_blank">' . $url . '</a>';
                    $data['description'] = str_replace($url, $urlLink, $data['description']);
                }
            }

            if (!empty($matchesHashtags[1])) {
                $hashtags = '#' . implode(', #', $matchesHashtags[1]);
                $data['hashtag'] = $hashtags;
        
                foreach ($matchesHashtags[1] as $tag) {
                    $tagLink = '<a href="' . route('search', ['search' => $tag]) . '" class="hashtag-link">#' . $tag . '</a>';
                    $data['description'] = str_replace("#$tag", $tagLink, $data['description']);
                }
            } else {
                $data['hashtag'] = '';
            }
        } else {
            $data['description'] = '';
            $data['hashtag'] = '';
        }
        // Mobile App View Image
        $mobile_app_image = FileUploader::upload($request->mobile_app_image,'public/storage/post/images/');
        $data['mobile_app_image'] = $mobile_app_image;


        $data['status'] = 'active';
        $data['user_reacts'] = json_encode(array());
        $data['shared_user'] = json_encode(array());
        $data['created_at'] = time();
        $data['updated_at'] = $data['created_at'];

        $post_id = Posts::insertGetId($data);

        //add media files
        if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
            //Data validation

            $rules = array('multiple_files.*' => 'mimes:jpeg,png,jpg,gif,svg,mp4,mov,wmv,avi,webm|max:500000');
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $validation_errors = $validator->getMessageBag()->toArray();
                foreach ($validation_errors as $key => $validation_error) {
                    $fileIndex = explode('.', $key);
                    if (array_key_exists('multiple_files.' . $fileIndex[1], $validation_errors)) {
                        $validation_errors['multiple_files'] = $validation_errors['multiple_files.' . $fileIndex[1]];
                    }
                    unset($validation_errors['multiple_files.' . $fileIndex[1]]);
                }

                return json_encode(array('validationError' => $validation_errors));
            }

            foreach ($request->multiple_files as $key => $media_file) {
                $file_name = random(40);
                $file_extention = strtolower($media_file->getClientOriginalExtension());
                if ($file_extention == 'avi' || $file_extention == 'mp4' || $file_extention == 'webm' || $file_extention == 'mov' || $file_extention == 'wmv' || $file_extention == 'mkv') {
                    $file_name = FileUploader::upload($media_file, 'public/storage/post/videos/' . $file_name . '.' . $file_extention);
                    $file_type = 'video';
                } else {
                    $file_name = FileUploader::upload($media_file, 'public/storage/post/images/' . $file_name . '.' . $file_extention, 1000, null, 300);
                    $file_type = 'image';
                }
                // $file_name = $file_name . '.' . $file_extention;

                $media_file_data = array('user_id' => auth()->user()->id, 'post_id' => $post_id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => $request->privacy);

                if (isset($request->page_id) && !empty($request->page_id)) {
                    $media_file_data['page_id'] = $request->page_id;
                } elseif (isset($request->group_id) && !empty($request->group_id)) {
                    $media_file_data['group_id'] = $request->group_id;
                } else {
                }
                $media_file_data['created_at'] = time();
                $media_file_data['updated_at'] = $media_file_data['created_at'];
                Media_files::create($media_file_data);
            }
        }

        if ($data['post_type'] == 'live_streaming') {
            //Live streaming
            $live['publisher'] = $data['publisher'];
            $live['publisher_id'] = $post_id;
            $live['user_id'] = auth()->user()->id;
            $live['details'] = json_encode(['link' => url('/streaming/live/' . $post_id), 'status' => TRUE]);
            $live['created_at'] = date('Y-m-d H:i:s', time());
            $live['updated_at'] = $live['created_at'];
  
            Live_streamings::insert($live);
            $response = array('open_new_tab' => url('/streaming/live/' . $post_id), 'reload' => 0, 'status' => 1, 'function' => 0, 'messageShowOn' => '[name=about]', 'message' => get_phrase('Post has been added to your timeline'));
        } else {
            //Ajax flush message
            Session::flash('success_message', get_phrase('Your post has been published'));
            $response = array('reload' => 1);
        }
        return json_encode($response);
    }

    public function live_streaming($post_id)
    {
        $user_id = Posts::where('post_id', $post_id)->value('user_id');
        $user = User::where('id', $user_id)->first();
        $make_pass = str_shuffle($user->name . $user->email);
        $make_pass = explode(' ', $make_pass);
        $join_pass = implode('', $make_pass);

        Live_streamings::where('publisher_id', $user_id)->update(['details->join_pass' => $join_pass]);

       // $room = get_settings('system_name') . $user->name . $user->email;
        $room = get_settings('system_name');
        return view('frontend.main_content.jitsi_streaming', compact('user', 'join_pass', 'room'));
    }

    public function edit_post_form($id)
    {
        $page_data['post'] = Posts::where('post_id', $id)->first();
        return view('frontend.main_content.edit_post_modal', $page_data);
    }

    public function edit_post($id, Request $request)
    {
        //$posts = Posts::where('id', $id)->first();

        $rules = array('privacy' => ['required', Rule::in(['private', 'public', 'friends'])]);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
        }

        if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
            //Data validation

            $rules = array('multiple_files.*' => 'mimes:jpeg,png,jpg,gif,svg,mp4,mov,wmv,avi,webm|max:20480');
            // $rules = array('multiple_files.*' => 'mimes:mp4,mov,wmv,avi,WEBM,mkv|max:20048');
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $validation_errors = $validator->getMessageBag()->toArray();
                foreach ($validation_errors as $key => $validation_error) {
                    $fileIndex = explode('.', $key);
                    if (array_key_exists('multiple_files.' . $fileIndex[1], $validation_errors)) {
                        $validation_errors['multiple_files'] = $validation_errors['multiple_files.' . $fileIndex[1]];
                    }
                    unset($validation_errors['multiple_files.' . $fileIndex[1]]);
                }

                return json_encode(array('validationError' => $validation_errors));
            }
        }

        $data['privacy'] = $request->privacy;

        if (isset($request->tagged_users_id) && is_array($request->tagged_users_id)) {
            $tagged_users = $request->tagged_users_id;
            $data['tagged_user_ids'] = json_encode($tagged_users);
        }

        if (isset($request->feeling_and_activity_id) && !empty($request->feeling_and_activity_id)) {
            $data['activity_id'] = $request->feeling_and_activity_id;
        }


        
        if (isset($request->description) && !empty($request->description)) {
            preg_match_all('/#(\w+)/', $request->description, $matchesHashtags); // Extract hashtags
            preg_match_all('/\b(?:https?|ftp):\/\/\S+/', $request->description, $matchesUrls); // Extract URLs
        
            $data['description'] = nl2br($request->description);
        
            
        
            if (!empty($matchesUrls[0])) {
                foreach ($matchesUrls[0] as $url) {
                    $urlLink = '<a href="' . $url . '" class="url-link hashtag-link" target="_blank">' . $url . '</a>';
                    $data['description'] = str_replace($url, $urlLink, $data['description']);
                }
            }

            if (!empty($matchesHashtags[1])) {
                $hashtags = '#' . implode(', #', $matchesHashtags[1]);
                $data['hashtag'] = $hashtags;
        
                foreach ($matchesHashtags[1] as $tag) {
                    $tagLink = '<a href="' . route('search', ['search' => $tag]) . '" class="hashtag-link">#' . $tag . '</a>';
                    $data['description'] = str_replace("#$tag", $tagLink, $data['description']);
                }
            } else {
                $data['hashtag'] = '';
            }
        } else {
            $data['description'] = '';
            $data['hashtag'] = '';
        }



        // Mobile Preview Upload Image
        $mobile_app_image = FileUploader::upload($request->mobile_app_image,'public/storage/post/images/');
        $data['mobile_app_image'] = $mobile_app_image;

        $data['updated_at'] = time();

        Posts::where('post_id', $id)->update($data);

        //add media files
        if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
            //Data validation

            $rules = array('multiple_files.*' => 'mimes:jpeg,png,jpg,gif,svg,mp4,mov,wmv,avi,webm|max:20480');
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $validation_errors = $validator->getMessageBag()->toArray();
                foreach ($validation_errors as $key => $validation_error) {
                    $fileIndex = explode('.', $key);
                    if (array_key_exists('multiple_files.' . $fileIndex[1], $validation_errors)) {
                        $validation_errors['multiple_files'] = $validation_errors['multiple_files.' . $fileIndex[1]];
                    }
                    unset($validation_errors['multiple_files.' . $fileIndex[1]]);
                }

                return json_encode(array('validationError' => $validation_errors));
            }

            foreach ($request->multiple_files as $key => $media_file) {
                $file_name = random(40);
                $file_extention = strtolower($media_file->getClientOriginalExtension());
                if ($file_extention == 'avi' || $file_extention == 'mp4' || $file_extention == 'webm' || $file_extention == 'mov' || $file_extention == 'wmv' || $file_extention == 'mkv') {
                    $media_file->move('public/storage/post/videos/', $file_name . '.' . $file_extention);
                    $file_type = 'video';
                } else {
                    FileUploader::upload($media_file, 'public/storage/post/images/' . $file_name . '.' . $file_extention, 1000, null, 300);
                    $file_type = 'image';
                }
                $file_name = $file_name . '.' . $file_extention;

                $media_file_data = array('user_id' => auth()->user()->id, 'post_id' => $id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => $request->privacy);

                if (isset($request->page_id) && !empty($request->page_id)) {
                    $media_file_data['page_id'] = $request->page_id;
                } elseif (isset($request->group_id) && !empty($request->group_id)) {
                    $media_file_data['group_id'] = $request->group_id;
                } else {
                }
                $media_file_data['created_at'] = time();
                $media_file_data['updated_at'] = $media_file_data['created_at'];
                Media_files::create($media_file_data);
            }
        }

        //Ajax flush message
        Session::flash('success_message', get_phrase('Your post has been updated'));
        $response = array('reload' => 1);
        return json_encode($response);
    }

    public function create_live_streaming($publisher, $publisher_id)
    {
        // return $publisher;
        $post_details = Posts::where('posts.status', 'active')
            ->where('posts.post_id', $publisher_id)
            ->join('users', 'posts.user_id', '=', 'users.id')->first();

        $live_streaming = Live_streamings::where('publisher', $publisher)
            ->where('user_id', $this->user->id)->where('publisher_id', $publisher_id);

        if ($live_streaming->count() > 0) {
            //Update
            $meeting_details = json_decode($live_streaming->value('details'), true);
            if (!empty($post_details->description)) {
                $live_topic = ellipsis($post_details->description, 200);
            } else {
                $live_topic = "Live";
            }

            $meeting_details['topic'] = $live_topic;
            $meeting_details['start_time'] = $this->toZoomTimeFormat(time());

            $path = 'meetings/' . $meeting_details['id'];
            $response = $this->zoomPatch($path, [
                'topic' => $meeting_details['topic'],
                'type' => self::MEETING_TYPE_SCHEDULE,
                'start_time' => $meeting_details['start_time'],
                'duration' => 40,
                'agenda' => null,
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'waiting_room' => false,
                ],
            ]);

            $data['publisher'] = $publisher;
            $data['publisher_id'] = $publisher_id;
            $data['details'] = json_encode($meeting_details);
            $data['updated_at'] = time();
            Live_streamings::where('streaming_id', $live_streaming->value('streaming_id'))->update($data);
        } else {
            if (!empty($post_details->description)) {
                $live_topic = ellipsis($post_details->description, 200);
            } else {
                $live_topic = "Live";
            }

            //Create
            $path = 'users/me/meetings';
            $response = $this->zoomPost($path, [
                'topic' => $live_topic,
                'type' => self::MEETING_TYPE_SCHEDULE,
                'start_time' => $this->toZoomTimeFormat(time()),
                'duration' => 40,
                'agenda' => null,
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'waiting_room' => false,
                ],
            ]);

            $var = array('success' => $response->status() === 201, 'data' => $response->body());

            $data['publisher'] = $publisher;
            $data['publisher_id'] = $publisher_id;
            $data['user_id'] = $this->user->id;
            $data['details'] = $var['data'];
            $data['created_at'] = time();
            $data['updated_at'] = $data['created_at'];
            Live_streamings::create($data);
        }
    }

    public function live($post_id)
    {

        $post_details = Posts::where(function ($query) {
            $query->whereJsonContains('users.friends', [$this->user->id])
                ->where('posts.privacy', '!=', 'private')
                ->orWhere('posts.user_id', $this->user->id);
        })
            ->where('posts.post_id', $post_id)
            ->where('posts.status', 'active')
            ->join('users', 'posts.user_id', '=', 'users.id');

        if ($post_details->count() > 0) {

            $post_details = $post_details->first();

            $live_streaming = Live_streamings::where('publisher', 'post')
                ->where('publisher_id', $post_id)
                ->where('user_id', $post_details->user_id);

            if ($live_streaming->get()->count() > 0) {
                $live_streaming = $live_streaming->first();

                $page_data['meeting_details'] = json_decode($live_streaming->details, true);

                if ($post_details->user_id == $this->user->id) {
                    $page_data['host'] = 1;
                    $page_data['isSupportAV'] = 1;
                    $page_data['disableJoinAudio'] = 0;
                } else {
                    $page_data['host'] = 0;
                    $page_data['isSupportAV'] = 0;
                    $page_data['disableJoinAudio'] = 1;
                }
            } else {
                return redirect()->back();
            }
        } else {
            return redirect()->back();
        }

        $page_data['post_details'] = $post_details;

        return view('frontend.live_streaming.index', $page_data);
    }

    public function live_ended($post_id)
    {
        Posts::where('post_id', $post_id)->update(['description' => json_encode(['live_video_ended' => 'yes'])]);
        return redirect()->route('timeline');
    }

    public function search_friends_for_tagging(Request $request)
    {
        $friends = DB::table('users')->whereJsonContains('friends', [$this->user->id])
            ->where('name', 'like', '%' . $request->search_value . '%')
            ->take(30)->get();

        $data['friends'] = $friends;
        return view('frontend.main_content.friend_list_for_tagging', $data);
    }

    public function my_react(Request $request)
    {
        $form_data = $request->all();

        if ($form_data['type'] == 'post') {
            $post_data = Posts::where('post_id', $form_data['post_id'])->get()->first();

            $all_reacts = json_decode($post_data['user_reacts'], true);

            if ($form_data['request_type'] == 'update') {
                $all_reacts[$this->user->id] = $form_data['react'];
            }

            if ($form_data['request_type'] == 'toggle') {
                if (array_key_exists($this->user->id, $all_reacts)) {
                    unset($all_reacts[$this->user->id]);
                } else {
                    $all_reacts[$this->user->id] = 'like';
                }
            }

            $data['user_reacts'] = json_encode($all_reacts);
            Posts::where('post_id', $form_data['post_id'])->update($data);

            $page_data['user_reacts'] = $all_reacts;
            $page_data['user_info'] = $this->user;
            $page_data['ajax_call'] = true;
            $page_data['my_react'] = true;
            $page_data['post_react'] = true;

            if ($form_data['response_type'] == 'number') {
                return count($all_reacts);
            } else {
                return view('frontend.main_content.post_reacts', $page_data);
            }
        }
    }

    public function my_comment_react(Request $request)
    {
        $form_data = $request->all();

        $comment_data = Comments::where('comment_id', $form_data['comment_id'])->get()->first();

        $all_reacts = json_decode($comment_data['user_reacts'], true);

        if ($form_data['request_type'] == 'update') {
            $all_reacts[$this->user->id] = $form_data['react'];
        }

        if ($form_data['request_type'] == 'toggle') {
            if (array_key_exists($this->user->id, $all_reacts)) {
                unset($all_reacts[$this->user->id]);
            } else {
                $all_reacts[$this->user->id] = 'like';
            }
        }

        $data['user_reacts'] = json_encode($all_reacts);
        Comments::where('comment_id', $form_data['comment_id'])->update($data);

        $page_data['user_comment_reacts'] = $all_reacts;
        $page_data['user_info'] = $this->user;
        $page_data['ajax_call'] = true;
        $page_data['my_react'] = true;
        $page_data['comment_react'] = true;
        return view('frontend.main_content.comment_reacts', $page_data);
    }

    public function load_post_comments(Request $request)
    {
        $post = Posts::where('posts.status', 'active')
            ->where('posts.post_id', $request->post_id)
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name', 'users.photo', 'users.friends', 'posts.created_at as created_at')->get()->first();

        $comments = DB::table('comments')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->where('comments.is_type', $request->type)
            ->where('comments.id_of_type', $request->post_id)
            ->where('comments.parent_id', $request->parent_id)
            ->select('comments.*', 'users.name', 'users.photo')
            ->orderBy('comment_id', 'DESC')->skip($request->total_loaded_comments)->take(3)->get();

        $page_data['post'] = $post;
        $page_data['type'] = $request->type;
        $page_data['post_id'] = $request->post_id;
        if ($request->parent_id == 0) {
            $page_data['comments'] = $comments;
            return view('frontend.main_content.comments', $page_data);
        } else {
            $page_data['child_comments'] = $comments;
            return view('frontend.main_content.child_comments', $page_data);
        }
    }

    public function post_comment(Request $request)
    {
        $form_data = $request->all();

        $data['description'] = $form_data['description'];

        if ($form_data['comment_id'] > 0) {
            $data['updated_at'] = time();
            Comments::where('comment_id', $form_data['comment_id'])->where('user_id', $this->user->id)->update($data);
            $comment_id = $form_data['comment_id'];
        } else {
            $data['parent_id'] = $form_data['parent_id'];
            $data['user_id'] = $this->user->id;
            $data['is_type'] = $form_data['type'];
            $data['id_of_type'] = $form_data['post_id'];
            $data['user_reacts'] = json_encode(array());
            $data['created_at'] = time();
            $data['updated_at'] = $data['created_at'];
            $comment_id = Comments::insertGetId($data);
        }

        $post = Posts::where('posts.post_id', $form_data['post_id'])
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name', 'users.photo', 'users.friends', 'posts.created_at as created_at')->get()->first();

        $comments = DB::table('comments')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->where('comments.is_type', $form_data['type'])
            ->where('comments.comment_id', $comment_id)->get();

        $page_data['post'] = $post;
        $page_data['type'] = $form_data['type'];
        $page_data['post_id'] = $form_data['post_id'];

        $total_comments = Comments::where('is_type', $form_data['type'])->where('id_of_type', $form_data['post_id'])->get()->count();

        if ($request->parent_id == 0) {
            $page_data['comments'] = $comments;
            return view('frontend.main_content.comments', $page_data);
        } else {
            $page_data['child_comments'] = $comments;
            return view('frontend.main_content.child_comments', $page_data);
        }
    }

    public function preview_post(Request $request)
    {

        //Previw post
        $posts = Posts::where(function ($query) {
            $query->where('posts.privacy', '!=', 'private')
                ->orWhere('posts.user_id', $this->user->id);
        })
            ->where('posts.post_id', $request->post_id)
            ->where('posts.status', 'active')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name', 'users.photo', 'users.friends', 'posts.created_at as created_at')
            ->take(1)->orderBy('posts.post_id', 'DESC')->get();

             // New
             $friendships = Friendships::where(function ($query) {
                $query->where('accepter', auth()->user()->id)
                    ->orWhere('requester', auth()->user()->id);
            })
                ->where('is_accepted', 1)
                ->orderBy('friendships.importance', 'desc')
                ->take(15)->get();

            $page_data['friendships'] = $friendships;
        //new   
        
        $page_data['posts'] = $posts;
        $page_data['file_name'] = $request->file_name;
        $page_data['user_info'] = $this->user;
        return view('frontend.main_content.preview_post', $page_data);
    }

    public function post_comment_count(Request $request)
    {
        $form_data = $request->all();
        return $total_child_comments = Comments::where('is_type', $form_data['type'])->where('id_of_type', $form_data['post_id'])->get()->count();
    }

    public function single_post($id, $type = null)
    {

        $post = Posts::where('post_id', $id)->first();
        if (!empty($post)) {
            $page_data['post'] = $post;
            $page_data['user_info'] = auth()->user();
            $page_data['type'] = 'user_post';
            $page_data['image_id'] = $type;
            $page_data['view_path'] = 'frontend.main_content.single-post';
     
            if (isset($_GET['shared'])) {
                return view('frontend.main_content.custom_shared_view', $page_data);
            } else {
                return view('frontend.index', $page_data);
            }
        } else {

            if (isset($_GET['shared'])) {
                $page_data['post'] = '';
                return view('frontend.main_content.custom_shared_view', $page_data);
            } else {
                $page_data['post'] = '';
                $page_data['view_path'] = 'frontend.main_content.custom_shared_view';
                return view('frontend.index', $page_data);
            }
        }
    }

    public function save_post_report(Request $request)
    {

        $report = new Report();

        $report->user_id = auth()->user()->id;
        $report->post_id = $request->post_id;
        $report->report = $request->report;
        $report->save();
        Session::flash('success_message', get_phrase('Report Done Successfully'));
        return json_encode(array('reload' => 1));
    }

    public function comment_delete()
    {
        $response = array();
        $comment_id = $_GET['comment_id'];
        $done = Comments::where('comment_id', $comment_id)->delete();
        if ($done) {
            $response = array('alertMessage' => get_phrase('Comment Deleted Successfully'), 'fadeOutElem' => "#comment_" . $_GET['comment_id']);
        }
        return json_encode($response);
    }

    public function share_group(Request $request)
    {
        $postshare = new Post_share();
        $postshare->user_id = auth()->user()->id;
        $postshare->post_id = $request->shared_post_id;
        $postshare->shared_on = 'group';
        $postshare->save();

        $post = new Posts();
        $post->user_id = auth()->user()->id;
        $post->publisher = 'group';
        $post->publisher_id = $request->group_id;
        $post->post_type = "share";
        $post->privacy = "public";
        $post->tagged_user_ids = json_encode(array());
        if (isset($request->shared_post_id) && !empty($request->shared_post_id)) {
            $post->description = $request->message;
        }
        if (isset($request->shared_product_id) && !empty($request->shared_product_id)) {
            $post->description = $request->productUrl;
        }
        $post->status = 'active';
        $post->user_reacts = json_encode(array());
        $post->shared_user = json_encode(array());
        $time = time();
        $post->created_at = $time;
        $post->updated_at = $time;
        $done = $post->save();

        $response = array('alertMessage' => get_phrase('Posted On Group Successfully'));
        return json_encode($response);
    }

    public function share_my_timeline(Request $request)
    {
        $postshare = new Post_share();
        $postshare->user_id = auth()->user()->id;
        $postshare->post_id = $request->shared_post_id;
        $postshare->shared_on = 'group';
        $postshare->save();

        $post = new Posts();
        $post->user_id = auth()->user()->id;
        $post->publisher = 'post';
        $post->publisher_id = auth()->user()->id;
        $post->post_type = "share";
        $post->privacy = "public";
        $post->tagged_user_ids = json_encode(array());
        if (isset($request->shared_post_id) && !empty($request->shared_post_id)) {
            $post->description = $request->postUrl;
        }
        if (isset($request->shared_product_id) && !empty($request->shared_product_id)) {
            $post->description = $request->productUrl;
        }
        $post->status = 'active';
        $post->user_reacts = json_encode(array());
        $post->shared_user = json_encode(array());
        $time = time();
        $post->created_at = $time;
        $post->updated_at = $time;

        if (isset($request->is_memory)) {
            $post->publisher = "memory";
        }
        
        $done = $post->save();

        Session::flash('success_message', get_phrase('Posted On My Timeline Successfully'));
        return json_encode(array('url' => route('profile')));
    }

    // post delete

    public function post_delete()
    {
        $response = array();
        $done = Posts::where('post_id', $_GET['post_id'])->delete();
        if ($done) {
            $response = array('alertMessage' => get_phrase('Post Deleted Successfully'), 'fadeOutElem' => "#postIdentification" . $_GET['post_id']);
        }
        return json_encode($response);
    }

    public function custom_shared_post_view($id)
    {

        $post = Posts::where(function ($query) {
            $query->whereJsonContains('users.friends', [$this->user->id])
                ->where('posts.privacy', '!=', 'private')
                ->orWhere('posts.user_id', $this->user->id);
        })
            ->where('posts.post_id', $id)
            ->where('posts.status', 'active')
            ->where('posts.report_status', '0')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name', 'users.photo', 'users.friends', 'posts.created_at as created_at')->first();

        $page_data['post'] = $post;
        $page_data['type'] = 'user_post';
        return view('frontend.main_content.custom_shared_view', $page_data);
    }

    public function delete_media_file($id)
    {
        $media_file = Media_files::where('id', $id)->where('user_id', auth()->user()->id);
        if ($media_file->count() > 0) {
            remove_file('public/storage/post/images/' . $media_file->first()->file_name);
            Media_files::find($id)->delete();
            $response = array('alertMessage' => get_phrase('Image deleted successfully'), 'fadeOutElem' => "#previous-uploaded-img-" . $id);
        } else {
            $response = array('alertMessage' => get_phrase('Image not found'));
        }

        return json_encode($response);
    }

    public function addons_manager()
    {
        $user_info = User::where('id', auth()->user()->id)->first();
        $page_data = [
            'section_title' => '',
            'user_info' => $user_info,
            'layout' => 'addons_layout',
            'link_name' => 'Timeline',
            'head_link' => route('timeline'),
            'view_path' => 'frontend.addons.index',
            'content_view' => 'frontend.addons.addon_layout',
        ];
        return view('frontend.index', $page_data);
    }

    public function user_settings()
    {
        $payment_settings = User::where('id', auth()->user()->id)->value('payment_settings');
        if ($payment_settings == '') {
            $settings = [
                'raz_key_id' => '',
                'raz_secret_key' => '',
                'theme_color' => '',
                'stripe_public_key' => '',
                'stripe_secret_key' => '',
                'stripe_public_live_key' => '',
                'stripe_secret_live_key' => '',
                'paypal_client_id' => '',
                'paypal_secret_key' => '',
                'paypal_production_client_id' => '',
                'paypal_production_secret_key' => '',
                'flutterwave_public_key' => '',
                'flutterwave_secret_key' => '',
                'flutterwave_encryption_key' => '',
                'stripe_live' => false,
                'paypal_live' => false,
                'flutterwave_live' => false,
            ];
            $data = json_encode($settings);
            User::where('id', auth()->user()->id)->update(['payment_settings' => $data]);
        }

        $settings = User::where('id', auth()->user()->id)->value('payment_settings');
        $settings = json_decode($settings);

        $page_data = [
            'section_title' => 'User Settings',
            'link_name' => 'Timeline',
            'head_link' => route('timeline'),
            // 'layout' => 'addons_layout',
            'payment_settings' => $settings,
            'view_path' => 'frontend.addons.index',
            'content_view' => 'frontend.addons.user_settings',
        ];
        return view('frontend.index', $page_data);
    }
 

    public function save_user_settings(Request $request)
    {
        $settings = $request->all();
        array_shift($settings);
        $settings['stripe_live'] = $request->stripe_live != null;
        $settings['paypal_live'] = $request->paypal_live != null;
        $settings['flutterwave_live'] = $request->flutterwave_live != null;

        $data = json_encode($settings);
        User::where('id', auth()->user()->id)->update(['payment_settings' => $data]);

        Session::flash('success_message', 'Settings saved.');
        return redirect()->back();
    }




   // Theme Color
    public function updateThemeColor(Request $request)
    {
        $themeColor = $request->input('themeColor');
        Session::put('theme_color', $themeColor);
        return response()->json(['success' => true]);
    }


      //New Album Page  Details
      public function details_album($id){
        $posts = Posts::where('post_id', $id)->get();
        $post_album = Posts::where('post_id', $id)->first();
        $user_info = $this->user;  

          // New
          $friendships = Friendships::where(function ($query) {
            $query->where('accepter', auth()->user()->id)
                ->orWhere('requester', auth()->user()->id);
        })
            ->where('is_accepted', 1)
            ->orderBy('friendships.importance', 'desc')
            ->take(15)->get();

        // $page_data['friendships'] = $friendships;
    //new  

        $page_data = [
            'post_id' => $id,
            'post_album' => $post_album,
            'posts' => $posts,
            'user_info' => $user_info,
            'friendships' => $friendships,
            'layout' => 'album_details',
            'view_path' => 'frontend.album_details.album_details'
        ];
         return view('frontend.index', $page_data);
     }
     
    
   
     public function block_user($id)
     {
         $page_data['post'] = Posts::where('post_id', $id)->first();
         return view('frontend.main_content.block_modal', $page_data);
     }

     public function block_user_post($id)
     {
        $block_post = Posts::find($id);
        $user_block = User::where('id', $block_post->user_id)->first();
        $user_block = new BlockUser();
        $user_block->user_id = auth()->user()->id;
        $user_block->block_user = $block_post->user_id;
        $user_block->save();
        Session::flash('success_message', get_phrase('Block Successfully'));
       return redirect()->route('timeline');
     }
     
    //  UnBlock User
    public function unblock_user($id){
        $unblock = BlockUser::find($id)->delete();
        Session::flash('success_message', get_phrase('Unblock Successfully'));
        return redirect()->back();
    }
  
    


}
