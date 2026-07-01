<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comments;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobApply;
use App\Models\JobWishlist;
use App\Models\Media_files;
use App\Models\Posts;
use App\Models\Stories;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use App\Models\FileUploader;
use App\Models\Group;
use App\Models\Group_member;
use App\Models\Event;
use App\Models\Message_thrade;
use App\Models\Chat;
use App\Models\Follower;
use Session;
use DB;
use Image;
use App\Models\Live_streamings;
use App\Models\Users;
use App\Models\Album_image;
use App\Models\Page;
use App\Models\Pagecategory;
use App\Models\Page_like;
use App\Models\Marketplace;
use App\Models\SavedProduct;
use App\Models\Brand;
use App\Models\Currency;
use App\Models\Video;
use App\Models\Blog;
use App\Models\Blogcategory;
use App\Models\Sponsor;
use App\Models\Share;
use App\Models\Setting;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\Saveforlater;
use App\Models\Report;
use App\Models\Albums;
use App\Models\Post_share;
use App\Models\Payment_gateway;
use App\Models\PaidContentSubscription;
use App\Models\PaidContentCreator;
use App\Models\PaidContentPackages;
use App\Models\PaidContentPayout;
use App\Models\Friendships;
use App\Models\Fundraiser;
use App\Models\Fundraiser_category;
use App\Models\Fundraiser_donation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

require 'vendor/autoload.php'; // Include Composer's autoloader

use Carbon\Carbon; // Import Carbon for date comparisons

class ApiController extends Controller
{

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // email checking 
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            if (isset($user) && $user->count() > 0) {
                return response([
                    'message' => 'Invalid credentials!'
                ], 401);
            } else {
                return response([
                    'message' => 'User not found!'
                ], 401);
            }
        } else if ($user->user_role == 'general') {

            // $user->tokens()->delete();

            $token = $user->createToken('auth-token')->plainTextToken;

            // $user->photo = get_photo('user_image', $user->photo);

            $response = [
                'message' => 'Login successful',
                'user' => $user,
                'user_id' => $user->id,
                'user_image' => get_user_images($user->id),
                'cover_photo' => get_cover_photos($user->id),
                'token' => $token
            ];

            return response($response, 201);

        } else {

            //user not authorized
            return response()->json([
                'message' => 'User not found!',
            ], 400);
        }

    }
    public function signup(Request $request)
    {
        // return $request->all();
        $response = array();

        // $request->validate([
        //     'name' => ['required', 'string', 'max:255'],
        //     'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        //     'password' => ['required'],
        // ]);
        // $request->validate([
        //     'name' => ['required', 'string', 'max:255'],
        //     'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        //     'password' => ['required', 'confirmed', Rules\Password::defaults()],
        // ]);

        $rules = array(
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()]
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
        }
        // return $response;
        $user = User::create([
            'user_role' => 'general',
            'username' => rand(100000, 999999),
            'name' => $request->name,
            'email' => $request->email,
            'friends' => json_encode(array()),
            'followers' => json_encode(array()),
            'timezone' => $request->timezone,
            'password' => Hash::make($request->password),
            'status' => 0,
            'lastActive' => Carbon::now(),
            'created_at' => time()
        ]);
        if ($user) {
            $response['success'] = true;
            $response['message'] = 'user create successfully';
        }
        event(new Registered($user));

        return $response;

        //    $user_id = auth('sanctum')->user();
        // $user = User::find($user_id);
        // $user = User::first();
        // if ($user->email != $request->email) {
        //     $usercreate = User::create([
        //         'user_role' => 'general',
        //         'username' => rand(100000, 999999),
        //         'name' => $request->name,
        //         'email' => $request->email,
        //         'friends' => json_encode(array()),
        //         'followers' => json_encode(array()),
        //         'timezone' => $request->timezone,
        //         'password' => Hash::make($request->password),
        //         'status' => 0,
        //         'lastActive' => Carbon::now(),
        //         'created_at' => time(),
        //     ]);
        //     if ($usercreate) {
        //         $response['success'] = true;
        //         $response['message'] = 'user create successfully';
        //     }
        // } else {
        //     $response['success'] = false;
        //     $response['message'] = 'This user email is use before';
        // }

        // event(new Registered($user));

        // Auth::login($user);

        // return redirect(RouteServiceProvider::HOME);
    }

    public function forgot_password(Request $request)
    {
        $response = array();

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );
        if ($status) {
            $response['success'] = true;
            $response['message'] = 'Reset Password Link send successfully to your email';
        }

        // return $status == Password::RESET_LINK_SENT
        //             ? back()->with('status', __($status))
        //             : back()->withInput($request->only('email'))
        //                     ->withErrors(['email' => __($status)]);
        return $response;
    }
    // Update password
    public function update_password(Request $request)
    {

        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $auth = auth('sanctum')->user();

            // The passwords matches
            if (!Hash::check($request->get('current_password'), $auth->password)) {
                $response['status'] = 'failed';
                $response['message'] = 'Current Password is Invalid';

                return $response;
            }

            // Current password and new password same
            if (strcmp($request->get('current_password'), $request->new_password) == 0) {
                $response['status'] = 'failed';
                $response['message'] = 'New Password cannot be same as your current password.';

                return $response;
            }

            // Current password and new password same
            if (strcmp($request->get('confirm_password'), $request->new_password) != 0) {
                $response['status'] = 'failed';
                $response['message'] = 'New Password is not same as your confirm password.';

                return $response;
            }

            $user = User::find($auth->id);
            $user->password = Hash::make($request->new_password);
            $user->save();

            $response['status'] = 'success';
            $response['message'] = 'Password Changed Successfully';


            return $response;

        } else {
            $response['status'] = 'failed';
            $response['message'] = 'Please login first';

            return $response;
        }
    }

    public function user(Request $request)
    {
        $token = $request->bearerToken();
        $data = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $user = User::find($user_id);

            if ($user) {
                $friends = json_decode($user->friends);

                // return $userDetails;
                if (!empty($friends)) {
                    $friendList = [];
                    foreach ($friends as $userId) {
                        $user = User::find($userId);
                        if ($user) {
                            $friendList[] = $user;
                        }
                    }
                    // $friendList = User::whereIn('id', $friends)->get();
                    // return $friendList;
                    foreach ($friendList as $friend) {
                        $userDetails = get_user_info($friend->id);


                        $friendData = [
                            'friend_name' => $userDetails->name,
                            'friend_id' => $userDetails->id,
                            'posts' => []
                        ];
                        $posts = Posts::where('user_id', $userDetails->id)->get();
                        foreach ($posts as $post) {
                            $postUser = User::find($post->user_id);
                            $media = Media_files::where('post_id', $post->post_id)->first();

                            $friendData['posts'][] = [
                                'post_id' => $post->post_id,
                                'privacy' => $post->privacy,
                                'description' => $post->description,
                                'post_user_name' => $postUser->name,
                                'post_user_image' => get_user_images($postUser->id),
                                'user_id' => $postUser->id,
                                'post_image' => !empty($media->file_name) ? get_post_images($media->file_name) : '',
                            ];
                        }

                        $data[] = $friendData;
                    }

                }
            }

        }

        return ['post' => $data];
    }
    public function user_post(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();
        $data = array();
        // if (isset($token) && $token != '') {
        // $user_id = auth('sanctum')->user()->id;
        $users = User::where('id', 4)->first();
        // foreach ($users as $key => $user) {
        // $data[$key]['user'] = $users;
        // $data[$key]['id'] = $user->id;
        // $data[$key]['user_role'] = $user->user_role;
        // $data[$key]['username'] = $user->username;
        // $data[$key]['email'] = $user->email;
        // $data[$key]['name'] = $user->name;


        // $friend = json_decode($users->friends);
        // print($user->friends);
        // die();
        // if (sizeof($friend) > 0) {
        //     $friend_list = User::whereIn('id', $friend)->get();

        //     foreach ($friend_list as $key => $friend) {
        //         $res[$key]['friend_id'] = $friend->id;
        //         $posts = Posts::where('user_id', $friend->id)->where('privacy', 'public')->get();
        //         // $res[$key]['exam_id'] = $exam->id;

        //         // $res[$key]['exam_category_id'] = $exam->exam_category_id;
        //         // $res[$key]['exam_category_name'] = ExamCategory::where('id', $exam->exam_category_id)->value('name');

        //         // $subject_list = json_decode($exam->marks, true);
        //         foreach ($posts as $index => $post) {

        //             $sub_res[$index]['post_id'] = $post->post_id;
        //             $sub_res[$index]['privacy'] = $post->privacy;
        //             $sub_res[$index]['description'] = $post->description;

        //             $res[$key]['post'] = $sub_res;

        //         }
        //     }
        //     $response = $res;

        //     return response($response, 200);



        //     foreach ($friend_list as $key1 => $friends) {
        //         $user_details = get_user_info($friends->id);
        //         // $response[$key1]['name1'] = $user_details->name;
        //         // $response[$key1]['id1'] = $user_details->id;
        //         $post = Posts::where('user_id', $user_details->id)->get();
        //         foreach ($post as $key2 => $posts) {
        //             // $response[] = $posts;
        //             $response[$key1][$key2]['details'] = $posts->description;
        //             $response[$key1][$key2]['post_ids'] = $posts->privacy;
        //             // $response[$key2]['name'] = $user_details->name;
        //             // $response[$key2]['id'] = $user_details->id;

        //             $post_user = User::where('id', $posts->user_id)->first();
        //             $response[$key1][$key2]['post_user_name'] = $post_user->name;
        //             $response[$key1][$key2]['user_id'] = $post_user->id;

        //             $media = Media_files::where('post_id', $posts->post_id)->first();

        //             // $response[$key1][$key2]['post_image'] ? $media->file_name : '';
        //             if ((!empty($media->file_name))) {
        //                 $response[$key1][$key2]['post_image'] = get_post_images($media->file_name);
        //             } else {
        //                 $response[$key1][$key2]['post_image'] = '';
        //             }

        //         }
        //         // print($friends->id);
        //         // echo "<br>";
        //     }
        //     // die();

        //     // $response = get_user_info($friend_list);
        // } else {
        //     $response = array();
        // }
        $data = $users;
        // }
        return $data;
        // } 
        // else {
        // }
        // return $data;
    }
    // public function getPostReactions(Request $request, $postId)
    // {

    //     $token = $request->bearerToken();
    //     $response = array();
    //     $data = array();

    //     if (isset($token) && $token != '') {
    //         $user_id = auth('sanctum')->user()->id;
    //         // Retrieve the post from the database
    //         $post = Posts::findOrFail($postId);

    //         // Retrieve the reactions for the post
    //         $reactions = json_decode($post->user_reacts, true);

    //         // Check if the user is logged in
    //         // $userId = Auth::id();

    //         // Check if the user has reacted to the post
    //         $userReaction = null;
    //         if ($user_id && isset($reactions[$user_id])) {
    //             $userReaction = $reactions[$user_id];
    //         }
    //     }

    //     return response()->json([
    //         'reactions' => $reactions,
    //         'user_reaction' => $userReaction
    //     ]);
    // }


    public function timeline(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $user = User::find($user_id);

            //First 10 stories
            $stories = Stories::where(function ($query) use ($user_id) {
                $query->whereJsonContains('users.friends', [$user_id])
                    ->where('stories.privacy', '!=', 'private')
                    ->orWhere('stories.user_id', $user_id);
            })
                ->where('stories.status', 'active')
                ->where('stories.created_at', '>=', (time() - 86400))
                ->join('users', 'stories.user_id', '=', 'users.id')
                ->select('stories.*', 'users.name', 'users.photo', 'users.friends', 'stories.created_at as created_at')
                // ->skip($request->offset)
                ->take(40)
                ->orderBy('stories.story_id', 'DESC')->get();
            //    if($stories){
            $timeline_story = [];
            foreach ($stories as $story) {
                $user = User::find($story->user_id);

                // Fetch media files associated with the post
                $mediaFiles = Media_files::where('story_id', $story->story_id)->get();

                // Initialize an array to store post images
                $storyImages = '';
                $filetype = 'text';

                // Loop through the media files and add them to the postImages array
                foreach ($mediaFiles as $media) {
                    $storyImages = $media->file_type == "image" ? get_story_images($media->file_name, "optimized") : get_story_videos($media->file_name);
                    $filetype = $media->file_type;
                }
                // Extract color, bg-color, and text from the story description
                $descriptionData = json_decode($story->description);
                $color = $descriptionData->color ?? '';
                $bgColor = $descriptionData->{'bg-color'} ?? '';
                $description = $descriptionData->text ?? '';

                // Convert the Unix timestamp to a formatted date string
                $formattedDate = date('M d \a\t H:i A', strtotime($story->posted_on));

                // Construct the response array for the current post
                $timeline_story[] = [
                    'story_id' => $story->story_id,
                    'user_id' => $story->user_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->id, "optimized"),
                    'publisher' => $story->publisher,
                    'fileType' => $filetype,
                    'privacy' => $story->privacy,
                    'content_type' => $story->content_type,
                    'description' => $description,
                    'color' => $color,
                    'bg-color' => $bgColor,
                    'post_images' => $storyImages, // Array of post images
                    'created_at' => $formattedDate, // Use the formatted date string
                    // Add other fields as needed
                ];
            }
            //    }else{
            //     $timeline_story = [];
            //    }


            //First 10 posts
            $posts = Posts::where(function ($query) use ($user_id) {
                $query->whereJsonContains('users.friends', [$user_id])
                    ->where('posts.privacy', '!=', 'private')
                    ->orWhere('posts.user_id', $user_id)

                    //if folowing any users, pages, groups and others if not friend listed
                    ->orWhere(function ($query3) {
                        $query3->where('posts.privacy', 'public')
                            ->where(function ($query4) {
                                $query4->where('posts.publisher', 'post')
                                    ->join('followers', function (JoinClause $join) {
                                        $join->on('posts.publisher_id', '=', 'followers.follow_id')
                                            ->where('followers.user_id', auth('sanctum')->user()->id);
                                    });
                            })
                            ->orWhere(function ($query5) {
                                $query5->where('posts.publisher', 'profile_picture')
                                    ->join('followers', function (JoinClause $join1) {
                                        $join1->on('posts.publisher_id', '=', 'followers.follow_id')
                                            ->where('followers.user_id', auth('sanctum')->user()->id);
                                    });
                            })
                            ->orWhere(function ($query6) {
                                $query6->where('posts.publisher', 'page')
                                    ->join('followers', function (JoinClause $join2) {
                                        $join2->on('posts.publisher_id', '=', 'followers.page_id')
                                            ->where('followers.user_id', auth('sanctum')->user()->id);
                                    });
                            })
                            ->orWhere(function ($query7) {
                                $query7->where('posts.publisher', 'group')
                                    ->join('followers', function (JoinClause $join3) {
                                        $join3->on('posts.publisher_id', '=', 'followers.group_id')
                                            ->where('followers.user_id', auth('sanctum')->user()->id);
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
                                    ->where('group_members.user_id', '=', auth('sanctum')->user()->id);
                            });
                        });
                })

                ->select('posts.*', 'users.name', 'users.photo', 'users.friends', 'posts.created_at as created_at')
                ->skip($request->offset)
                ->take(10)
                ->orderBy('posts.post_id', 'DESC')->get();
            $timeline = [];
            foreach ($posts as $post) {
                $user = User::find($post->user_id);

                // Fetch media files associated with the post
                $mediaFiles = Media_files::where('post_id', $post->post_id)->get();

                // Initialize an array to store post images
                $postImages = [];
                $file = 'text';

                // Loop through the media files and add them to the postImages array
                foreach ($mediaFiles as $media) {
                    if ($media->file_type == "image") {
                        $postImages[] = get_post_images($media->file_name, "optimized");
                    } else {
                        $postImages[] = get_post_videos($media->file_name);
                    }
                    $file = $media->file_type;
                }



                // comment count 
                $commentsCount = Comments::where('id_of_type', $post->post_id)->count();
                // user react of each post
                $userReacts = json_decode($post->user_reacts, true);
                // Initialize counters for reaction types
                $reactionsCount = array_fill_keys(['like', 'love', 'sad', 'haha', 'angry'], 0);

                // Count occurrences of reactions
                foreach ($userReacts as $react) {
                    if (array_key_exists($react, $reactionsCount)) {
                        $reactionsCount[$react]++;
                    }
                }
                // Calculate the total reactions
                $totalReacts = array_sum($reactionsCount);

                // $formattedDate = date('M d \a\t H:i A', strtotime($post->posted_on));

                $createdDate = Carbon::createFromTimestamp(strtotime($post->posted_on));
                $daysDifference = $createdDate->diffInDays(Carbon::now());
                if ($daysDifference < 7) {
                    // Show "time ago" format
                    $formattedDate = $createdDate->diffForHumans();
                } else {
                    // Show the exact date and time
                    $formattedDate = $createdDate->toDayDateTimeString();
                    // Example format: 'Mon, Jan 1, 2024 12:00 AM'
                }


                $followers = Follower::where('user_id', $user_id)->get();
                $follow = 'Follow';
                foreach ($followers as $follo) {
                    if ($follo->follow_id == $post->user_id) {
                        $follow = 'Unfollow';
                    }
                }

                $taggedUserIds = json_decode($post->tagged_user_ids, true);
                $taggedUsers = User::whereIn('id', $taggedUserIds)->get(['id', 'name']);
                $taggedUserList = [];
                foreach ($taggedUsers as $tags) {
                    $taggedUserList[] = [
                        'id' => $tags->id,
                        'name' => $tags->name
                    ];
                }

                // Construct the response array for the current post
                $timeline[] = [
                    'post_id' => $post->post_id,
                    'user_id' => $post->user_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->id, "optimized"),
                    'publisher' => $post->publisher,
                    'publisherId' => $post->publisher_id,
                    'post_type' => $post->post_type,
                    'fileType' => $file,
                    'privacy' => $post->privacy,
                    'location' => $post->location != null ? $post->location : "",
                    'post_images' => $postImages,
                    'thumbnail' => $post->mobile_app_image != null ? get_post_images($post->mobile_app_image) : "",
                    'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,
                    'description' => $post->description != null ? $post->description : "",
                    'commentsCount' => $commentsCount,
                    'reaction_counts' => $reactionsCount,
                    'total' => $totalReacts,
                    'created_at' => $formattedDate,
                    'follow' => $follow,
                    'taggedUserList' => $taggedUserList,

                ];
            }
        }
        $response['stories'] = $timeline_story;
        $response['post'] = $timeline;
        return $response;
    }
    
    // public function timeline(Request $request)
    // {
    //     $token = $request->bearerToken();
    //     $response = array();

    //     if (isset($token) && $token != '') {
    //         $user_id = auth('sanctum')->user()->id;
    //         $user = User::find($user_id);

    //         //First 10 stories
    //         $stories = Stories::where(function ($query) use ($user_id) {
    //             $query->whereJsonContains('users.friends', [$user_id])
    //                 ->where('stories.privacy', '!=', 'private')
    //                 ->orWhere('stories.user_id', $user_id);
    //         })
    //             ->where('stories.status', 'active')
    //             ->where('stories.created_at', '>=', (time() - 86400))
    //             ->join('users', 'stories.user_id', '=', 'users.id')
    //             ->select('stories.*', 'users.name', 'users.photo', 'users.friends', 'stories.created_at as created_at')
    //             ->orderBy('stories.story_id', 'DESC')->get();
    //         //    if($stories){
    //         $timeline_story = [];
    //         foreach ($stories as $story) {
    //             $user = User::find($story->user_id);

    //             // Fetch media files associated with the post
    //             $mediaFiles = Media_files::where('story_id', $story->story_id)->get();

    //             // Initialize an array to store post images
    //             $storyImages = '';

    //             // Loop through the media files and add them to the postImages array
    //             foreach ($mediaFiles as $media) {
    //                 $storyImages = $media->file_type == "image" ? get_story_images($media->file_name) : get_story_videos($media->file_name);
    //             }
    //             // Extract color, bg-color, and text from the story description
    //             $descriptionData = json_decode($story->description);
    //             $color = $descriptionData->color ?? '';
    //             $bgColor = $descriptionData->{'bg-color'} ?? '';
    //             $description = $descriptionData->text ?? '';

    //             // Convert the Unix timestamp to a formatted date string
    //             $formattedDate = date('M d \a\t H:i A', strtotime($story->created_at));

    //             // Construct the response array for the current post
    //             $timeline_story[] = [
    //                 'story_id' => $story->story_id,
    //                 'user_id' => $story->user_id,
    //                 'name' => $user->name,
    //                 'photo' => get_user_images($user->id),
    //                 'publisher' => $story->publisher,
    //                 // 'post_type' => $story->post_type,
    //                 'privacy' => $story->privacy,
    //                 'content_type' => $story->content_type,
    //                 'description' => $description,
    //                 'color' => $color,
    //                 'bg-color' => $bgColor,
    //                 'post_images' => $storyImages, // Array of post images
    //                 'created_at' => $formattedDate, // Use the formatted date string
    //                 // Add other fields as needed
    //             ];
    //         }
    //         //    }else{
    //         //     $timeline_story = [];
    //         //    }


    //         //First 10 posts
    //         $posts = Posts::where(function ($query) use ($user_id) {
    //             $query->whereJsonContains('users.friends', [$user_id])
    //                 ->where('posts.privacy', '!=', 'private')
    //                 ->orWhere('posts.user_id', $user_id)

    //                 //if folowing any users, pages, groups and others if not friend listed
    //                 ->orWhere(function ($query3) {
    //                     $query3->where('posts.privacy', 'public')
    //                         ->where(function ($query4) {
    //                             $query4->where('posts.publisher', 'post')
    //                                 ->join('followers', function (JoinClause $join) {
    //                                     $join->on('posts.publisher_id', '=', 'followers.follow_id')
    //                                         ->where('followers.user_id', auth('sanctum')->user()->id);
    //                                 });
    //                         })
    //                         ->orWhere(function ($query5) {
    //                             $query5->where('posts.publisher', 'profile_picture')
    //                                 ->join('followers', function (JoinClause $join1) {
    //                                     $join1->on('posts.publisher_id', '=', 'followers.follow_id')
    //                                         ->where('followers.user_id', auth('sanctum')->user()->id);
    //                                 });
    //                         })
    //                         ->orWhere(function ($query6) {
    //                             $query6->where('posts.publisher', 'page')
    //                                 ->join('followers', function (JoinClause $join2) {
    //                                     $join2->on('posts.publisher_id', '=', 'followers.page_id')
    //                                         ->where('followers.user_id', auth('sanctum')->user()->id);
    //                                 });
    //                         })
    //                         ->orWhere(function ($query7) {
    //                             $query7->where('posts.publisher', 'group')
    //                                 ->join('followers', function (JoinClause $join3) {
    //                                     $join3->on('posts.publisher_id', '=', 'followers.group_id')
    //                                         ->where('followers.user_id', auth('sanctum')->user()->id);
    //                                 });
    //                         });
    //                 });
    //         })
    //             ->where('posts.status', 'active')
    //             ->where('posts.report_status', '0')
    //             ->where('publisher', '!=', 'paid_content') // post type can not be paid content
    //             ->join('users', 'posts.user_id', '=', 'users.id')

    //             ->where(function ($query) {
    //                 $query->where('posts.publisher', '!=', 'video_and_shorts')
    //                     ->orWhere(function ($query2) {
    //                         $query2->join('group_members', function (JoinClause $join) {
    //                             $join->on('posts.publisher_id', '=', 'group_members.group_id')
    //                                 ->where('posts.publisher', '=', 'group')
    //                                 ->where('group_members.user_id', '=', auth('sanctum')->user()->id);
    //                         });
    //                     });
    //             })

    //             ->select('posts.*', 'users.name', 'users.photo', 'users.friends', 'posts.created_at as created_at')
    //             ->skip($request->offset)->take(10)->orderBy('posts.post_id', 'DESC')->get();
    //         $timeline = [];
    //         foreach ($posts as $post) {
    //             $user = User::find($post->user_id);

    //             // Fetch media files associated with the post
    //             $mediaFiles = Media_files::where('post_id', $post->post_id)->get();

    //             // Initialize an array to store post images
    //             $postImages = [];

    //             // Loop through the media files and add them to the postImages array
    //             foreach ($mediaFiles as $media) {
    //                 if ($media->file_type == "image") {
    //                     $postImages[] = get_post_images($media->file_name);
    //                 } else {
    //                     $postImages[] = get_post_videos($media->file_name);
    //                 }
    //             }

    //             // comment count 
    //             $commentsCount = Comments::where('id_of_type', $post->post_id)->count();

    //             // user react of each post
    //             $userReacts = json_decode($post->user_reacts, true);

    //             // Initialize counters for reaction types
    //             $reactionsCount = array_fill_keys(['like', 'love', 'sad', 'haha', 'angry'], 0);

    //             // Count occurrences of reactions
    //             foreach ($userReacts as $react) {
    //                 if (array_key_exists($react, $reactionsCount)) {
    //                     $reactionsCount[$react]++;
    //                 }
    //             }

    //             // Convert the Unix timestamp to a formatted date string
    //             $formattedDate = date('M d \a\t H:i A', strtotime($post->created_at));


    //             // Calculate the total reactions
    //             $totalReacts = array_sum($reactionsCount);

    //             // Construct the response array for the current post
    //             $timeline[] = [
    //                 'post_id' => $post->post_id,
    //                 'user_id' => $post->user_id,
    //                 'name' => $user->name,
    //                 'photo' => get_user_images($user->id),
    //                 'publisher' => $post->publisher,
    //                 'post_type' => $post->post_type,
    //                 'privacy' => $post->privacy,
    //                 'post_images' => $postImages, // Array of post images
    //                 'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,
    //                 'description' => $post->description != null ? $post->description : "",
    //                 'commentsCount' => $commentsCount,
    //                 'reaction_counts' => $reactionsCount,
    //                 'total' => $totalReacts,
    //                 'created_at' => $formattedDate, // Use the formatted date string
    //                 // Add other fields as needed
    //             ];
    //         }



    //     }
    //     $response['stories'] = $timeline_story;
    //     $response['post'] = $timeline;
    //     return $response;
    // }

    public function stories(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();
        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $stories = DB::table('stories')
                ->where('stories.privacy', '!=', 'private')
                ->where('stories.user_id', $user_id)
                ->where('stories.status', 'active')
                ->where('stories.content_type', 'file')
                ->where('stories.created_at', '>=', (time() - 86400))
                ->orderBy('stories.story_id', 'DESC')->get();

            $timeline_story = [];
            foreach ($stories as $story) {
                $user = User::find($story->user_id);

                // Fetch media files associated with the post
                // $media = Media_files::where('story_id', $story->story_id)->first();

                // // $storyImages = $media->file_type == "image" ? get_story_images($media->file_name) : get_story_videos($media->file_name);
                // $storyImages = get_story_images($media->file_name) ;
                $mediaFiles = Media_files::where('story_id', $story->story_id)->get();

                // Initialize an array to store post images
                $storyImages = '';
                $filetype = 'text';

                // Loop through the media files and add them to the postImages array
                foreach ($mediaFiles as $media) {
                    $storyImages = $media->file_type == "image" ? get_story_images($media->file_name, "optimized") : get_story_videos($media->file_name);
                    $filetype = $media->file_type;
                }
                // Convert the Unix timestamp to a formatted date string
                $formattedDate = date('M d \a\t H:i A', strtotime($story->created_at));

                // Extract color, bg-color, and text from the story description
                $descriptionData = json_decode($story->description);
                $color = $descriptionData->color ?? '';
                $bgColor = $descriptionData->{'bg-color'} ?? '';
                $description = $descriptionData->text ?? '';

                // Construct the response array for the current post
                $timeline_story[] = [
                    'story_id' => $story->story_id,
                    'user_id' => $story->user_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->id, "optimized"),
                    'publisher' => $story->publisher,
                    'fileType' => $filetype,
                    'privacy' => $story->privacy,
                    'content_type' => $story->content_type,
                    'description' => $description,
                    'color' => $color,
                    'bg-color' => $bgColor,
                    'post_images' => $storyImages, // Array of post images
                    'created_at' => $formattedDate, // Use the formatted date string
                    // Add other fields as needed
                ];
            }

            $response['stories'] = $timeline_story;
        }
        return $response;
    }

    public function create_story(Request $request)
    {
        // return $request->story_files;
        $token = $request->bearerToken();
        $response = array();
        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $all_data = $request->all();

            $data['publisher'] = "user";
            $data['content_type'] = $request->content_type;

            if ($request->publisher == 'user') {
                $data['publisher_id'] = $user_id;
            } else {
                $data['publisher_id'] = $user_id;
            }

            if ($request->content_type == 'text') {

                if (!empty($request->description)) {
                    $data['description'] = json_encode(
                        array('color' => $all_data['color'], 'bg-color' => $all_data['bg-color'], 'text' => $all_data['description'])
                    );
                }
            }

            $data['privacy'] = $request->privacy;
            $data['created_at'] = time();
            $data['updated_at'] = $data['created_at'];
            $data['user_id'] = $user_id;
            $data['status'] = 'active';
            $story_id = Stories::insertGetId($data);


            if ($request->content_type != 'text') {

                //add media files
                // foreach ($request->story_files as $key => $media_file) {
                // Check if media file is not empty and handle file upload
                $media_file = $request->story_files;
                if (!empty($media_file)):

                    $file_extention = $media_file->getClientOriginalExtension();
                    if ($file_extention == 'avi' || $file_extention == 'mp4' || $file_extention == 'webm' || $file_extention == 'mov' || $file_extention == 'wmv' || $file_extention == 'mkv') {
                        $file_name = FileUploader::upload($media_file, 'public/storage/story/videos');
                        $file_type = 'video';
                    } else {
                        $file_name = FileUploader::upload($media_file, 'public/storage/story/images', 800);
                        $file_type = 'image';
                    }
                    $media_file_data = array('user_id' => $user_id, 'story_id' => $story_id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => $request->privacy);
                    $media_file_data['created_at'] = time();
                    $media_file_data['updated_at'] = $media_file_data['created_at'];
                    $media = Media_files::create($media_file_data);
                    if ($media) {
                        $response['success'] = true;
                        $response['message'] = 'media is uploaded';
                    } else {
                        $response['success'] = false;
                        $response['message'] = 'media is not uploaded';
                    }
                endif;
            }

            // }
        } else {
            $response['success'] = false;
            $response['message'] = 'Invalid token.';
        }

        return $response;
    }

    public function load_timeline(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();
        if (!$request->offset) {
            $offset = 0;
        } else {
            $offset = $request->offset;
        }

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $posts = Posts::where(function ($query) use ($user_id) {
                $query->whereJsonContains('users.friends', [$user_id])
                    ->where('posts.privacy', '!=', 'private')
                    ->orWhere('posts.user_id', $user_id)

                    //if following any users, pages, groups and others if not friend listed
                    ->orWhere(function ($query3) {
                        $query3->where('posts.privacy', 'public')
                            ->where(function ($query4) {
                                $query4->where('posts.publisher', 'post')
                                    ->join('followers', function (JoinClause $join) {
                                        $join->on('posts.publisher_id', '=', 'followers.follow_id')
                                            ->where('followers.user_id', auth('sanctum')->user()->id);
                                    });
                            })
                            ->orWhere(function ($query5) {
                                $query5->where('posts.publisher', 'profile_picture')
                                    ->join('followers', function (JoinClause $join1) {
                                        $join1->on('posts.publisher_id', '=', 'followers.follow_id')
                                            ->where('followers.user_id', auth('sanctum')->user()->id);
                                    });
                            })
                            ->orWhere(function ($query6) {
                                $query6->where('posts.publisher', 'page')
                                    ->join('followers', function (JoinClause $join2) {
                                        $join2->on('posts.publisher_id', '=', 'followers.page_id')
                                            ->where('followers.user_id', auth('sanctum')->user()->id);
                                    });
                            })
                            ->orWhere(function ($query7) {
                                $query7->where('posts.publisher', 'group')
                                    ->join('followers', function (JoinClause $join3) {
                                        $join3->on('posts.publisher_id', '=', 'followers.group_id')
                                            ->where('followers.user_id', auth('sanctum')->user()->id);
                                    });
                            });
                    });
            })
                ->where('posts.status', 'active')
                ->where('posts.publisher', 'post')
                ->where('posts.report_status', '0')
                ->join('users', 'posts.user_id', '=', 'users.id')
                ->select('posts.*', 'users.name', 'users.photo', 'users.friends', 'posts.created_at as created_at')
                ->skip($offset)->take(10)->orderBy('posts.post_id', 'DESC')->get();

            foreach ($posts as $post) {
                $user = User::find($post->user_id);

                // Fetch media files associated with the post
                $mediaFiles = Media_files::where('post_id', $post->post_id)->get();

                // Initialize an array to store post images
                $postImages = [];

                // Loop through the media files and add them to the postImages array
                foreach ($mediaFiles as $media) {
                    if ($media->file_type == "image") {
                        $postImages[] = get_post_images($media->file_name);
                    } else {
                        $postImages[] = get_post_videos($media->file_name);
                    }
                }

                // comment count 
                $commentsCount = Comments::where('id_of_type', $post->post_id)->count();

                // user react of each post
                $userReacts = json_decode($post->user_reacts, true);

                // Initialize counters for reaction types
                $reactionsCount = array_fill_keys(['like', 'love', 'sad', 'haha', 'angry'], 0);

                // Count occurrences of reactions
                foreach ($userReacts as $react) {
                    if (array_key_exists($react, $reactionsCount)) {
                        $reactionsCount[$react]++;
                    }
                }

                // Convert the Unix timestamp to a formatted date string
                $formattedDate = date('M d \a\t H:i A', strtotime($post->created_at));


                // Calculate the total reactions
                $totalReacts = array_sum($reactionsCount);

                // Construct the response array for the current post
                $timeline[] = [
                    'post_id' => $post->post_id,
                    'user_id' => $post->user_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->id),
                    'publisher' => $post->publisher,
                    'post_type' => $post->post_type,
                    'privacy' => $post->privacy,
                    // 'post_images' => $postImages, // Array of post images
                    // 'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,
                    // 'description' => $post->description,
                    // 'commentsCount' => $commentsCount,
                    // 'reaction_counts' => $reactionsCount,
                    // 'total' => $totalReacts,
                    // 'created_at' => $formattedDate, // Use the formatted date string
                    // // Add other fields as needed
                ];
            }


            // $page_data['user_info'] = $this->user;
        }
        // $response['offset'] = $offset + 10;
        $response['post'] = $timeline;
        return $response;
    }
    public function friends(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();
        // $data = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $friendships = Friendships::where(function ($query) use ($user_id) {
                $query->where('accepter', $user_id)
                    ->orWhere('requester', $user_id);
            })
                ->where('is_accepted', 1)
                ->orderBy('friendships.importance', 'desc')
                ->get();
            // $user = User::where('id', $user_id)->first();


            $response['friendsList'] = [];
            // $friend_list = User::whereIn('id', $friend)->get();
            foreach ($friendships as $key => $friend) {

                $profile_id = $friend->requester == $user_id ? $friend->accepter : $friend->requester;
                $user = User::find($profile_id);

                $followers = Follower::where('user_id', $user_id)->get();
                $response['friendsList'][$key]['follow'] = 'Follow';
                foreach ($followers as $follo) {
                    if ($follo->follow_id == $profile_id) {
                        $response['friendsList'][$key]['follow'] = 'Unfollow';
                    }
                }

                // $user = User::where('id', $friend->id)->first();
                $response['friendsList'][$key]['friend_id'] = $user->id;
                $response['friendsList'][$key]['name'] = $user->name;
                $response['friendsList'][$key]['photo'] = get_user_images($user->id, "optimized");
                $response['friendsList'][$key]['cover_photo'] = get_cover_photos($user->id, "optimized");

            }


            // $response['status'] = 'success';
            // $response['error_reason'] = 'None';
            return response($response, 200);
        } else {
            // $response['status'] = 'failed';
            // $response['error_reason'] = 'Unauthorized login';
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // return $response;
    }

    public function add_friend(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();
        // $data = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $friendship = new Friendships();
            $friendship->accepter = $id;
            $friendship->requester = $user_id;
            $friendship->is_accepted = '0';
            $friendship->save();

            $notify = new Notification();
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $id;
            $notify->type = 'profile';
            $notify->save();

            $response['status'] = true;
            $response['message'] = 'send friend request Successfully';
        } else {
            $response['status'] = false;
            $response['message'] = 'unauthorised access';
        }

        return $response;
    }

    public function unfriend(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();
        // $data = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            Friendships::where(function ($query) use ($id, $user_id) {
                $query->where('accepter', $id)->where('requester', $user_id);
            })->orWhere(function ($query) use ($id, $user_id) {
                $query->where('requester', $id)->where('accepter', $user_id);
            })->delete();

            //remove my id from this user table
            $unfriended_user_friends = User::where('id', $id)->value('friends');
            $unfriended_user_friends = json_decode($unfriended_user_friends, true);
            if (is_array($unfriended_user_friends)) {
                $array_key = array_search($user_id, $unfriended_user_friends, true);
                unset($unfriended_user_friends[$array_key]);
            } else {
                $unfriended_user_friends = [];
            }
            $unfriended_user_friends = json_encode($unfriended_user_friends);
            User::where('id', $id)->update(['friends' => $unfriended_user_friends]);


            //remove user id from my user friend list
            $unfriended_user_friends = User::where('id', $user_id)->value('friends');
            $unfriended_user_friends = json_decode($unfriended_user_friends, true);
            if (is_array($unfriended_user_friends)) {
                $array_key = array_search($id, $unfriended_user_friends, true);
                unset($unfriended_user_friends[$array_key]);
            } else {
                $unfriended_user_friends = [];
            }
            $unfriended_user_friends = json_encode($unfriended_user_friends);
            User::where('id', $user_id)->update(['friends' => $unfriended_user_friends]);

            $notify = Notification::where('sender_user_id', $user_id)->where('reciver_user_id', $id)->delete();

            $response['status'] = true;
            $response['message'] = 'unfriend Successfully';
        } else {
            $response['status'] = false;
            $response['message'] = 'unauthorised access';
        }
        return $response;
    }

    public function friend_request(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();
        // $data = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            // $friendships = Friendships::where(function ($query) use ($id) {
            //     $query->where('accepter', $id)
            //         ->orWhere('requester', $id);
            // })
            //     ->where('is_accepted', 1)
            //     ->orderBy('friendships.importance', 'desc')
            //     ->get();

            $friend_requests = Friendships::where('accepter', $user_id)
                ->where('is_accepted', '!=', 1)
                ->get();
            $response['friendsList'] = [];
            foreach ($friend_requests as $key => $friend) {

                $profile_id = $friend->requester == $user_id ? $friend->accepter : $friend->requester;
                $user = User::find($profile_id);

                // $user = User::where('id', $friend->id)->first();
                $response['friendsList'][$key]['friend_id'] = $user->id;
                $response['friendsList'][$key]['name'] = $user->name;
                $response['friendsList'][$key]['photo'] = get_user_images($user->id);
                $response['friendsList'][$key]['cover_photo'] = get_cover_photos($user->id);

            }

            // $page_data['friendships'] = $friendships;
            // $page_data['friend_requests'] = $friend_requests;

        } else {
            $response['status'] = false;
            $response['message'] = 'unauthorised access';
        }
        // $response = $page_data;
        return $response;
    }
    public function follow(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();
        // $data = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $follwer = new Follower();
            $follwer->follow_id = $id;
            $follwer->user_id = $user_id;
            $follow = $follwer->save();
            if ($follow) {
                $response['status'] = true;
                $response['message'] = 'Follow Successfully';
            } else {
                $response['status'] = false;
                $response['message'] = 'does not find out';
            }
        }
        return $response;
    }

    public function unfollow(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();
        // $data = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $follwer = Follower::where('follow_id', $id)->delete();

            if ($follwer) {
                $response['status'] = true;
                $response['message'] = 'Unfollow Successfully';
            } else {
                $response['status'] = false;
                $response['message'] = 'does not find out';
            }

        }
        return $response;
    }
    public function create_post(Request $request)
    {
        // return $request->all();

        $token = $request->bearerToken();
        $response = array();
        $data = array();


        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            // $rules = array('privacy' => ['required', Rule::in(['private', 'public', 'friends'])]);
            // $validator = Validator::make($request->all(), $rules);
            // if ($validator->fails()) {
            //     return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            // }

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

            $data['user_id'] = $user_id;
            $data['privacy'] = $request->privacy;
            // $data['privacy'] = 'public';

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
                $data['publisher_id'] = $user_id;
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
                $data['description'] = $request->description;
            } else {
                $data['description'] = '';
            }

            if (isset($request->address) && !empty($request->address)) {
                $data['location'] = $request->address;
            }
            // else {
            //     $data['location'] = '';
            // }

            // Mobile App View Image
            $mobile_app_image = FileUploader::upload($request->mobile_app_image, 'public/storage/post/images/');
            $data['mobile_app_image'] = $mobile_app_image;

            $data['status'] = 'active';
            $data['user_reacts'] = json_encode(array());
            $data['shared_user'] = json_encode(array());
            $data['created_at'] = time();
            $data['updated_at'] = $data['created_at'];

            $post_id = Posts::insertGetId($data);
            if ($post_id >= 0) {
                $response['status'] = 200;
                $response['message'] = 'Your post successfully publidhed';
            }

            //add media files
            if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
                //Data validation
                // $response['message'] = 'check image for upload';
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
                        FileUploader::upload($media_file, 'public/storage/post/videos/' . $file_name . '.' . $file_extention);
                        $file_type = 'video';

                    } else {
                        FileUploader::upload($media_file, 'public/storage/post/images/' . $file_name . '.' . $file_extention, 1000, null, 300);
                        $file_type = 'image';

                    }
                    $file_name = $file_name . '.' . $file_extention;
                    //    return [$file_name];
                    $media_file_data = array('user_id' => auth('sanctum')->user()->id, 'post_id' => $post_id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => $request->privacy);

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
                $live['user_id'] = $user_id;
                $live['details'] = json_encode(['link' => url('/streaming/live/' . $post_id), 'status' => TRUE]);
                $live['created_at'] = date('Y-m-d H:i:s', time());
                $live['updated_at'] = $live['created_at'];

                Live_streamings::insert($live);
                $response = array('open_new_tab' => url('/streaming/live/' . $post_id), 'reload' => 0, 'status' => 1, 'function' => 0, 'messageShowOn' => '[name=about]', 'message' => get_phrase('Post has been added to your timeline'));
            } else {
                //Ajax flush message
                // Session::flash('success_message', get_phrase('Your post has been published'));
                // $response = array('reload' => 1);
            }
            return $response;


        }

        //Data validation


    }

    public function edit_post($id, Request $request)
    {

        $token = $request->bearerToken();
        $response = array();
        $data = array();


        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            //$posts = Posts::where('id', $id)->first();

            // $rules = array('privacy' => ['required', Rule::in(['private', 'public', 'friends'])]);
            // $validator = Validator::make($request->all(), $rules);
            // if ($validator->fails()) {
            //     return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            // }

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
                $data['description'] = $request->description;
            }

            // Mobile Preview Upload Image
            $mobile_app_image = FileUploader::upload($request->mobile_app_image, 'public/storage/post/images/');
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

                    $media_file_data = array('user_id' => $user_id, 'post_id' => $id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => $request->privacy);

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
            $response['status'] = 200;
            $response['message'] = 'Your post successfully updated';

            //Ajax flush message
        }
        return $response;
    }
    public function delete_post($id, Request $request)
    {
        $token = $request->bearerToken();
        $response = array();
        $data = array();


        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $done = Posts::where('post_id', $id)->delete();
            if ($done) {
                $response = array('alertMessage' => get_phrase('Post Deleted Successfully'), 'fadeOutElem' => "#postIdentification" . $id);
            }
        }
        return $response;
    }

    public function save_post_report(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();


        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $report = new Report();
            $report->user_id = $user_id;
            $report->post_id = $request->post_id;
            $report->report = $request->report;
            $done = $report->save();
            if ($done) {
                $response = array('alertMessage' => 'Post Report saved Successfully');
            } else {
                $response = array('alertMessage' => 'Something is error');
            }
        }
        return $response;
    }

    public function post_media_file($id, Request $request)
    {
        $token = $request->bearerToken();
        $response = array();
        $postImages = array(); // Initialize an array to store post images

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $mediaFiles = Media_files::where('post_id', $id)->get();

            // Loop through the media files and add them to the postImages array
            foreach ($mediaFiles as $media) {
                $mediaData = array();
                if ($media->file_type == "image") {
                    $mediaData["id"] = $media->id;
                    $mediaData["file_type"] = "image";
                    $mediaData["file_url"] = get_post_images($media->file_name, "optimized");
                } else {
                    $mediaData["id"] = $media->id;
                    $mediaData["file_type"] = "video";
                    $mediaData["file_url"] = get_post_videos($media->file_name);
                }
                $postImages[] = $mediaData; // Add media data to the postImages array
            }
        }

        $response = $postImages;
        return $response;
    }

    public function delete_media_file($id, Request $request)
    {
        $token = $request->bearerToken();
        $response = array();
        $data = array();


        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $media_file = Media_files::where('id', $id)->where('user_id', $user_id);
            if ($media_file->count() > 0) {
                remove_file('public/storage/post/images/' . $media_file->first()->file_name);
                Media_files::find($id)->delete();
                $response = array('alertMessage' => get_phrase('Image deleted successfully'), 'fadeOutElem' => "#previous-uploaded-img-" . $id);
            } else {
                $response = array('alertMessage' => get_phrase('Image not found'));
            }
        }
        return $response;
    }

    public function profile(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();
        $data = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            // $user_id = 4;
            $user = User::where('id', $user_id)->first();

            $followers = Follower::where('user_id', $user_id)->count();
            // User Profile Information
            $response['id'] = $user->id;
            $response['user_role'] = $user->user_role;
            $response['username'] = $user->username;
            $response['email'] = $user->email;
            $response['name'] = $user->name;
            $response['nickname'] = $user->nickname;
            $response['friend'] = $user->friends;
            $response['followers'] = $followers;
            $response['gender'] = $user->gender;
            $response['studied_at'] = $user->studied_at;
            $response['address'] = $user->address;
            $response['profession'] = $user->profession;
            $response['job'] = $user->job;
            $response['marital_status'] = $user->marital_status;
            $response['phone'] = $user->phone;
            $response['date_of_birth'] = date('Y-m-d', $user->date_of_birth);
            $response['about'] = $user->about;
            $response['photo'] = get_user_images($user->id, "optimized");
            $response['cover_photo'] = get_cover_photos($user->id, "optimized");
            $response['status'] = $user->status;
            $response['lastActive'] = $user->lastActive;
            $response['timezone'] = $user->timezone;
            $response['email_verified_at'] = $user->email_verified_at;
            $response['created_at'] = date_create($user->created_at)->format('l,j F Y');
            $response['updated_at'] = $user->updated_at;
            $response['payment_settings'] = $user->payment_settings;

            // Fetch Posts by the User
            $posts = Posts::orderBy('post_id', 'desc')->where('user_id', $user->id)->where('posts.publisher', 'post')->get();
            $response['posts'] = [];
            foreach ($posts as $key1 => $post) {
                $response['posts'][$key1]['post_id'] = $post->post_id;
                $response['posts'][$key1]['user_id'] = $post->user_id;
                $response['posts'][$key1]['name'] = $user->name;
                $response['posts'][$key1]['photo'] = get_user_images($user->id, "optimized");
                $response['posts'][$key1]['publisher'] = $post->publisher;
                $response['posts'][$key1]['publisherId'] = $post->publisher_id;
                $response['posts'][$key1]['location'] = $post->location != null ? $post->location : "";
                $response['posts'][$key1]['description'] = $post->description;
                $response['posts'][$key1]['post_type'] = $post->post_type;
                $response['posts'][$key1]['privacy'] = $post->privacy;
                $userReacts = json_decode($post->user_reacts, true);
                // $response['posts'][$key1]['userReacts'] = $userReacts;
                if (isset($userReacts[$user_id])) {
                    $response['posts'][$key1]['userReaction'] = $userReacts[$user_id];
                } else {
                    // Handle the case where the user reaction is not set
                    $response['posts'][$key1]['userReaction'] = null; // Or any default value
                }

                // $userReaction = null;
                // if ($user_id && isset($userReacts[$user_id])) {
                //     $userReaction = $userReacts[$user_id];

                // }
                // $response['posts'][$key1]['userReaction'] = $userReaction;

                // Initialize counters
                $likeCount = 0;
                $loveCount = 0;
                $sadCount = 0;
                $hahaCount = 0;
                $angryCount = 0;

                // Count occurrences of reactions
                foreach ($userReacts as $react) {
                    switch ($react) {
                        case 'like':
                            $likeCount++;
                            break;
                        case 'love':
                            $loveCount++;
                            break;
                        case 'sad':
                            $sadCount++;
                            break;
                        case 'haha':
                            $hahaCount++;
                            break;
                        case 'angry':
                            $angryCount++;
                            break;
                        default:
                            // Do nothing or handle unexpected reactions
                            break;
                    }

                }
                // Calculate the total reactions
                $totalReacts = $likeCount + $loveCount + $sadCount + $hahaCount + $angryCount;
                $response['posts'][$key1]['reaction_counts'] = [
                    'like' => $likeCount,
                    'love' => $loveCount,
                    'sad' => $sadCount,
                    'haha' => $hahaCount,
                    'angry' => $angryCount,
                    'total' => $totalReacts // Include total reactions count
                ];

                $commentsCount = Comments::where('id_of_type', $post->post_id)->count();
                $response['posts'][$key1]['comments_count'] = $commentsCount;

                // Media section of posts
                // $media = Media_files::where('post_id', $post->post_id)->first();
                // $response['posts'][$key1]['post_image'] = (!empty($media->file_name)) ? get_post_images($media->file_name) : '';

                $mediaFiles = Media_files::where('post_id', $post->post_id)->get();

                // Initialize an array to store post images

                $response['posts'][$key1]['post_images'] = [];
                $response['posts'][$key1]['fileType'] = 'text';
                // Loop through the media files and add them to the postImages array
                foreach ($mediaFiles as $media) {

                    if ($media->file_type == "image") {
                        $response['posts'][$key1]['post_images'][] = get_post_images($media->file_name, "optimized");
                    } else {
                        $response['posts'][$key1]['post_images'][] = get_post_videos($media->file_name);
                    }
                    $response['posts'][$key1]['fileType'] = $media->file_type;
                }
                $response['posts'][$key1]['thumbnail'] = $post->mobile_app_image != null ? get_post_images($post->mobile_app_image) : "";

                $createdDate = Carbon::createFromTimestamp(strtotime($post->posted_on));
                $daysDifference = $createdDate->diffInDays(Carbon::now());
                if ($daysDifference < 7) {
                    // Show "time ago" format
                   $response['posts'][$key1]['created_at']  = $createdDate->diffForHumans();
                } else {
                    // Show the exact date and time
                    $response['posts'][$key1]['created_at'] = $createdDate->toDayDateTimeString();
                    // Example format: 'Mon, Jan 1, 2024 12:00 AM'
                }

                // $response['posts'][$key1]['created_at'] = date('M d \a\t H:i A', strtotime($post->created_at));

                $followers = Follower::where('user_id', $user_id)->get();
                $response['posts'][$key1]['follow'] = 'Follow';
                foreach ($followers as $follo) {
                    if ($follo->follow_id == $post->user_id) {
                        $response['posts'][$key1]['follow'] = 'Unfollow';
                    }
                }

                $taggedUserIds = json_decode($post->tagged_user_ids, true);
                $taggedUsers = User::whereIn('id', $taggedUserIds)->get(['id', 'name']);
                $response['posts'][$key1]['taggedUserList'] = [];
                foreach ($taggedUsers as $key2 => $tags) {
                    $response['posts'][$key1]['taggedUserList'][$key2]['id'] = $tags->id;
                    $response['posts'][$key1]['taggedUserList'][$key2]['name'] = $tags->name;
                }





            }

            $friendships = Friendships::where(function ($query) use ($user_id) {
                $query->where('accepter', $user_id)
                    ->orWhere('requester', $user_id);
            })
                ->where('is_accepted', 1)
                ->orderBy('friendships.importance', 'desc')
                ->get();
            // $user = User::where('id', $user_id)->first();

            $response['friends'] = [];

            // $friend_list = User::whereIn('id', $friend)->get();
            foreach ($friendships as $key => $friend) {

                $profile_id = $friend->requester == $user_id ? $friend->accepter : $friend->requester;
                $user = User::find($profile_id);

                $followers = Follower::where('user_id', $user_id)->get();
                $response['friends'][$key]['follow'] = 'Follow';
                foreach ($followers as $follo) {
                    if ($follo->follow_id == $profile_id) {
                        $response['friends'][$key]['follow'] = 'Unfollow';
                    }
                }
                // $user = User::where('id', $friend->id)->first();
                $response['friends'][$key]['friend_id'] = $user->id;
                $response['friends'][$key]['name'] = $user->name;
                $response['friends'][$key]['photo'] = get_user_images($user->id, "optimized");
                $response['friends'][$key]['cover_photo'] = get_cover_photos($user->id, "optimized");

            }


            return response($response, 200);
        } else {
            // Handle invalid or missing token
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
    public function other_profile(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();
        $data = array();

        if (isset($token) && $token != '') {
            $my_id = auth('sanctum')->user()->id;
            $user_id = $id;
            $user = User::where('id', $user_id)->first();

            $is_chat = 'not_chat'; // Initialize profile ID as 0
            $msgthread_id = 0; // Initialize profile ID as 0

            // Get chat messages involving the current user
            $chats = Message_thrade::whereIn('reciver_id', [$my_id, $user_id])
                ->WhereIn('sender_id', [$my_id, $user_id])
                ->get();

            // Loop through chat messages to find matching profile ID
            foreach ($chats as $chat) {
                if ($chat->reciver_id == $my_id || $chat->sender_id == $my_id) {
                    // Set the profile ID to the matching user ID
                    $is_chat = "chat";
                    $msgthread_id = $chat->id;
                    // Break the loop once a match is found
                    break;
                }
            }

            $frnd_id = 0;
            $requested = "Add Friend";
            $req = Friendships::whereIn('accepter', [$my_id, $user_id])
                ->WhereIn('requester', [$my_id, $user_id])
                ->get();
            foreach ($req as $chat) {
                if ($chat->accepter == $my_id || $chat->requester == $my_id) {
                    // Set the profile ID to the matching user ID
                    $is_chat = "chat";
                    $frnd_id = $chat->id;
                    $requested = $chat->is_accepted == 0 ? ($chat->requester == $my_id ? 'Requested' : "Confirm") : "Friend";


                    // Break the loop once a match is found
                    break;
                }
            }
            $followers = Follower::where('user_id', $my_id)->get();
            $follow = 'Follow';
            foreach ($followers as $follo) {
                if ($follo->follow_id == $user_id) {
                    $follow = 'Unfollow';
                }
                // else{
                //     $follow = 'Unfollow';
                // }
            }

            $followers = Follower::where('user_id', $user_id)->count();
            // User Profile Information
            $response['id'] = $user->id;
            $response['thrade'] = $msgthread_id;
            // $response['frnd'] = $frnd_id;
            $response['requested'] = $requested;
            $response['follow'] = $follow;
            $response['user_role'] = $user->user_role;
            $response['username'] = $user->username;
            $response['email'] = $user->email;
            $response['name'] = $user->name;
            $response['nickname'] = $user->nickname;
            $response['friend'] = $user->friends;
            $response['followers'] = $followers;
            $response['gender'] = $user->gender;
            $response['studied_at'] = $user->studied_at;
            $response['address'] = $user->address;
            $response['profession'] = $user->profession;
            $response['job'] = $user->job;
            $response['marital_status'] = $user->marital_status;
            $response['phone'] = $user->phone;
            $response['date_of_birth'] = date('Y-m-d', $user->date_of_birth);
            $response['about'] = $user->about;
            $response['photo'] = get_user_images($user->id, "optimized");
            $response['cover_photo'] = get_cover_photos($user->id, "optimized");
            $response['status'] = $user->status;
            $response['lastActive'] = $user->lastActive;
            $response['timezone'] = $user->timezone;
            $response['email_verified_at'] = $user->email_verified_at;
            $response['created_at'] = date_create($user->created_at)->format('l,j F Y');
            $response['updated_at'] = $user->updated_at;
            $response['payment_settings'] = $user->payment_settings;

            // Fetch Posts by the User
            $posts = Posts::orderBy('post_id', 'desc')->where('user_id', $user->id)->where('posts.publisher', 'post')->get();
            $response['posts'] = [];
            foreach ($posts as $key1 => $post) {
                $response['posts'][$key1]['post_id'] = $post->post_id;
                $response['posts'][$key1]['user_id'] = $post->user_id;
                $response['posts'][$key1]['name'] = $user->name;
                $response['posts'][$key1]['photo'] = get_user_images($user->id, "optimized");
                $response['posts'][$key1]['publisher'] = $post->publisher;
                $response['posts'][$key1]['publisherId'] = $post->publisher_id;
                $response['posts'][$key1]['location'] = $post->location != null ? $post->location : "";
                $response['posts'][$key1]['description'] = $post->description;
                $response['posts'][$key1]['post_type'] = $post->post_type;
                $response['posts'][$key1]['privacy'] = $post->privacy;
                $userReacts = json_decode($post->user_reacts, true);
                // $response['posts'][$key1]['userReacts'] = $userReacts;
                if (isset($userReacts[$user_id])) {
                    $response['posts'][$key1]['userReaction'] = $userReacts[$user_id];
                } else {
                    // Handle the case where the user reaction is not set
                    $response['posts'][$key1]['userReaction'] = null; // Or any default value
                }

                // $userReaction = null;
                // if ($user_id && isset($userReacts[$user_id])) {
                //     $userReaction = $userReacts[$user_id];

                // }
                // $response['posts'][$key1]['userReaction'] = $userReaction;

                // Initialize counters
                $likeCount = 0;
                $loveCount = 0;
                $sadCount = 0;
                $hahaCount = 0;
                $angryCount = 0;

                // Count occurrences of reactions
                foreach ($userReacts as $react) {
                    switch ($react) {
                        case 'like':
                            $likeCount++;
                            break;
                        case 'love':
                            $loveCount++;
                            break;
                        case 'sad':
                            $sadCount++;
                            break;
                        case 'haha':
                            $hahaCount++;
                            break;
                        case 'angry':
                            $angryCount++;
                            break;
                        default:
                            // Do nothing or handle unexpected reactions
                            break;
                    }

                }
                // Calculate the total reactions
                $totalReacts = $likeCount + $loveCount + $sadCount + $hahaCount + $angryCount;
                $response['posts'][$key1]['reaction_counts'] = [
                    'like' => $likeCount,
                    'love' => $loveCount,
                    'sad' => $sadCount,
                    'haha' => $hahaCount,
                    'angry' => $angryCount,
                    'total' => $totalReacts // Include total reactions count
                ];

                $commentsCount = Comments::where('id_of_type', $post->post_id)->count();
                $response['posts'][$key1]['comments_count'] = $commentsCount;

                // Media section of posts
                // $media = Media_files::where('post_id', $post->post_id)->first();
                // $response['posts'][$key1]['post_image'] = (!empty($media->file_name)) ? get_post_images($media->file_name) : '';

                $mediaFiles = Media_files::where('post_id', $post->post_id)->get();

                // Initialize an array to store post images

                $response['posts'][$key1]['post_images'] = [];
                $response['posts'][$key1]['fileType'] = 'text';
                // Loop through the media files and add them to the postImages array
                foreach ($mediaFiles as $media) {

                    if ($media->file_type == "image") {
                        $response['posts'][$key1]['post_images'][] = get_post_images($media->file_name, "optimized");
                    } else {
                        $response['posts'][$key1]['post_images'][] = get_post_videos($media->file_name);
                    }
                    $response['posts'][$key1]['fileType'] = $media->file_type;
                }
                $response['posts'][$key1]['thumbnail'] = $post->mobile_app_image != null ? get_post_images($post->mobile_app_image) : "";

                // $response['posts'][$key1]['created_at'] = date('M d \a\t H:i A', strtotime($post->created_at));
                $createdDate = Carbon::createFromTimestamp(strtotime($post->posted_on));
                $daysDifference = $createdDate->diffInDays(Carbon::now());
                if ($daysDifference < 7) {
                    // Show "time ago" format
                    $response['posts'][$key1]['created_at'] = $createdDate->diffForHumans();
                } else {
                    // Show the exact date and time
                   $response['posts'][$key1]['created_at'] = $createdDate->toDayDateTimeString();
                    // Example format: 'Mon, Jan 1, 2024 12:00 AM'
                }

                $followers = Follower::where('user_id', $my_id)->get();
                $response['posts'][$key1]['follow'] = 'Follow';
                foreach ($followers as $follo) {
                    if ($follo->follow_id == $post->user_id) {
                        $response['posts'][$key1]['follow'] = 'Unfollow';
                    }
                }

                $taggedUserIds = json_decode($post->tagged_user_ids, true);
                $taggedUsers = User::whereIn('id', $taggedUserIds)->get(['id', 'name']);
                $response['posts'][$key1]['taggedUserList'] = [];
                foreach ($taggedUsers as $key2 => $tags) {
                    $response['posts'][$key1]['taggedUserList'][$key2]['id'] = $tags->id;
                    $response['posts'][$key1]['taggedUserList'][$key2]['name'] = $tags->name;
                }



            }

            $friendships = Friendships::where(function ($query) use ($user_id) {
                $query->where('accepter', $user_id)
                    ->orWhere('requester', $user_id);
            })
                ->where('is_accepted', 1)
                ->orderBy('friendships.importance', 'desc')
                ->get();
            // $user = User::where('id', $user_id)->first();

            $response['friends'] = [];

            // $friend_list = User::whereIn('id', $friend)->get();
            foreach ($friendships as $key => $friend) {
                $profile_id = $friend->requester == $user_id ? $friend->accepter : $friend->requester;
                $user = User::find($profile_id);

                $followers = Follower::where('user_id', $my_id)->get();
                $response['friends'][$key]['follow'] = 'Follow';
                foreach ($followers as $follo) {
                    if ($follo->follow_id == $profile_id) {
                        $response['friends'][$key]['follow'] = 'Unfollow';
                    }
                }


                // $user = User::where('id', $friend->id)->first();
                $response['friends'][$key]['friend_id'] = $user->id;
                $response['friends'][$key]['name'] = $user->name;
                $response['friends'][$key]['photo'] = get_user_images($user->id, "optimized");
                $response['friends'][$key]['cover_photo'] = get_cover_photos($user->id, "optimized");

            }


            return response($response, 200);
        } else {
            // Handle invalid or missing token
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    function edit_profile(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;


            $data['name'] = $request->name;
            $data['nickname'] = $request->nickname;
            $data['job'] = $request->job;
            $data['studied_at'] = $request->studied_at;
            $data['marital_status'] = $request->marital_status;
            $data['gender'] = $request->gender;
            $data['address'] = $request->address;
            $data['phone'] = $request->phone;
            $data['date_of_birth'] = strtotime($request->date_of_birth);
            $data['about'] = $request->about;

            // return $data['date_of_birth'];
            $done = User::where('id', $user_id)->update($data);

            if ($done) {
                $response['status'] = 200;
                $response['message'] = 'Your data updated';
            } else {
                $response['status'] = 400;
                $response['message'] = ' data not updated';
            }
        } else {
            $response['status'] = 500;
            $response['message'] = ' unauthorised access';
        }
        return $response;
    }

    function update_profile_pic(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $rules = array(
                'profile_photo' => 'nullable',

            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }

            if ($request->profile_photo && !empty($request->profile_photo)) {

                $file_name = FileUploader::upload($request->profile_photo, 'public/storage/userimage', 800);

                //Create post for updating profile photo
                $this->create_profile_photo_post($request->profile_photo, $file_name);

                //Update to database
                $data['photo'] = $file_name;
            }

            $done = Users::where('id', $user_id)->update($data);
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'Profile pic updated successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to Profile pic updated ';
            }
        }
        return $response;
    }

    function create_profile_photo_post($image, $file_name)
    {
        FileUploader::upload($image, 'public/storage/post/images/' . $file_name, 800);

        $data['user_id'] = auth('sanctum')->user()->id;
        $data['privacy'] = 'public';
        $data['publisher'] = 'post';
        $data['publisher_id'] = auth('sanctum')->user()->id;
        $data['post_type'] = 'profile_picture';
        $data['tagged_user_ids'] = json_encode(array());
        $data['activity_id'] = 0;
        $data['location'] = '';
        $data['description'] = '';
        $data['status'] = 'active';
        $data['user_reacts'] = json_encode(array());
        $data['created_at'] = time();
        $data['updated_at'] = $data['created_at'];
        $post_id = Posts::insertGetId($data);

        //Stored to media files table 
        $media_file_data = array('user_id' => auth('sanctum')->user()->id, 'post_id' => $post_id, 'file_name' => $file_name, 'file_type' => 'image', 'privacy' => 'public');
        $media_file_data['created_at'] = time();
        $media_file_data['updated_at'] = $media_file_data['created_at'];
        Media_files::create($media_file_data);
    }

    function update_cover_pic(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            // Validate the input and return correct response
            $rules = array('cover_photo' => 'required');
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }

            $file_name = FileUploader::upload($request->cover_photo, 'public/storage/cover_photo', 1120);

            //Update to database
            $data['cover_photo'] = $file_name;
            $done = Users::where('id', $user_id)->update($data);
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'Cover pic updated successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to Cover pic updated ';
            }
        }
        return $response;
    }
    function profile_photos(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $all_photos = Media_files::where('user_id', $user_id)
                ->where('file_type', 'image')
                ->whereNull('page_id')
                ->whereNull('story_id')
                ->whereNull('product_id')
                ->whereNull('group_id')
                ->whereNull('chat_id')
                ->orderBy('id', 'DESC')->get();
            $photoArray = [];
            foreach ($all_photos as $photo) {
                $photoArray[] = [
                    'post_id' => $photo->post_id,
                    'photo' => get_post_images($photo->file_name, "optimized"),
                ];
            }
            $page_data['all_photos'] = $photoArray;

            $all_albums = Albums::where('user_id', $user_id)
                ->whereNull('page_id')
                ->whereNull('group_id')
                ->take(6)->orderBy('id', 'DESC')->get();

            $albumArray = [];
            foreach ($all_albums as $album) {
                $albumArray[] = [
                    'id' => $album->id,
                    'user_id' => $album->user_id,
                    'title' => $album->title,
                    'sub_title' => $album->sub_title,
                    'thumbnail' => get_group_event_photos($album->thumbnail, 'album', 'thumbnails'),
                ];
            }
            $page_data['all_albums'] = $albumArray;

            $all_videos = Media_files::where('user_id', $user_id)
                ->where('file_type', 'video')
                ->whereNull('story_id')
                ->whereNull('page_id')
                ->whereNull('album_id')
                ->whereNull('product_id')
                ->whereNull('chat_id')
                ->orderBy('id', 'DESC')->get();
            $videoArray = [];
            foreach ($all_videos as $video) {
                $videoArray[] = [
                    'post_id' => $video->post_id,
                    'video' => get_post_videos($video->file_name),
                ];
            }
            $page_data['all_videos'] = $videoArray;

            $response = $page_data;
        }
        return response()->json($response);
    }
    function other_profile_photos(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            // $user_id = auth('sanctum')->user()->id;
            $user_id = $id;
            $all_photos = Media_files::where('user_id', $user_id)
                ->where('file_type', 'image')
                ->whereNull('page_id')
                ->whereNull('story_id')
                ->whereNull('product_id')
                ->whereNull('group_id')
                ->whereNull('chat_id')
                ->orderBy('id', 'DESC')->get();
            $photoArray = [];
            foreach ($all_photos as $photo) {
                $photoArray[] = [
                    'post_id' => $photo->post_id,
                    'photo' => get_post_images($photo->file_name, "optimized"),
                ];
            }
            $page_data['all_photos'] = $photoArray;

            $all_albums = Albums::where('user_id', $user_id)
                ->whereNull('page_id')
                ->whereNull('group_id')
                ->take(6)->orderBy('id', 'DESC')->get();

            $albumArray = [];
            foreach ($all_albums as $album) {
                $albumArray[] = [
                    'id' => $album->id,
                    'user_id' => $album->user_id,
                    'title' => $album->title,
                    'sub_title' => $album->sub_title,
                    'thumbnail' => get_group_event_photos($album->thumbnail, 'album', 'thumbnails'),
                ];
            }
            $page_data['all_albums'] = $albumArray;

            $all_videos = Media_files::where('user_id', $user_id)
                ->where('file_type', 'video')
                ->whereNull('story_id')
                ->whereNull('page_id')
                ->whereNull('album_id')
                ->whereNull('product_id')
                ->whereNull('chat_id')
                ->orderBy('id', 'DESC')->get();
            $videoArray = [];
            foreach ($all_videos as $video) {
                $videoArray[] = [
                    'post_id' => $video->post_id,
                    'video' => get_post_videos($video->file_name),
                ];
            }
            $page_data['all_videos'] = $videoArray;

            $response = $page_data;
        }
        return $response;
    }
    function single_post(Request $request, $post_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $post = Posts::where('post_id', $post_id)->first();
            // foreach ($posts as $post) {
            $user = User::where("id", $post->user_id)->first();

            // Fetch media files associated with the post
            $mediaFiles = Media_files::where('post_id', $post->post_id)->get();

            // Initialize an array to store post images
            $postImages = [];
            $file = 'text';

            // Loop through the media files and add them to the postImages array
            foreach ($mediaFiles as $media) {
                if ($media->file_type == "image") {
                    $postImages[] = get_post_images($media->file_name, "optimized");
                } else {
                    $postImages[] = get_post_videos($media->file_name);
                }
                $file = $media->file_type;
            }

            // comment count 
            $commentsCount = Comments::where('id_of_type', $post->post_id)->count();

            // user react of each post
            $userReacts = json_decode($post->user_reacts, true);

            // Initialize counters for reaction types
            $reactionsCount = array_fill_keys(['like', 'love', 'sad', 'haha', 'angry'], 0);

            // Count occurrences of reactions
            foreach ($userReacts as $react) {
                if (array_key_exists($react, $reactionsCount)) {
                    $reactionsCount[$react]++;
                }
            }

            // Convert the Unix timestamp to a formatted date string
            // $formattedDate = date('M d \a\t H:i A', strtotime($post->created_at));
            $createdDate = Carbon::createFromTimestamp(strtotime($post->posted_on));
            $daysDifference = $createdDate->diffInDays(Carbon::now());
            if ($daysDifference < 7) {
                // Show "time ago" format
                $formattedDate = $createdDate->diffForHumans();
            } else {
                // Show the exact date and time
                $formattedDate = $createdDate->toDayDateTimeString();
                // Example format: 'Mon, Jan 1, 2024 12:00 AM'
            }


            // Calculate the total reactions
            $totalReacts = array_sum($reactionsCount);
            $followers = Follower::where('user_id', $user_id)->get();
            $follow = 'Follow';
            foreach ($followers as $follo) {
                if ($follo->follow_id == $post->user_id) {
                    $follow = 'Unfollow';
                }
            }

            $taggedUserIds = json_decode($post->tagged_user_ids, true);
            $taggedUsers = User::whereIn('id', $taggedUserIds)->get(['id', 'name']);
            $taggedUserList = [];
            foreach ($taggedUsers as $tags) {
                $taggedUserList[] = [
                    'id' => $tags->id,
                    'name' => $tags->name
                ];
            }


            // Construct the response array for the current post
            $timeline = [
                'post_id' => $post->post_id,
                'user_id' => $post->user_id,
                'name' => $user->name,
                'photo' => get_user_images($user->id, "optimized"),
                'publisher' => $post->publisher,
                'location' => $post->location != null ? $post->location : "",
                'post_type' => $post->post_type,
                'fileType' => $file,
                'privacy' => $post->privacy,
                'post_images' => $postImages, // Array of post images
                'thumbnail' => $post->mobile_app_image != null ? get_post_images($post->mobile_app_image) : "",
                'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,
                'description' => $post->description != null ? $post->description : "",
                'commentsCount' => $commentsCount,
                'reaction_counts' => $reactionsCount,
                'total' => $totalReacts,
                'created_at' => $formattedDate,
                'follow' => $follow,
                'taggedUserList' => $taggedUserList,
                // Add other fields as needed
            ];
            // }

            $response = $timeline;
        }
        return response()->json($response);
    }



    public function reaction(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            try {
                $post_id = $request->post_id; // Get the post_id from the request
                $user_id = auth('sanctum')->user()->id; // Get the current authenticated user's id
                $reactionValue = $request->react; // Get the reaction value from the request

                // Retrieve the post from the database
                $post = Posts::where('post_id', $post_id)->first();

                // Check if the post exists
                if ($post) {
                    // Ensure user_reacts is initialized properly
                    $userReacts = json_decode($post->user_reacts, true);

                    // Remove the user's reaction if the reaction is "none"
                    if ($reactionValue === "none") {
                        unset($userReacts[$user_id]);
                    } else {
                        // Update the user's reaction
                        $userReacts[$user_id] = $reactionValue;
                    }

                    // Update the user_reacts column in the database
                    $post->update(['user_reacts' => json_encode($userReacts)]);

                    // Return the updated array
                    $response = $userReacts;
                    return response()->json($response, 200);
                } else {
                    // Handle the case where the post does not exist
                    return response()->json(['error' => 'Post not found'], 404);
                }
            } catch (\Exception $e) {
                // Handle database errors
                return response()->json(['error' => $e->getMessage()], 500);
            }
        } else {
            // Handle invalid or missing token
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function post_comment(Request $request)
    {

        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {

            $user_id = auth('sanctum')->user()->id;
            // $form_data = $request->all();
            if ($request->comment == 'comment') {
                $data['parent_id'] = $request->parent_id;
                $data['user_id'] = $user_id;
                $data['is_type'] = $request->is_type;
                $data['id_of_type'] = $request->id_of_type;
                $data['description'] = $request->description;
                $data['user_reacts'] = json_encode(array());
                $data['created_at'] = time();
                $data['updated_at'] = $data['created_at'];
                $comment_id = Comments::insertGetId($data);
                if ($comment_id >= 0) {
                    $response['status'] = 200;
                    $response['message'] = 'Your comment successfully publidhed';
                }
            } else {
                $comment_id = $request->comment_id; // Get the post_id from the request
                $user_id = auth('sanctum')->user()->id; // Get the current authenticated user's id
                $reactionValue = $request->react; // Get the reaction value from the request

                // Retrieve the post from the database
                $comment = Comments::where('comment_id', $comment_id)->first();

                // Check if the post exists
                if ($comment) {
                    // Ensure user_reacts is initialized properly
                    $userReacts = json_decode($comment->user_reacts, true);

                    // Remove the user's reaction if the reaction is "none"
                    if ($reactionValue === "none") {
                        unset($userReacts[$user_id]);
                    } else {
                        // Update the user's reaction
                        $userReacts[$user_id] = $reactionValue;
                    }

                    // Update the user_reacts column in the database
                    $comment->update(['user_reacts' => json_encode($userReacts)]);

                    // Return the updated array
                    $response = $userReacts;
                    return response()->json($response, 200);
                } else {
                    // Handle the case where the post does not exist
                    return response()->json(['error' => 'Comment not found'], 404);
                }
            }


        }

        return $response;

    }

    // public function comment_reaction(Request $request)
    // {
    //     $token = $request->bearerToken();
    //     $response = array();

    //     if (isset($token) && $token != '') {
    //         try {
    //             $comment_id = $request->comment_id; // Get the post_id from the request
    //             $user_id = auth('sanctum')->user()->id; // Get the current authenticated user's id
    //             $reactionValue = $request->react; // Get the reaction value from the request

    //             // Retrieve the post from the database
    //             $comment = Comments::where('comment_id', $comment_id)->first();

    //             // Check if the post exists
    //             if ($comment) {
    //                 // Ensure user_reacts is initialized properly
    //                 $userReacts = json_decode($comment->user_reacts, true);

    //                 // Remove the user's reaction if the reaction is "none"
    //                 if ($reactionValue === "none") {
    //                     unset($userReacts[$user_id]);
    //                 } else {
    //                     // Update the user's reaction
    //                     $userReacts[$user_id] = $reactionValue;
    //                 }

    //                 // Update the user_reacts column in the database
    //                 $comment->update(['user_reacts' => json_encode($userReacts)]);

    //                 // Return the updated array
    //                 $response = $userReacts;
    //                 return response()->json($response, 200);
    //             } else {
    //                 // Handle the case where the post does not exist
    //                 return response()->json(['error' => 'Comment not found'], 404);
    //             }
    //         } catch (\Exception $e) {
    //             // Handle database errors
    //             return response()->json(['error' => $e->getMessage()], 500);
    //         }
    //     } else {
    //         // Handle invalid or missing token
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }
    // }


    // public function get_comment(Request $request)
    // {

    //     $token = $request->bearerToken();
    //     $response = array();

    //     if (isset($token) && $token != '') {

    //         $user_id = auth('sanctum')->user()->id;
    //         // $form_data = $request->all();
    //         // Fetch Posts by the User
    //         $posts = Posts::orderBy('post_id', 'desc')->where('user_id', $user_id)->get();
    //         foreach ($posts as $key1 => $post) {
    //             // comment section
    //             $comments = Comments::where('id_of_type', $post->post_id)->get(); // Use get() to retrieve all comments
    //             $commentsData = [];

    //             foreach ($comments as $comment) {
    //                 $commentData = [
    //                     'comment_id' => $comment->comment_id,
    //                     'post_id' => $comment->id_of_type,
    //                     'description' => $comment->description,
    //                     'replies' => [] // Initialize replies array
    //                 ];

    //                 // Fetch replies for this comment
    //                 $replies = Comments::where('parent_id', $comment->comment_id)->get();

    //                 foreach ($replies as $reply) {
    //                     $replyData = [
    //                         'reply_id' => $reply->comment_id,
    //                         'description' => $reply->description
    //                     ];
    //                     $commentData['replies'][] = $replyData;
    //                 }

    //                 $commentsData[] = $commentData;
    //             }

    //             $response['posts'][$key1]['comments'] = $commentsData;
    //         }
    //     }
    //     return $response;

    // }

    public function get_comment(Request $request, $postId)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $posts = Posts::orderBy('post_id', 'desc')->where('post_id', $postId)->get();

            // foreach ($posts as $post) {
            $comments = Comments::orderBy('comment_id', 'desc')->where('id_of_type', $postId)->where('parent_id', '0')->get();
            $postComments = [];

            foreach ($comments as $comment) {
                $userReacts = json_decode($comment->user_reacts, true);

                // Initialize counters
                $likeCount = 0;
                $loveCount = 0;
                $sadCount = 0;
                $hahaCount = 0;
                $angryCount = 0;

                // Count occurrences of reactions
                foreach ($userReacts as $react) {
                    switch ($react) {
                        case 'like':
                            $likeCount++;
                            break;
                        case 'love':
                            $loveCount++;
                            break;
                        case 'sad':
                            $sadCount++;
                            break;
                        case 'haha':
                            $hahaCount++;
                            break;
                        case 'angry':
                            $angryCount++;
                            break;
                        default:
                            // Do nothing or handle unexpected reactions
                            break;
                    }
                }
                // Calculate the total reactions
                $totalReacts = $likeCount + $loveCount + $sadCount + $hahaCount + $angryCount;

                $createdOn = Carbon::createFromTimestamp($comment->created_at);
                if (Carbon::now()->diffInDays($createdOn) < 7) {
                    $formattedDate = $createdOn->diffForHumans();
                } else {
                    $formattedDate = $createdOn->toDayDateTimeString();
                }

                $commentData = [
                    'comment_id' => $comment->comment_id,
                    'post_id' => $comment->id_of_type,
                    'user_id' => $comment->user_id,
                    'post_type' => $comment->is_type,
                    'description' => $comment->description,
                    // $user = User::where('id', $comment->user_id)->first(),
                    'name' => User::where('id', $comment->user_id)->first()->name,
                    'photo' => get_user_images(User::where('id', $comment->user_id)->first()->id),
                    // 'user_react' => $comment->user_reacts,
                    // $response['posts'][$key1]['userReacts'] = $userReacts;
                    // 'user_react' => $userReacts,
                    'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,
                    
                    'reaction_counts' => [
                        'like' => $likeCount,
                        'love' => $loveCount,
                        'sad' => $sadCount,
                        'haha' => $hahaCount,
                        'angry' => $angryCount,
                        'total' => $totalReacts // Include total reactions count
                    ],
                    'created' => $formattedDate,
                    
                    'replies' => []
                ];

                $replies = Comments::where('parent_id', $comment->comment_id)->get();

                foreach ($replies as $reply) {
                    $userReacts = json_decode($reply->user_reacts, true);

                    // Initialize counters
                    $likeCount = 0;
                    $loveCount = 0;
                    $sadCount = 0;
                    $hahaCount = 0;
                    $angryCount = 0;

                    // Count occurrences of reactions
                    foreach ($userReacts as $react) {
                        switch ($react) {
                            case 'like':
                                $likeCount++;
                                break;
                            case 'love':
                                $loveCount++;
                                break;
                            case 'sad':
                                $sadCount++;
                                break;
                            case 'haha':
                                $hahaCount++;
                                break;
                            case 'angry':
                                $angryCount++;
                                break;
                            default:
                                // Do nothing or handle unexpected reactions
                                break;
                        }
                    }
                    // Calculate the total reactions
                    $totalReacts = $likeCount + $loveCount + $sadCount + $hahaCount + $angryCount;
                     // Format the created_at date for replies
                     $replyCreatedOn = Carbon::createFromTimestamp($reply->created_at);
                     if (Carbon::now()->diffInDays($replyCreatedOn) < 7) {
                         $replyFormattedDate = $replyCreatedOn->diffForHumans();
                     } else {
                         $replyFormattedDate = $replyCreatedOn->toDayDateTimeString();
                     }

                    $replyData = [
                        'reply_id' => $reply->comment_id,
                        'post_id' => $reply->id_of_type,
                        'user_id' => $reply->user_id,
                        'post_type' => $reply->is_type,
                        'name' => User::where('id', $reply->user_id)->first()->name,
                        'photo' => get_user_images(User::where('id', $reply->user_id)->first()->id),
                        'description' => $reply->description,
                        'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,

                        'reaction_counts' => [
                            'like' => $likeCount,
                            'love' => $loveCount,
                            'sad' => $sadCount,
                            'haha' => $hahaCount,
                            'angry' => $angryCount,
                            'total' => $totalReacts // Include total reactions count
                        ],
                        'created' => $replyFormattedDate,
                    ];
                    $commentData['replies'][] = $replyData;
                }

                $postComments[] = $commentData;
            }

            $response = $postComments;
            // }
        }

        return response()->json($response); // Return as JSON response
    }

    public function comment_delete(Request $request, $comment_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $done = Comments::where('comment_id', $comment_id)->delete();
            if ($done) {
                $response = array('alertMessage' => get_phrase('Comment Deleted Successfully'), 'fadeOutElem' => "#comment_" . $comment_id);
            }
        }
        return $response;
    }


    public function groups(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $groups = Group::orderBy('id', 'desc')->get();

            if ($groups->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No groups found';
            } else {
                $groupArray = [];

                foreach ($groups as $group) {
                    $group_members_count = Group_member::where('group_id', $group->id)->count();
                    $group_members = Group_member::where('group_id', $group->id)->get();
                    $matchingFriendsCount = 0; // Initialize outside the loop
                    $is_Joined = 0; // Initialize outside the loop
                    $response1 = [];

                    if ($group_members->count() > 0) {
                        foreach ($group_members as $member) {
                            $user = User::where('id', $user_id)->first();
                            $user1 = User::where('id', $member->user_id)->first();
                            $friendsList = json_decode($user->friends, true);
                            // $countfriends = count($friendsList);

                            // Count matching friends
                            // $matchingFriendsCount = count(array_intersect($friendsList, $group_members->pluck('user_id')->toArray()));
                            foreach ($friendsList as $friendId) {
                                if ($friendId == $member->user_id) {
                                    $matchingFriendsCount++;
                                }
                            }
                            // foreach ($user_id as $userId) {
                            if ($user_id == $member->user_id) {
                                $is_Joined++;
                            }
                            // }

                            $response1[] = [
                                'id' => $member->id,
                                'user_id' => $member->user_id,
                                'group_id' => $member->group_id,
                                'name' => $user1->name,
                                'photo' => get_user_images($user1->id),
                                // 'countfriends' => $countfriends,
                            ];
                        }
                    }

                    $groupArray[] = [
                        'id' => $group->id,
                        'user_id' => $group->user_id,
                        'title' => $group->title,
                        'privacy' => $group->privacy,
                        'subtitle' => $group->subtitle,
                        'location' => $group->location != null ? $group->location : "",
                        'status' => $group->status,
                        'about' => $group->about,
                        'group_type' => $group->group_type,
                        'logo' => get_group_logos($group->logo, "logo"),
                        'coverPhoto' => get_group_cover_photos($group->banner, "coverphoto"),
                        // 'created_at' => $group->created_at,
                        // 'updated_at' => $group->updated_at,
                        'group_members_count' => $group_members_count,
                        'matching_friends_count' => $matchingFriendsCount,
                        'is_Joined' => $is_Joined,
                        'members' => $response1,
                    ];
                }
                $response = $groupArray;
            }
        }

        return response()->json($response);
    }
    public function groups_details(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $group = Group::where('id', $id)->first();
            // $group = $groups->id;
            if (!$group) {
                $response['success'] = false;
                $response['message'] = 'No groups found';
            } else {
                $groupArray = [];

                // foreach ($groups as $group) {
                $group_members_count = Group_member::where('group_id', $group->id)->count();
                $group_members = Group_member::where('group_id', $group->id)->get();
                $matchingFriendsCount = 0; // Initialize outside the loop
                $is_Joined = 0; // Initialize outside the loop
                $response1 = [];

                if ($group_members->count() > 0) {
                    foreach ($group_members as $member) {
                        $user = User::where('id', $user_id)->first();
                        $user1 = User::where('id', $member->user_id)->first();
                        $friendsList = json_decode($user->friends, true);
                        // $countfriends = count($friendsList);

                        // Count matching friends
                        // $matchingFriendsCount = count(array_intersect($friendsList, $group_members->pluck('user_id')->toArray()));
                        foreach ($friendsList as $friendId) {
                            if ($friendId == $member->user_id) {
                                $matchingFriendsCount++;
                            }
                        }
                        // foreach ($user_id as $userId) {
                        if ($user_id == $member->user_id) {
                            $is_Joined++;
                        }
                        // }

                        $response1[] = [
                            'id' => $member->id,
                            'user_id' => $member->user_id,
                            'group_id' => $member->group_id,
                            'name' => $user1->name,
                            'photo' => get_user_images($user1->id),
                            // 'countfriends' => $countfriends,
                        ];
                    }
                }

                $groupArray = [
                    'id' => $group->id,
                    'user_id' => $group->user_id,
                    'title' => $group->title,
                    'privacy' => $group->privacy,
                    'subtitle' => $group->subtitle != null ? $group->subtitle : "",
                    'location' => $group->location != null ? $group->location : "",
                    'status' => $group->status != null ? $group->status : "",
                    'about' => $group->about != null ? $group->about : "",
                    'group_type' => $group->group_type != null ? $group->group_type : "",
                    'logo' => get_group_logos($group->logo, "logo"),
                    'coverPhoto' => get_group_cover_photos($group->banner, "coverphoto"),
                    // 'created_at' => $group->created_at,
                    // 'updated_at' => $group->updated_at,
                    'group_members_count' => $group_members_count,
                    'matching_friends_count' => $matchingFriendsCount,
                    'is_Joined' => $is_Joined,
                    'members' => $response1,
                ];
                // }
                $response = $groupArray;
            }
        }

        return response()->json($response);
    }

    public function create_group(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {

            $user_id = auth('sanctum')->user()->id;
            $rules = array(
                'image' => 'mimes:jpeg,jpg,png,gif|nullable',
                'name' => 'required|max:255',
                'privacy' => 'required|max:255',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }

            if ($request->image && !empty($request->image)) {
                $file_name = FileUploader::upload($request->image, 'public/storage/groups/logo', 300);
            }

            $group = new Group();
            $group->user_id = $user_id;
            $group->title = $request->name;
            $group->subtitle = $request->subtitle;
            $group->about = $request->about;
            $group->privacy = $request->privacy;
            $group->status = $request->status;
            if ($request->image && !empty($request->image)) {
                $group->logo = $file_name;
            }
            $done = $group->save();
            if ($done) {
                $group_member = new Group_member();
                $group_member->group_id = $group->id;
                $group_member->user_id = $user_id;
                $group_member->role = 'admin';
                $group_member->is_accepted = '1';
                $done = $group_member->save();
                if ($done) {
                    $response['success'] = true;
                    $response['message'] = 'group create successfully';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Failed to group page';
                }
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return response()->json($response);

    }
    public function update_group(Request $request, $group_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {

            $user_id = auth('sanctum')->user()->id;
            $rules = array(
                // 'image' => 'mimes:jpeg,jpg,png,gif|nullable',
                // 'name' => 'required|max:255',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }

            $group = Group::find($group_id);
            //previous image name
            $imagename = $group->logo;
            if ($request->image && !empty($request->image)) {
                $file_name = FileUploader::upload($request->image, 'public/storage/groups/logo', 300);
            }
            // $group->user_id = auth()->user()->id;
            $group->title = $request->name;
            $group->subtitle = $request->subtitle;
            $group->about = $request->about;
            $group->privacy = $request->privacy;
            $group->status = $request->status;
            $group->location = $request->location;
            $group->group_type = $request->group_type;

            if ($request->image && !empty($request->image)) {
                $group->logo = $file_name;
            }
            $done = $group->save();
            if ($done) {
                // just put the file name and folder name nothing more :) 
                if (!empty($request->image)) {
                    if (File::exists(public_path('storage/groups/logo/' . $imagename))) {
                        File::delete(public_path('storage/groups/logo/' . $imagename));
                    }
                }
                $response['success'] = true;
                $response['message'] = 'group update successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to group page';
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function updatecoverphoto_group(Request $request, $group_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $group = Group::find($group_id);
            $imagename = $group->coverphoto;

            if ($request->cover_photo && !empty($request->cover_photo)) {
                //Upload image
                $file_name = rand(1, 35000) . '.' . $request->cover_photo->getClientOriginalExtension();
                //logo
                $img = Image::make($request->cover_photo);
                $img->resize(1120, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save(uploadTo('groups/coverphoto') . $file_name);
                $group->banner = $file_name;
            }
            $done = $group->save();
            if ($done) {
                // just put the file name and folder name nothing more :) 
                if (!empty($request->cover_photo)) {
                    if (File::exists(public_path('storage/groups/coverphoto/' . $imagename))) {
                        File::delete(public_path('storage/groups/coverphoto/' . $imagename));
                    }
                }
                $response['success'] = true;
                $response['message'] = 'coverphoto update successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to group page';
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function groups_join(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            try {
                // Get authenticated user ID
                $user_id = auth('sanctum')->user()->id;

                // Create a new group member
                $group_member = new Group_member();
                $group_member->group_id = $id;
                $group_member->user_id = $user_id;
                $group_member->role = 'general';
                $group_member->is_accepted = '1';

                $member = Group_member::where('user_id', $user_id)->where('group_id', $id)->first();
                if ($member) {
                    Group_member::where('user_id', $user_id)->where('group_id', $id)->delete();
                    $response['success'] = false;
                    $response['message'] = 'Job delete from wishlist successfully';
                } else {
                    $group_member->save();
                    $response['success'] = true;
                    $response['message'] = 'User joined the group successfully';
                }

            } catch (\Exception $e) {
                // Handle any exceptions
                $response['success'] = false;
                $response['message'] = 'Error joining the group: ' . $e->getMessage();
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Invalid token.';
        }

        return $response;
    }
    public function groups_discussion(Request $request, $group_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $group = Group::where('id', $group_id)->first();

            // $response = array(); // Initialize the response array

            if ($group) {
                // $group_members = Group_member::orderBy('created_at', 'desc')->where('group_id', $group->id)->get();

                // foreach ($group_members as $member) {
                //     $user = User::where('id', $member->user_id)->first();
                //     // $user1 = User::where('id', $member->user_id)->first();

                // $posts_data = array();

                // Fetch posts for the current member
                $member_posts = Posts::orderBy('created_at', 'desc')->where('publisher_id', $group->id)->where('publisher', 'group')->where('privacy', 'public')->get();

                foreach ($member_posts as $post) {
                    $user1 = User::where('id', $post->user_id)->first();

                    // Media section of posts
                    $mediaFiles = Media_files::where('post_id', $post->post_id)->get();

                    // Initialize an array to store post images
                    $postImages = [];
                    $file = 'text';

                    // Loop through the media files and add them to the postImages array
                    foreach ($mediaFiles as $media) {

                        if ($media->file_type == "image") {
                            $postImages[] = get_post_images($media->file_name);
                        } else {
                            $postImages[] = get_post_videos($media->file_name);
                        }
                        $file = $media->file_type;

                    }

                    // comment count 
                    $commentsCount = Comments::where('id_of_type', $post->post_id)->count();

                    // user react of each posts
                    $userReacts = json_decode($post->user_reacts, true);

                    // Initialize counters
                    $likeCount = 0;
                    $loveCount = 0;
                    $sadCount = 0;
                    $hahaCount = 0;
                    $angryCount = 0;

                    // Count occurrences of reactions
                    foreach ($userReacts as $react) {
                        switch ($react) {
                            case 'like':
                                $likeCount++;
                                break;
                            case 'love':
                                $loveCount++;
                                break;
                            case 'sad':
                                $sadCount++;
                                break;
                            case 'haha':
                                $hahaCount++;
                                break;
                            case 'angry':
                                $angryCount++;
                                break;
                            default:
                                // Do nothing or handle unexpected reactions
                                break;
                        }
                    }
                    // Calculate the total reactions
                    $totalReacts = $likeCount + $loveCount + $sadCount + $hahaCount + $angryCount;

                    // $formattedDate = date('M d \a\t H:i A', strtotime($post->created_at));
                    $createdDate = Carbon::createFromTimestamp(strtotime($post->posted_on));
                    $daysDifference = $createdDate->diffInDays(Carbon::now());
                    if ($daysDifference < 7) {
                        // Show "time ago" format
                        $formattedDate = $createdDate->diffForHumans();
                    } else {
                        // Show the exact date and time
                        $formattedDate = $createdDate->toDayDateTimeString();
                        // Example format: 'Mon, Jan 1, 2024 12:00 AM'
                    }

                    $followers = Follower::where('user_id', $user_id)->get();
                    $follow = 'Follow';
                    foreach ($followers as $follo) {
                        if ($follo->follow_id == $post->user_id) {
                            $follow = 'Unfollow';
                        }
                    }

                    $taggedUserIds = json_decode($post->tagged_user_ids, true);
                    $taggedUsers = User::whereIn('id', $taggedUserIds)->get(['id', 'name']);
                    $taggedUserList = [];
                    foreach ($taggedUsers as $tags) {
                        $taggedUserList[] = [
                            'id' => $tags->id,
                            'name' => $tags->name
                        ];
                    }

                    $response[] = [
                        'id' => $post->post_id,
                        'user_id' => $post->user_id,
                        'name' => $user1->name,
                        'photo' => get_user_images($user1->id),
                        'publisher' => $post->publisher,
                        'location' => $post->location != null ? $post->location : "",
                        'postType' => $post->post_type,
                        'fileType' => $file,
                        'publisher_id' => $post->publisher_id,
                        'privacy' => $post->privacy,
                        'post_images' => $postImages,
                        'thumbnail' => $post->mobile_app_image != null ? get_post_images($post->mobile_app_image) : "",
                        'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,
                        'description' => $post->description,
                        'commentsCount' => $commentsCount,
                        'reaction_counts' => [
                            'like' => $likeCount,
                            'love' => $loveCount,
                            'sad' => $sadCount,
                            'haha' => $hahaCount,
                            'angry' => $angryCount,
                            'total' => $totalReacts // Include total reactions count
                        ],
                        'created_at' => $formattedDate,
                        'follow' => $follow,
                        'taggedUserList' => $taggedUserList,

                        // Add other fields as needed
                    ];
                }

            } else {
                $response['success'] = false;
                $response['message'] = 'No group found';
            }
        }

        return response()->json($response);
    }
    public function groups_people(Request $request, $group_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $group = Group::where('id', $group_id)->first();

            $groupArray = [];


            $group_members_count = Group_member::where('group_id', $group->id)->count();
            $group_members = Group_member::where('group_id', $group->id)->get();
            $matchingFriendsCount = 0; // Initialize outside the loop
            $is_Joined = 0; // Initialize outside the loop
            $response1 = [];

            if ($group_members->count() > 0) {
                foreach ($group_members as $member) {
                    $user1 = User::where('id', $member->user_id)->first();

                    $user = User::where('id', $user_id)->first();
                    $friendsList = json_decode($user->friends, true);
                    $countfriends = count($friendsList);

                    $isFriend = in_array($member->user_id, $friendsList) ? 1 : 0;

                    $requested = "Add Friend";
                    $req = Friendships::whereIn('accepter', [$user_id, $member->user_id])
                        ->WhereIn('requester', [$user_id, $member->user_id])
                        ->get();
                    foreach ($req as $chat) {
                        if ($chat->accepter == $user_id || $chat->requester == $user_id) {
                            // Set the profile ID to the matching user ID
                            $is_chat = "chat";
                            $frnd_id = $chat->id;
                            $requested = $chat->is_accepted == 0 ? ($chat->requester == $user_id ? 'Requested' : "Confirm") : "Friend";


                            // Break the loop once a match is found
                            // break;
                        }
                    }


                    // $mutualFriendsCount = $this->countMutualFriends($friendsList, $member->user_id);

                    // Count mutual friends
                    $memberFriendsList = json_decode($user1->friends, true);

                    $mutualFriendsCount = count(array_intersect($friendsList, $memberFriendsList));
                    // Count matching friends
                    // $matchingFriendsCount = count(array_intersect($friendsList, $group_members->pluck('user_id')->toArray()));
                    foreach ($friendsList as $friendId) {
                        if ($friendId == $member->user_id) {
                            $matchingFriendsCount++;
                        }
                    }


                    // foreach ($user_id as $userId) {
                    if ($user_id == $member->user_id) {
                        $is_Joined++;
                    }

                    // }

                    $response1[] = [
                        'id' => $member->id,
                        'user_id' => $member->user_id,
                        'group_id' => $member->group_id,
                        'name' => $user1->name,
                        'isFriend' => $requested,
                        'mutualFriendsCount' => $mutualFriendsCount,
                        'photo' => get_user_images($user1->id),

                    ];
                }
            }

            $groupArray = [
                'id' => $group->id,
                'user_id' => $group->user_id,
                // 'title' => $group->title,
                // 'privacy' => $group->privacy,
                // 'group_type' => $group->group_type,
                // 'logo' => get_group_logos($group->logo, "logo"),
                // 'coverPhoto' => get_group_cover_photos($group->banner, "coverphoto"),
                // 'status' => $group->status,
                'countfriends' => $countfriends,
                // 'created_at' => $group->created_at,
                // 'updated_at' => $group->updated_at,
                'group_members_count' => $group_members_count,
                'matching_friends_count' => $matchingFriendsCount,
                'is_Joined' => $is_Joined,
                'members' => $response1,
            ];

            $response = $groupArray;

        }

        return response()->json($response);
    }
    public function groups_event(Request $request, $group_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $group = Group::where('id', $group_id)->first();

            $group_event = Event::where('group_id', $group_id)->get();
            $response1 = [];

            if ($group_event->count() > 0) {
                foreach ($group_event as $event) {
                    $user = User::where('id', $event->user_id)->first();
                    $going_user_id = json_decode($event->going_users_id, true);
                    $interested_user_id = json_decode($event->interested_users_id, true);
                    $response1[] = [
                        'id' => $event->id,
                        'user_id' => $event->user_id,
                        'my_event' => $event->user_id == $user_id ? "my_event" : "not_my_event",
                        // 'group_id' = $event->group_id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'event_date' => date_create($event->event_date)->format('l,j F Y'),
                        'date' => $event->event_date,
                        'event_time' => $event->event_time,
                        'location' => $event->location != null ? $event->location : "",
                        'privacy' => $event->privacy,
                        'banner' => get_group_event_photos($event->banner, "coverphoto", "event"),


                        'going_user_id' => count($going_user_id),
                        'going' => in_array($user_id, $going_user_id) ? "going" : "not_going",


                        'interested_user_id' => count($interested_user_id),
                        'interest' => in_array($user_id, $interested_user_id) ? "interested" : "not_interested",


                        'user_name' => $user->name,
                        'user_photo' => get_user_images($user->id),
                        // 'id' => $event->id,
                        // 'user_id' => $event->user_id,
                        // 'group_id' => $event->group_id,
                        // 'title' => $event->title,
                        // 'description' => $event->description,
                        // 'event_date' => $event->event_date,
                        // 'event_time' => $event->event_time,
                        // 'banner' => get_group_event_photos($event->banner, "coverphoto", "event"),
                        // 'privacy' => $event->privacy,
                        // 'name' => $user->name,
                        // 'photo' => get_user_images($user->id),
                    ];
                }
                $response = $response1;
            }
        }
        return response()->json($response);
    }
    public function group_photos(Request $request, $group_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $all_photos = Media_files::where('group_id', $group_id)->where('file_type', 'image')->orderBy('id', 'DESC')->get();
            $photoArray = [];
            foreach ($all_photos as $photo) {
                $photoArray[] = [
                    'post_id' => $photo->post_id,
                    'photo' => get_post_images($photo->file_name),
                ];
            }
            $page_data['all_photos'] = $photoArray;
            // $page_data['all_photos'] = $all_photos;

            $all_videos = Media_files::where('group_id', $group_id)->where('file_type', 'video')->orderBy('id', 'DESC')->get();
            $videoArray = [];
            foreach ($all_videos as $video) {
                $videoArray[] = [
                    'post_id' => $video->post_id,
                    'video' => get_post_videos($video->file_name),
                ];
            }
            $page_data['all_videos'] = $videoArray;

            $all_albums = Albums::where('group_id', $group_id)->orderBy('id', 'DESC')->get();
            $albumArray = [];
            foreach ($all_albums as $album) {
                $albumArray[] = [
                    'id' => $album->id,
                    'user_id' => $album->user_id,
                    'title' => $album->title,
                    'sub_title' => $album->sub_title,
                    'thumbnail' => get_group_event_photos($album->thumbnail, 'album', 'thumbnails'),
                ];
            }
            $page_data['all_albums'] = $albumArray;
            $response = $page_data;
        }
        return response()->json($response);
    }

    public function pages(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $pages = Page::orderBy('id', 'desc')->get();

            if ($pages->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No Pages found';
            } else {
                $pageArray = [];

                foreach ($pages as $page) {
                    $category = Pagecategory::where('id', $page->category_id)->first();
                    $like_count = Page_like::where('page_id', $page->id)->count();
                    $is_Liked = Page_like::where('page_id', $page->id)->where('user_id', $user_id)->first();

                    $pageArray[] = [
                        'id' => $page->id,
                        'user_id' => $page->user_id,
                        'title' => $page->title,
                        'category_id' => $page->category_id,
                        'category' => $category->name,
                        'like_count' => $like_count,
                        'is_Liked' => $is_Liked ? 'Liked' : 'Suggested',
                        'my_page' => $page->user_id == $user_id ? "my_page" : "not_my_page",
                        'owner' => $page->user_id == $user_id ? "me" : "others",
                        'description' => $page->description,
                        'job' => $page->job,
                        'lifestyle' => $page->lifestyle,
                        'location' => $page->location != null ? $page->location : "",
                        'logo' => get_group_event_photos($page->logo, "logo", "pages"),
                        'coverPhoto' => get_group_event_photos($page->coverphoto, "coverphoto", "pages"),

                        // 'created_at' => $group->created_at,
                        // 'updated_at' => $group->updated_at,
                    ];
                }
                $response = $pageArray;
            }
        }

        return response()->json($response);
    }

    public function pages_details(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $page = Page::where('id', $id)->first();

            if (!$page) {
                $response['success'] = false;
                $response['message'] = 'No Pages found';
            } else {
                $pageArray = [];

                // foreach ($pages as $page) {
                $category = Pagecategory::where('id', $page->category_id)->first();
                $like_count = Page_like::where('page_id', $page->id)->count();
                $is_Liked = Page_like::where('page_id', $page->id)->where('user_id', $user_id)->first();

                $pageArray = [
                    'id' => $page->id,
                    'user_id' => $page->user_id,
                    'title' => $page->title,
                    'category_id' => $page->category_id,
                    'category' => $category->name,
                    'like_count' => $like_count,
                    'is_Liked' => $is_Liked ? 'Liked' : 'Suggested',
                    'my_page' => $page->user_id == $user_id ? "my_page" : "not_my_page",
                    'owner' => $page->user_id == $user_id ? "me" : "others",
                    'description' => $page->description,
                    'job' => $page->job,
                    'lifestyle' => $page->lifestyle,
                    'location' => $page->location != null ? $page->location : "",
                    'logo' => get_group_event_photos($page->logo, "logo", "pages"),
                    'coverPhoto' => get_group_event_photos($page->coverphoto, "coverphoto", "pages"),

                    // 'created_at' => $group->created_at,
                    // 'updated_at' => $group->updated_at,
                ];
                // }
                $response = $pageArray;
            }
        }

        return response()->json($response);
    }
    public function pages_update(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {

            $user_id = auth('sanctum')->user()->id;

            $rules = array(
                // 'image' => 'mimes:jpeg,jpg,png,gif|nullable',
                // 'name' => 'required|max:255',
                // 'category' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }

            $page = Page::find($id);
            //previous image name
            $imagename = $page->logo;
            if ($request->image && !empty($request->image)) {
                $file_name = FileUploader::upload($request->image, 'public/storage/pages/logo', 250);
            }


            $page->user_id = $user_id;
            $page->title = $request->name;
            $page->job = $request->job;
            $page->lifestyle = $request->lifestyle;
            $page->location = $request->location;
            $page->category_id = $request->category;
            $page->description = $request->description;
            if ($request->image && !empty($request->image)) {
                $page->logo = $file_name;
            }
            $done = $page->save();
            if ($done) {
                // just put the file name and folder name nothing more :) 
                if (!empty($request->image)) {
                    if (File::exists(public_path('storage/pages/logo/' . $imagename))) {
                        File::delete(public_path('storage/pages/logo/' . $imagename));
                    }
                }
                $response['success'] = true;
                $response['message'] = 'Page update successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to page update';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';

            // return json_encode(array('reload' => 1));
        }
        return response()->json($response);
    }

    public function page_delete(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            // Check if the page exists
            $page = Page::find($id);
            if (!$page) {
                $response['success'] = false;
                $response['message'] = 'Page not found';
                return response()->json($response);
            }

            // Attempt to delete the page likes associated with the page
            $pagelike_remove = Page_like::where('page_id', $id)->delete();

            // Determine if there were associated likes and delete the page accordingly
            if ($pagelike_remove !== false) {
                $page_delete = $page->delete();

                // Check if the page was deleted successfully
                if ($page_delete) {
                    // Delete associated files (cover photo and logo) only if page deletion is successful
                    $imagename = $page->coverphoto;
                    $logoname = $page->logo;

                    if ($imagename && File::exists(public_path('storage/pages/coverphoto/' . $imagename))) {
                        File::delete(public_path('storage/pages/coverphoto/' . $imagename));
                    }

                    if ($logoname && File::exists(public_path('storage/pages/logo/' . $logoname))) {
                        File::delete(public_path('storage/pages/logo/' . $logoname));
                    }

                    $response['success'] = true;
                    $response['message'] = 'Page and associated likes deleted successfully';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Failed to delete the page';
                }
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to delete associated page likes';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return response()->json($response);
    }

    public function page_like(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {

            $user_id = auth('sanctum')->user()->id;
            $pagelike = new Page_like();
            $pagelike->page_id = $id;
            $pagelike->user_id = $user_id;
            $pagelike->role = 'general';

            $member = Page_like::where('user_id', $user_id)->where('page_id', $id)->first();
            if ($member) {

                Page_like::where('user_id', $user_id)->where('page_id', $id)->delete();

                $response['success'] = false;
                $response['message'] = 'Page dislike successfully';
            } else {
                $pagelike->save();
                $response['success'] = true;
                $response['message'] = 'User like the Page';
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        // return json_encode($response);
        return response()->json($response);
    }
    // public function page_dislike(Request $request, $id)
    // {
    //     $token = $request->bearerToken();
    //     $response = array();

    //     if (isset($token) && $token != '') {
    //         $pagelike_remove = Page_like::where('page_id', $id)->delete();
    //         if ($pagelike_remove) {
    //             $response['success'] = true;
    //             $response['message'] = 'Page disliked successfully';
    //         } else {
    //             $response['success'] = false;
    //             $response['message'] = 'Failed to dislike the page';
    //         }

    //     } else {
    //         $response['success'] = false;
    //         $response['message'] = 'Unauthorized access';
    //     }
    //     return response()->json($response);
    // }
    public function pages_create(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {

            $user_id = auth('sanctum')->user()->id;

            $rules = array(
                'image' => 'nullable',
                'name' => 'required|max:255',
                'category' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }

            if ($request->image && !empty($request->image)) {

                $file_name = FileUploader::upload($request->image, 'public/storage/pages/logo', 250);

            }

            $page = new Page();
            $page->user_id = $user_id;
            $page->title = $request->name;
            $page->category_id = $request->category;
            $page->description = $request->description;
            if ($request->image && !empty($request->image)) {
                $page->logo = $file_name;
            }
            $done = $page->save();
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'Page create successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to create page';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return response()->json($response);

    }
    public function update_page_coverphoto(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {

            $page = Page::find($id);
            $imagename = $page->coverphoto;

            if ($request->hasFile('cover_photo')) {
                $file_name = FileUploader::upload($request->file('cover_photo'), 'public/storage/pages/coverphoto', 1120);
                $page->coverphoto = $file_name;
            }

            $done = $page->save();
            if ($done) {
                // Remove the old cover photo if a new one was uploaded
                if (!empty($request->cover_photo) && $request->hasFile('cover_photo')) {
                    if (File::exists(public_path('storage/pages/coverphoto/' . $imagename))) {
                        File::delete(public_path('storage/pages/coverphoto/' . $imagename));
                    }
                }
                $response['success'] = true;
                $response['message'] = 'Page cover photo upload successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to page cover photo upload';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        // return json_encode(array('reload' => 1));
        return response()->json($response);

    }
    public function page_category(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $blogs = PageCategory::orderBy('id', 'desc')->get();

            if ($blogs->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No category found';
            } else {
                $blogArray = [];

                foreach ($blogs as $blog) {

                    $blogArray[] = [

                        'category' => $blog->name,
                        'category_id' => $blog->id,


                    ];
                }
                $response = $blogArray;
            }
        }

        return response()->json($response);
    }

    public function pages_timeline(Request $request, $page_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $page = Page::where('id', $page_id)->first();

            $response = array(); // Initialize the response array

            if ($page) {

                // Fetch posts for the current member
                $page_posts = Posts::orderBy('created_at', 'desc')->where('publisher_id', $page->id)->where('publisher', 'page')->where('privacy', 'public')->get();

                foreach ($page_posts as $post) {
                    $user1 = User::where('id', $post->user_id)->first();

                    // Media section of posts
                    $mediaFiles = Media_files::where('post_id', $post->post_id)->get();

                    // Initialize an array to store post images
                    $postImages = [];
                    $file = 'text';

                    // Loop through the media files and add them to the postImages array
                    foreach ($mediaFiles as $media) {

                        if ($media->file_type == "image") {
                            $postImages[] = get_post_images($media->file_name);
                        } else {
                            $postImages[] = get_post_videos($media->file_name);
                        }
                        $file = $media->file_type;
                    }

                    // comment count 
                    $commentsCount = Comments::where('id_of_type', $post->post_id)->count();

                    // user react of each posts
                    $userReacts = json_decode($post->user_reacts, true);

                    // Initialize counters
                    $likeCount = 0;
                    $loveCount = 0;
                    $sadCount = 0;
                    $hahaCount = 0;
                    $angryCount = 0;

                    // Count occurrences of reactions
                    foreach ($userReacts as $react) {
                        switch ($react) {
                            case 'like':
                                $likeCount++;
                                break;
                            case 'love':
                                $loveCount++;
                                break;
                            case 'sad':
                                $sadCount++;
                                break;
                            case 'haha':
                                $hahaCount++;
                                break;
                            case 'angry':
                                $angryCount++;
                                break;
                            default:
                                // Do nothing or handle unexpected reactions
                                break;
                        }
                    }
                    // Calculate the total reactions
                    $totalReacts = $likeCount + $loveCount + $sadCount + $hahaCount + $angryCount;

                    // $formattedDate = date('M d \a\t H:i A', strtotime($post->created_at));
                    $createdDate = Carbon::createFromTimestamp(strtotime($post->posted_on));
                    $daysDifference = $createdDate->diffInDays(Carbon::now());
                    if ($daysDifference < 7) {
                        // Show "time ago" format
                        $formattedDate = $createdDate->diffForHumans();
                    } else {
                        // Show the exact date and time
                        $formattedDate = $createdDate->toDayDateTimeString();
                        // Example format: 'Mon, Jan 1, 2024 12:00 AM'
                    }
                    $followers = Follower::where('user_id', $user_id)->get();
                    $follow = 'Follow';
                    foreach ($followers as $follo) {
                        if ($follo->follow_id == $post->user_id) {
                            $follow = 'Unfollow';
                        }
                    }

                    $taggedUserIds = json_decode($post->tagged_user_ids, true);
                    $taggedUsers = User::whereIn('id', $taggedUserIds)->get(['id', 'name']);
                    $taggedUserList = [];
                    foreach ($taggedUsers as $tags) {
                        $taggedUserList[] = [
                            'id' => $tags->id,
                            'name' => $tags->name
                        ];
                    }

                    $response[] = [
                        'id' => $post->post_id,
                        'user_id' => $post->user_id,
                        'publisher_id' => $post->publisher_id,
                        'name' => $user1->name,
                        'photo' => get_user_images($user1->id),
                        'publisher' => $post->publisher,
                        'location' => $post->location != null ? $post->location : "",
                        'privacy' => $post->privacy,
                        'postType' => $post->post_type,
                        'fileType' => $file,
                        'post_images' => $postImages,
                        'thumbnail' => $post->mobile_app_image != null ? get_post_images($post->mobile_app_image) : "",
                        'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,
                        'description' => $post->description,
                        'commentsCount' => $commentsCount,
                        'reaction_counts' => [
                            'like' => $likeCount,
                            'love' => $loveCount,
                            'sad' => $sadCount,
                            'haha' => $hahaCount,
                            'angry' => $angryCount,
                            'total' => $totalReacts // Include total reactions count
                        ],
                        'created_at' => $formattedDate,
                        'follow' => $follow,
                        'taggedUserList' => $taggedUserList,

                        // Add other fields as needed
                    ];
                }


                // }
            } else {
                $response['success'] = false;
                $response['message'] = 'No page found';
            }
        }

        return response()->json($response);
    }
    public function page_photos(Request $request, $id)
    {

        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $all_photos = Media_files::where('page_id', $id)
                ->where('file_type', 'image')
                ->orderBy('id', 'DESC')->get();
            $photoArray = [];
            foreach ($all_photos as $photo) {
                $photoArray[] = [
                    'post_id' => $photo->post_id,
                    'photo' => get_post_images($photo->file_name),
                ];
            }
            $page_data['all_photos'] = $photoArray;

            $all_albums = Albums::where('page_id', $id)
                ->orderBy('id', 'DESC')->get();
            $albumArray = [];
            foreach ($all_albums as $album) {
                $albumArray[] = [
                    'id' => $album->id,
                    'user_id' => $album->user_id,
                    'title' => $album->title,
                    'sub_title' => $album->sub_title,
                    'thumbnail' => get_group_event_photos($album->thumbnail, 'album', 'thumbnails'),
                ];
            }
            $page_data['all_albums'] = $albumArray;

            $all_videos = Media_files::where('page_id', $id)
                ->where('file_type', 'video')
                ->orderBy('id', 'DESC')->get();
            $videoArray = [];
            foreach ($all_videos as $video) {
                $videoArray[] = [
                    'post_id' => $video->post_id,
                    'video' => get_post_videos($video->file_name),
                ];
            }
            $page_data['all_videos'] = $videoArray;

            $response = $page_data;
        }
        return $response;
    }

    function create_album(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            // $rules = array('title' => 'required|max:255', 'privacy' => 'required', 'thumbnail' => 'image|nullable');
            // $validator = Validator::make($request->all(), $rules);
            // // Validate the input and return correct response
            // if ($validator->fails()) {
            //     return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            // }

            $data['user_id'] = $user_id;
            $data['title'] = $request->title;
            $data['sub_title'] = $request->sub_title;
            $data['privacy'] = $request->privacy;
            if (isset($request->page_id) && !empty($request->page_id)) {
                $data['page_id'] = $request->page_id;
            }
            if (isset($request->group_id) && !empty($request->group_id)) {
                $data['group_id'] = $request->group_id;
            }
            $data['created_at'] = time();
            $data['updated_at'] = $data['created_at'];


            if ($request->thumbnail) {
                $file_name = FileUploader::upload($request->thumbnail, 'public/storage/thumbnails/album', 800);

                $data['thumbnail'] = $file_name;
            }
            $done = Albums::insertGetId($data);
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'Album created successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to create Marketplace';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;

    }

    public function delete_album(Request $request, $id)
    {

        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $done = Album_image::where('album_id', $id)
                ->delete();
            $done = DB::table('albums')->where('id', $id)->delete();
            $done = DB::table('media_files')->where('album_id', $id)->delete();
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'Album deleted successfully';
            } else {
                $response['success'] = true;
                $response['message'] = 'Album Not available';
            }


        }
        return $response;
    }
    public function album_photos(Request $request, $id)
    {

        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $all_photos = Album_image::where('album_id', $id)

                ->orderBy('id', 'DESC')->get();
            $photoArray = [];
            foreach ($all_photos as $photo) {
                $photoArray[] = [
                    'post_id' => $photo->id,
                    'photo' => get_group_event_photos($photo->image, 'images', 'album'),
                ];
            }
            $page_data['all_photos'] = $photoArray;

            $response = $page_data;
        }
        return $response;
    }
    public function add_album_image(Request $request)
    {

        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            if (is_array($request->images) && $request->images[0] != null) {
                //Data validation
                $rules = array('multiple_files' => 'mimes:jpeg,jpg,png,gif');
                $validator = Validator::make($request->images, $rules);
                if ($validator->fails()) {
                    return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
                }
                foreach ($request->images as $key => $media_file) {
                    $file_name = FileUploader::upload($media_file, 'public/storage/album/images', 1000, null, 300);
                    $file_type = 'image';

                    $albumimage = new Album_image();
                    $albumimage->user_id = $user_id;
                    $albumimage->album_id = $request->album;
                    $albumimage->image = $file_name;
                    if (isset($request->page_id) && !empty($request->page_id)) {
                        $albumimage->page_id = $request->page_id;
                    } elseif (isset($request->group_id) && !empty($request->group_id)) {
                        $albumimage->group_id = $request->group_id;
                    } else {

                    }
                    $done = $albumimage->save();

                    if (isset($request->profile_id) && !empty($request->profile_id)) {
                        $data['publisher_id'] = $user_id;
                        $data['user_id'] = $user_id;
                        $data['publisher'] = 'post';
                        $data['post_type'] = 'general';
                        $data['privacy'] = 'public';
                        $data['privacy'] = 'public';
                        $data['status'] = 'active';
                        $data['tagged_user_ids'] = json_encode(array());
                        $data['user_reacts'] = json_encode(array());
                        $data['shared_user'] = json_encode(array());
                        $data['created_at'] = time();
                        $data['updated_at'] = $data['created_at'];

                        $post_id = Posts::insertGetId($data);
                        foreach ($request->images as $key => $media_file) {
                            $file_extention = strtolower($media_file->getClientOriginalExtension());
                            if ($file_extention == 'avi' || $file_extention == 'mp4' || $file_extention == 'webm' || $file_extention == 'mov' || $file_extention == 'wmv' || $file_extention == 'mkv') {
                                $file_name = FileUploader::upload($media_file, 'public/storage/post/videos');
                                $file_type = 'video';
                            } else {
                                $file_name = FileUploader::upload($media_file, 'public/storage/post/images', 1000, null, 300);
                                $file_type = 'image';
                            }


                            $media_file_data = array('user_id' => $user_id, 'post_id' => $post_id, 'album_id' => $request->album, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => $request->privacy);
                            $media_file_data['created_at'] = time();
                            $media_file_data['updated_at'] = $media_file_data['created_at'];
                            $done = Media_files::create($media_file_data);
                        }
                    }



                }
                if ($done) {

                    $response['success'] = true;
                    $response['message'] = 'Add photo successfully';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Failed to add photo';
                }
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function marketplace(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $marketplace = Marketplace::orderBy('id', 'Desc')->get();

            if ($marketplace->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No marketplace found';
            } else {
                $pageArray = [];

                foreach ($marketplace as $page) {
                    $user = User::where('id', $page->user_id)->first();
                    $category = Category::where('id', $page->category)->first();
                    $brand = Brand::where('id', $page->brand)->first();
                    $currency = Currency::where('id', $page->currency_id)->first();
                    $is_Saved = SavedProduct::where('product_id', $page->id)->first();

                    $is_chat = 'not_chat'; // Initialize profile ID as 0
                    $msgthread_id = 0; // Initialize profile ID as 0

                    // Get chat messages involving the current user
                    $chats = Message_thrade::where('reciver_id', $user_id)
                        ->orWhere('sender_id', $user_id)
                        ->get();

                    // Loop through chat messages to find matching profile ID
                    foreach ($chats as $chat) {
                        if ($chat->reciver_id == $user_id || $chat->sender_id == $user_id) {
                            // Set the profile ID to the matching user ID
                            $is_chat = "chat";
                            $msgthread_id = $chat->id;
                            // Break the loop once a match is found
                            break;
                        }
                    }

                    $pageArray[] = [
                        'id' => $page->id,
                        'user_id' => $page->user_id,
                        'thrade' => $msgthread_id,
                        'user' => $user->name,
                        'photo' => get_user_images($user->id),
                        'title' => $page->title,
                        'price' => $page->price,
                        'category_id' => $page->category,
                        'status_id' => $page->status,
                        'brand_id' => $page->brand,
                        'currency_id' => $page->currency_id,
                        'condition' => $page->condition,
                        'status' => $page->status,
                        'category' => $category->name,
                        'brand' => $brand->name,
                        'currency' => $currency->name,
                        'is_Saved' => $is_Saved ? 'saved' : 'not_saved',
                        'my_product' => $page->user_id == $user_id ? "my_product" : "not_my_product",
                        'description' => $page->description,
                        'location' => $page->location != null ? $page->location : "",
                        'coverphoto' => get_group_event_photos($page->image, "coverphoto", "marketplace"),
                        // 'coverPhoto' => get_group_event_photos($page->coverphoto, "coverphoto", "pages"),

                        // 'created_at' => date('d F Y', strtotime($page->created_at)),
                        'created_at' => date('d-m-Y', strtotime($page->created_at)),
                        // 'updated_at' => $group->updated_at,
                    ];
                }
                $response = $pageArray;
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return response()->json($response);
    }
    public function create_marketplace(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $rules = array(
                'title' => 'required|max:255',
                'price' => 'required',
                'location' => 'required',
                'category' => 'required',
                'condition' => 'required',
                'status' => 'required',
                'brand' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }

            $marketplace = new Marketplace();
            $marketplace->user_id = $user_id;
            $marketplace->title = $request->title;
            $marketplace->currency_id = $request->currency;
            $marketplace->price = $request->price;
            $marketplace->location = $request->location;
            $marketplace->category = $request->category;
            $marketplace->condition = $request->condition;
            $marketplace->brand = $request->brand;
            $marketplace->buy_link = $request->buy_link;
            $marketplace->status = $request->status;
            $marketplace->description = $request->description;
            $marketplace->save();
            $product_id = $marketplace->id;
            if ($product_id) {
                if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
                    //Data validation
                    $rules = array('multiple_files' => 'mimes:jpeg,jpg,png,gif');
                    $validator = Validator::make($request->multiple_files, $rules);
                    if ($validator->fails()) {
                        return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
                    }

                    foreach ($request->multiple_files as $key => $media_file) {

                        $file_name = FileUploader::upload($media_file, 'public/storage/marketplace/thumbnail', 315);
                        FileUploader::upload($media_file, 'public/storage/marketplace/coverphoto/' . $file_name, 315);

                        $file_type = 'image';

                        $productupdate = Marketplace::find($product_id);
                        $media_file_data = array('user_id' => $user_id, 'product_id' => $product_id, 'file_name' => $file_name, 'file_type' => $file_type);
                        $media_file_data['created_at'] = time();
                        $media_file_data['updated_at'] = $media_file_data['created_at'];
                        Media_files::create($media_file_data);
                        if ($key == '0') {
                            $productupdate = Marketplace::find($product_id);
                            $productupdate->image = $file_name;
                            $productupdate->save();
                        }
                    }
                }

                $response['success'] = true;
                $response['message'] = 'Marketplace created successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to create Marketplace';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        // return json_encode(array('reload' => 1));
        return response()->json($response);
    }
    public function update_marketplace(Request $request, $id)
    {

        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $rules = array(
                // 'title' => 'required|max:255',
                // 'price' => 'required',
                // 'location' => 'required',
                // 'category' => 'required',
                // 'condition' => 'required',
                // 'status' => 'required',
                // 'brand' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }

            $marketplace = Marketplace::find($id);
            $marketplace->user_id = $user_id;
            $marketplace->title = $request->title;
            $marketplace->currency_id = $request->currency;
            $marketplace->price = $request->price;
            $marketplace->location = $request->location;
            $marketplace->category = $request->category;
            $marketplace->condition = $request->condition;
            $marketplace->brand = $request->brand;
            $marketplace->status = $request->status;
            $marketplace->description = $request->description;
            $marketplace->save();
            $product_id = $id;
            if ($product_id) {
                if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
                    //Data validation
                    $rules = array('multiple_files' => 'mimes:jpeg,jpg,png,gif');
                    $validator = Validator::make($request->multiple_files, $rules);
                    if ($validator->fails()) {
                        return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
                    }

                    if (isset($request->multiple_files)) {
                        // this for deleting previous data file 
                        $previousfile = Media_files::where('product_id', $id)->get();
                        foreach ($previousfile as $previousfile) {
                            $market = Media_files::find($previousfile->id);
                            // store image name for delete file operation 
                            $imagename = $market->banner;
                            $done = $market->delete();
                            if ($done) {
                                // just put the file name and folder name nothing more :) 
                                removeFile('marketplace', $imagename);
                            }
                        }
                        // end code sec 
                    }

                    foreach ($request->multiple_files as $key => $media_file) {
                        $file_name = FileUploader::upload($media_file, 'public/storage/marketplace/thumbnail', 315);
                        FileUploader::upload($media_file, 'public/storage/marketplace/coverphoto/' . $file_name, 315);
                        $file_type = 'image';

                        $productupdate = Marketplace::find($product_id);
                        $media_file_data = array('user_id' => $user_id, 'product_id' => $product_id, 'file_name' => $file_name, 'file_type' => $file_type);
                        $media_file_data['created_at'] = time();
                        $media_file_data['updated_at'] = $media_file_data['created_at'];
                        Media_files::create($media_file_data);
                        if ($key == '0') {
                            $productupdate = Marketplace::find($product_id);
                            $productupdate->image = $file_name;
                            $productupdate->save();
                        }
                    }
                }
                $response['success'] = true;
                $response['message'] = 'update successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to update';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        // return json_encode(array('reload' => 1));
        return response()->json($response);
    }
    public function delete_marketplace(Request $request, $product_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            // Find the marketplace product by its ID
            $market = Marketplace::find($product_id);

            if ($market) {
                // Store the image name for deletion
                $imagename = $market->banner;

                // Delete the marketplace product
                $done = $market->delete();

                if ($done) {
                    // Remove the file from storage
                    removeFile('marketplace', $imagename);

                    // Delete all associated media files with the product_id
                    $previousFiles = Media_files::where('product_id', $product_id)->get();
                    foreach ($previousFiles as $file) {
                        // Delete the entry from the media_files table
                        $file->delete();

                        // Remove the file from storage
                        removeFile('marketplace', $file->file_name);
                    }

                    // Prepare the response
                    $response = array(
                        'alertMessage' => get_phrase('Product Deleted Successfully'),
                        'fadeOutElem' => "#product-" . $product_id
                    );
                } else {
                    // If deletion fails
                    $response = array(
                        'alertMessage' => get_phrase('Failed to delete product'),
                        'fadeOutElem' => ''
                    );
                }
            } else {
                // If the product with the given ID is not found
                $response = array(
                    'alertMessage' => get_phrase('Product not found'),
                    'fadeOutElem' => ''
                );
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }
    public function marketplace_brand(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $category = Brand::orderBy('id', 'desc')->get();

            if ($category->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No category found';
            } else {
                $categoriesArray = [];
                foreach ($category as $categories) {
                    $categoriesArray[] = [
                        'category_id' => $categories->id,
                        'category' => $categories->name,
                    ];
                }
                $response = $categoriesArray;
            }
        }

        return response()->json($response);
    }
    public function marketplace_category(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $category = Category::orderBy('id', 'desc')->get();

            if ($category->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No category found';
            } else {
                $categoriesArray = [];

                foreach ($category as $categories) {

                    $categoriesArray[] = [

                        'category_id' => $categories->id,
                        'category' => $categories->name,


                    ];
                }
                $response = $categoriesArray;
            }
        }

        return response()->json($response);
    }
    public function currencies(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $category = Currency::orderBy('id', 'desc')->get();

            if ($category->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No category found';
            } else {
                $categoriesArray = [];

                foreach ($category as $categories) {

                    $categoriesArray[] = [

                        'category_id' => $categories->id,
                        'category' => $categories->name,


                    ];
                }
                $response = $categoriesArray;
            }
        }

        return response()->json($response);
    }

    public function filter(Request $request)
    {

        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $search = $_GET['search'];
            $category = $_GET['category'];
            $condition = $_GET['condition'];
            $min = $_GET['min'];
            $max = $_GET['max'];
            $brand = $_GET['brand'];
            $location = $_GET['location'];


            $query = Marketplace::where('status', 1)->orderBy('id', 'desc');



            if (!empty($search) || !empty($location)) {
                $query->where(function ($query) use ($search, $location) {
                    if (!empty ($search)) {
                        $query->where(function ($query) use ($search) {
                            $query->where('title', 'like', '%' . $search . '%')
                                ->orWhere('description', 'like', '%' . $search . '%');
                        });
                    }
                    if (!empty ($location)) {
                        $query->orWhere('location', 'like', '%' . $location . '%');
                    }
                });
            }
            if (!empty($min) || !empty($max)) {
                $query->where(function ($query) use ($min, $max) {
                    if (!empty ($min)) {
                        $query->where('price', '>=', $min);
                    }
                    if (!empty ($max)) {
                        $query->where('price', '<=', $max);
                    }
                });
            }

            if (isset($condition) && !empty($condition)) {
                $query->where('condition', $condition);
            }

            if (isset($category) && !empty($category)) {
                $query->where('category', $category);
            }

            if (isset($brand) && !empty($brand)) {
                $query->where('brand', $brand);
            }

            $marketplace = $query->get();
            // return $marketplace;
            $pageArray = [];

            foreach ($marketplace as $page) {
                $user = User::where('id', $page->user_id)->first();
                $category = Category::where('id', $page->category)->first();
                $brand = Brand::where('id', $page->brand)->first();
                $currency = Currency::where('id', $page->currency_id)->first();
                $is_Saved = SavedProduct::where('product_id', $page->id)->first();
                $is_chat = 'not_chat'; // Initialize profile ID as 0
                $msgthread_id = 0; // Initialize profile ID as 0

                // Get chat messages involving the current user
                $chats = Message_thrade::where('reciver_id', $user_id)
                    ->orWhere('sender_id', $user_id)
                    ->get();

                // Loop through chat messages to find matching profile ID
                foreach ($chats as $chat) {
                    if ($chat->reciver_id == $user_id || $chat->sender_id == $user_id) {
                        // Set the profile ID to the matching user ID
                        $is_chat = "chat";
                        $msgthread_id = $chat->id;
                        // Break the loop once a match is found
                        break;
                    }
                }
                $pageArray[] = [
                    'id' => $page->id,
                    'thrade' => $msgthread_id,
                    'user_id' => $page->user_id,
                    'user' => $user->name,
                    'photo' => get_user_images($user->id),
                    'title' => $page->title,
                    'price' => $page->price,
                    'category_id' => $page->category,
                    'status_id' => $page->status,
                    'brand_id' => $page->brand,
                    'currency_id' => $page->currency_id,
                    'condition' => $page->condition,
                    'status' => $page->status,
                    'category' => $category->name,
                    'brand' => $brand->name,
                    'currency' => $currency->name,
                    'is_Saved' => $is_Saved ? 'saved' : 'not_saved',
                    'my_product' => $page->user_id == $user_id ? "my_product" : "not_my_product",
                    'description' => $page->description,
                    'location' => $page->location != null ? $page->location : "",
                    'coverphoto' => get_group_event_photos($page->image, "coverphoto", "marketplace"),
                    // 'coverPhoto' => get_group_event_photos($page->coverphoto, "coverphoto", "pages"),

                    // 'created_at' => date('d F Y', strtotime($page->created_at)),
                    'created_at' => date('d-m-Y', strtotime($page->created_at)),
                    // 'updated_at' => $group->updated_at,
                ];
            }
            $response = $pageArray;
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }


    //save for later in marketplace product
    public function save_for_later(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $saveproduct = new SavedProduct();
            $saveproduct->user_id = $user_id;
            $saveproduct->product_id = $id;


            $save = SavedProduct::where('product_id', $id)->first();

            if ($save) {

                SavedProduct::where('product_id', $id)->delete();

                $response['success'] = false;
                $response['message'] = 'product unsave successfully';
            } else {
                $saveproduct->save();
                $response['success'] = true;
                $response['message'] = 'User saved the product';
            }
            // if ($save) {
            //     $response['success'] = true;
            //     $response['message'] = 'save successfully';
            // } else {
            //     $response['success'] = false;
            //     $response['message'] = 'Failed to save';
            // }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        // return json_encode($response);
        return response()->json($response);
    }

    // //delete or remove from save for later in marketplace product
    // public function unsave_for_later(Request $request, $id)
    // {
    //     $token = $request->bearerToken();
    //     $response = array();

    //     if (isset($token) && $token != '') {
    //         $user_id = auth('sanctum')->user()->id;
    //         $done = SavedProduct::where('product_id', $id)->where('user_id', $user_id)->delete();
    //         if ($done) {

    //             $response['success'] = true;
    //             $response['message'] = 'unsave successfully';
    //         } else {
    //             $response['success'] = false;
    //             $response['message'] = 'Failed to unsave';
    //         }
    //     } else {
    //         $response['success'] = false;
    //         $response['message'] = 'Unauthorized access';
    //     }
    //     return response()->json($response);
    // }
    public function videos(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $videos = Video::orderBy('id', 'desc')->take(40)->get();
            foreach ($videos as $key => $video) {
                $response[$key]['id'] = $video->id;

                $response[$key]['user_id'] = $video->user_id;
                $user1 = User::where('id', $video->user_id)->first();
                $response[$key]['name'] = $user1->name;
                $response[$key]['photo'] = get_user_images($user1->id);

                $response[$key]['title'] = $video->title;
                $response[$key]['category'] = $video->category;
                $response[$key]['privacy'] = $video->privacy;
                $response[$key]['file'] = get_one_folder_files($video->file, "videos");
                $response[$key]['thumbnail'] = $video->mobile_app_image != null ? get_one_folder_files($video->mobile_app_image, "videos") : "";


                // Fetch Posts by the User
                $post = Posts::where('publisher_id', $video->id)->first();

                $response[$key]['post_id'] = $post->post_id;
                // $response[$key]['publisher_id'] = $post->publisher_id;
                // $response[$key]['description'] = $post->description;
                $response[$key]['post_type'] = $post->post_type;
                $response[$key]['privacy'] = $post->privacy;
                $userReacts = json_decode($post->user_reacts, true);
                // $response['posts'][$key1]['userReacts'] = $userReacts;
                if (isset($userReacts[$user_id])) {
                    $response[$key]['userReaction'] = $userReacts[$user_id];
                } else {
                    // Handle the case where the user reaction is not set
                    $response[$key]['userReaction'] = null; // Or any default value
                }

                // $userReaction = null;
                // if ($user_id && isset($userReacts[$user_id])) {
                //     $userReaction = $userReacts[$user_id];

                // }
                // $response['posts'][$key1]['userReaction'] = $userReaction;

                // Initialize counters
                $likeCount = 0;
                $loveCount = 0;
                $sadCount = 0;
                $hahaCount = 0;
                $angryCount = 0;

                // Count occurrences of reactions
                foreach ($userReacts as $react) {
                    switch ($react) {
                        case 'like':
                            $likeCount++;
                            break;
                        case 'love':
                            $loveCount++;
                            break;
                        case 'sad':
                            $sadCount++;
                            break;
                        case 'haha':
                            $hahaCount++;
                            break;
                        case 'angry':
                            $angryCount++;
                            break;
                        default:
                            // Do nothing or handle unexpected reactions
                            break;
                    }

                }
                // Calculate the total reactions
                $totalReacts = $likeCount + $loveCount + $sadCount + $hahaCount + $angryCount;
                $response[$key]['reaction_counts'] = [
                    'like' => $likeCount,
                    'love' => $loveCount,
                    'sad' => $sadCount,
                    'haha' => $hahaCount,
                    'angry' => $angryCount,
                    'total' => $totalReacts // Include total reactions count
                ];

                $commentsCount = Comments::where('id_of_type', $post->post_id)->count();
                $response[$key]['comments_count'] = $commentsCount;

                // $response[$key]['created_at'] = date('M d \a\t H:i A', strtotime($post->created_at));
                $createdDate = Carbon::createFromTimestamp(strtotime($post->posted_on));
                $daysDifference = $createdDate->diffInDays(Carbon::now());
                if ($daysDifference < 7) {
                    // Show "time ago" format
                    $response[$key]['created_at']  = $createdDate->diffForHumans();
                } else {
                    // Show the exact date and time
                    $response[$key]['created_at']  = $createdDate->toDayDateTimeString();
                    // Example format: 'Mon, Jan 1, 2024 12:00 AM'
                }

                $followers = Follower::where('user_id', $user_id)->get();
                $response[$key]['follow'] = 'Follow';
                foreach ($followers as $follo) {
                    if ($follo->follow_id == $post->user_id) {
                        $response[$key]['follow'] = 'Unfollow';
                    }
                }

                $taggedUserIds = json_decode($post->tagged_user_ids, true);
                $taggedUsers = User::whereIn('id', $taggedUserIds)->get(['id', 'name']);
                $response[$key]['taggedUserList'] = [];
                foreach ($taggedUsers as $key2 => $tags) {
                    $response[$key]['taggedUserList'][$key2]['id'] = $tags->id;
                    $response[$key]['taggedUserList'][$key2]['name'] = $tags->name;
                }


                // // Media section of posts
                // $media = Media_files::where('post_id', $post->post_id)->first();
                // $response['posts'][$key1]['post_image'] = (!empty($media->file_name)) ? get_post_images($media->file_name) : '';


            }
        }
        return $response;
    }
    public function view_videos(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $video = Video::find($id);
            $video_view_data = json_decode($video->view);

            if (!in_array($user_id, $video_view_data)) {
                array_push($video_view_data, $user_id);
                $video->view = json_encode($video_view_data);
                $video->save();


                $response['success'] = true;
                $response['message'] = 'video view successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to view video upload';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return response()->json($response);
    }
    public function create_videos(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            // $rules = array('video' => 'required|file|mimes:mp4,mov,wmv,mkv,webm,avi,m4v| max:500000');
            // $validator = Validator::make($request->all(), $rules);
            // if ($validator->fails()) {
            //     return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            // }

            $file_name = FileUploader::upload($request->video, 'public/storage/videos');

            $mobile_app_image = FileUploader::upload($request->mobile_app_image, 'public/storage/videos');

            $video = new Video();
            $video->title = $request->title;
            $video->user_id = $user_id;
            $video->privacy = $request->privacy;
            $video->category = $request->category;
            $video->file = $file_name;
            $video->mobile_app_image = $mobile_app_image;
            $video->view = json_encode(array());
            $done = $video->save();
            if ($done) {
                $post = new Posts();
                $post->user_id = $user_id;
                $post->publisher = 'video_and_shorts';
                $post->publisher_id = $video->id;
                $post->post_type = 'general';
                $post->privacy = $video->privacy;
                $post->description = $video->title;
                $post->mobile_app_image = $mobile_app_image;
                $post->tagged_user_ids = json_encode(array());
                $post->user_reacts = json_encode(array());
                $post->status = 'active';
                $post->created_at = time();
                $post->updated_at = time();
                $post->save();

                $response['success'] = true;
                $response['message'] = 'video upload successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to video upload';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return response()->json($response);
    }

    public function save_for_later_videos(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $saveforlater = new Saveforlater();
            $saveforlater->user_id = $user_id;
            $saveforlater->video_id = $id;
            $done = $saveforlater->save();
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'save successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to save';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return response()->json($response);
    }

    public function unsave_for_later_videos(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $done = Saveforlater::where('video_id', $id)->where('user_id', $user_id)->delete();
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'unsave successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to unsave';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return response()->json($response);
    }
    public function delete_videos(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            // $user_id = auth('sanctum')->user()->id;
            // Find the marketplace product by its ID
            $video = Video::find($id);

            if ($video) {
                // Store the image name for deletion
                $file = $video->file;

                // Delete the video
                $done = $video->delete();

                if ($done) {
                    removeFile('video', $file);
                    // Delete all associated media files with the product_id
                    $post = Posts::where('publisher_id', $id)->where('publisher', 'video_and_shorts')->first();

                    // Delete the entry from the post table
                    if ($post)
                        $post->delete();

                    // Prepare the response
                    $response = array(
                        'alertMessage' => get_phrase('Video Deleted Successfully'),
                        'fadeOutElem' => "#Video-" . $id
                    );
                } else {
                    // If deletion fails
                    $response = array(
                        'alertMessage' => get_phrase('Failed to delete Video'),
                        'fadeOutElem' => ''
                    );
                }
            } else {
                // If the product with the given ID is not found
                $response = array(
                    'alertMessage' => get_phrase('Video not found'),
                    'fadeOutElem' => ''
                );
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }
    public function events(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $events = Event::orderBy('id', 'desc')->whereNull("group_id")->get();
            foreach ($events as $key => $event) {
                $response[$key]['id'] = $event->id;
                $response[$key]['user_id'] = $event->user_id;
                $response[$key]['my_event'] = $event->user_id == $user_id ? "my_event" : "not_my_event";
                // $response[$key]['group_id'] = $event->group_id;
                $response[$key]['title'] = $event->title;
                $response[$key]['description'] = $event->description != null ? $event->description : "";
                $response[$key]['event_date'] = date_create($event->event_date)->format('l,j F Y');
                $response[$key]['date'] = $event->event_date;
                $response[$key]['event_time'] = $event->event_time;
                $response[$key]['location'] = $event->location;
                $response[$key]['privacy'] = $event->privacy;
                $response[$key]['banner'] = get_group_event_photos($event->banner, "coverphoto", "event");

                $going_user_id = json_decode($event->going_users_id, true);
                $response[$key]['going_user_id'] = count($going_user_id);
                $response[$key]['going'] = in_array($user_id, $going_user_id) ? "going" : "not_going";

                $interested_user_id = json_decode($event->interested_users_id, true);
                $response[$key]['interested_user_id'] = count($interested_user_id);
                $response[$key]['interest'] = in_array($user_id, $interested_user_id) ? "interested" : "not_interested";

                $user = User::where('id', $event->user_id)->first();
                $response[$key]['user_name'] = $user->name;
                $response[$key]['user_photo'] = get_user_images($user->id);
            }
        }
        return $response;
    }
    public function events_details(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $events = Event::where('id', $id)->whereNull("group_id")->get();
            foreach ($events as $key => $event) {
                $response['id'] = $event->id;
                $response['user_id'] = $event->user_id;
                $response['my_event'] = $event->user_id == $user_id ? "my_event" : "not_my_event";
                // $response['group_id'] = $event->group_id;
                $response['title'] = $event->title;
                $response['description'] = $event->description;
                $response['event_date'] = date_create($event->event_date)->format('l,j F Y');
                $response['date'] = $event->event_date;
                $response['event_time'] = $event->event_time;
                $response['location'] = $event->location;
                $response['privacy'] = $event->privacy;
                $response['banner'] = get_group_event_photos($event->banner, "coverphoto", "event");

                $going_user_id = json_decode($event->going_users_id, true);
                $response['going_user_id'] = count($going_user_id);
                $response['going'] = in_array($user_id, $going_user_id) ? "going" : "not_going";

                $interested_user_id = json_decode($event->interested_users_id, true);
                $response['interested_user_id'] = count($interested_user_id);
                $response['interest'] = in_array($user_id, $interested_user_id) ? "interested" : "not_interested";

                $user = User::where('id', $event->user_id)->first();
                $response['user_name'] = $user->name;
                $response['user_photo'] = get_user_images($user->id);
            }
        }
        return $response;
    }

    // event store
    public function create_event(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {

            $user_id = auth('sanctum')->user()->id;

            $rules = array(
                'coverphoto' => 'mimes:jpeg,jpg,png,gif|nullable',
                'eventname' => 'required|max:255',
                'eventdate' => 'required',
                'eventtime' => 'required',
                'eventlocation' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }
            if ($request->coverphoto && !empty($request->coverphoto)) {

                //Upload image
                $file_name = rand(1, 35000) . '.' . $request->coverphoto->getClientOriginalExtension();

                //thumbnail
                $img = Image::make($request->coverphoto);
                $img->resize(325, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save(uploadTo('event/thumbnail') . $file_name);

                // cover photo 
                $img = Image::make($request->coverphoto);
                $img->resize(1120, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save(uploadTo('event/coverphoto') . $file_name);
            }
            $event = new Event();

            $event->user_id = $user_id;
            $event->title = $request->eventname;
            $event->description = $request->description;
            $event->event_date = $request->eventdate;
            $event->event_time = $request->eventtime;
            $event->location = $request->eventlocation;
            if (isset($request->group_id)) {
                $event->group_id = $request->group_id;
            }
            !empty($request->coverphoto) ? $event->banner = $file_name : "";
            $event->going_users_id = "[]";
            $event->interested_users_id = "[]";
            $event->privacy = $request->privacy;
            $done = $event->save();
            if ($done) {
                $data['user_id'] = $user_id;
                $data['privacy'] = $request->privacy;
                $data['publisher'] = 'event';
                $data['publisher_id'] = $event->id;
                $data['post_type'] = "event";
                $data['status'] = 'active';
                $data['description'] = $request->description;
                $data['user_reacts'] = json_encode(array());
                $data['user_reacts'] = json_encode(array());
                $data['tagged_user_ids'] = json_encode(array());
                $data['created_at'] = time();
                $data['updated_at'] = $data['created_at'];
                Posts::create($data);

                $response['success'] = true;
                $response['message'] = 'event create successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to create event';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        // return json_encode(array('reload' => 1));
        return $response;
    }

    //  update event 
    public function update_event(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $rules = array(
                'coverphoto' => 'mimes:jpeg,jpg,png,gif|nullable',
                'eventname' => 'required|max:255',
                'eventdate' => 'required',
                'eventtime' => 'required',
                'eventlocation' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
            }
            if ($request->coverphoto && !empty($request->coverphoto)) {

                //Upload image
                $file_name = rand(1, 35000) . '.' . $request->coverphoto->getClientOriginalExtension();

                //thumbnail
                $img = Image::make($request->coverphoto);
                $img->resize(325, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save(uploadTo('event/thumbnail') . $file_name);

                // cover photo 
                $img = Image::make($request->coverphoto);
                $img->resize(1120, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save(uploadTo('event/coverphoto') . $file_name);
            }
            $event = Event::find($id);

            $event->user_id = $user_id;
            // store image name for delete file operation 
            $imagename = $event->banner;

            $event->title = $request->eventname;
            $event->description = $request->description;
            $event->event_date = $request->eventdate;
            $event->event_time = $request->eventtime;
            $event->location = $request->eventlocation;
            !empty($request->coverphoto) ? $event->banner = $file_name : $event->banner;
            $event->privacy = $request->privacy;
            $done = $event->save();
            if ($done) {
                // just put the file name and folder name nothing more :) 
                removeFile('event', $imagename);

                $response['success'] = true;
                $response['message'] = 'update successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to update';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    // delete event 
    public function delete_event(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $event = Event::find($id);
            // store image name for delete file operation 
            $imagename = $event->banner;

            $done = $event->delete();
            if ($done) {
                // $response = array('alertMessage' => get_phrase('Event Deleted Successfully'), 'fadeOutElem' => "#event-" . $_GET['event_id']);
                // just put the file name and folder name nothing more :) 

                removeFile('event', $imagename);
                $post = Posts::where('publisher_id', $id)->where('publisher', 'event')->first();

                // Delete the entry from the post table
                if ($post)
                    $post->delete();

                $response['success'] = true;
                $response['message'] = 'delete successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to delete';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    // event going 
    public function event_going(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;



            $going_user_id = $user_id;
            $event_id = $id;
            $event = Event::find($event_id);
            if ($event) {
                $event_going_user = json_decode($event->going_users_id);
                array_push($event_going_user, $going_user_id);
                $event_going_user = json_encode($event_going_user);

                $event->going_users_id = $event_going_user;
                $event->save();

                $response['alertMessage'] = get_phrase('Going to Event');
                $response['showElem'] = "#dropdown_interest$event_id";
                $response['hideElem'] = "#btn_interest$event_id";
                $response['fadeOutElem'] = "#dropdown_interest$event_id .btn-going";
                $response['fadeInElem'] = "#dropdown_interest$event_id .btn-interested";

                $response['elemSelector'] = "#dropdown_interest$event_id .dropdown_event_label";
                $response['content'] = '<i class="fa-solid fa-star"></i> Going';
            } else {
                $response['success'] = false;
                $response['message'] = 'event not found';
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    // event notgoing 
    public function event_notgoing(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;


            $going_user_id = $user_id;
            $event_id = $id;
            $event = Event::find($event_id);
            $event_going_user = json_decode($event->going_users_id, true);
            $this_user_key = array_search($going_user_id, $event_going_user);
            array_splice($event_going_user, $this_user_key);
            $event_going_user = json_encode($event_going_user);

            $event->going_users_id = $event_going_user;
            $event->save();
            $response = array('alertMessage' => get_phrase('Cancle to Event Going'), 'showElem' => "#goingId$event_id", 'hideElem' => "#notGoingId$event_id");
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    // event interested
    public function event_interested(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $going_user_id = $user_id;

            $event_id = $id;
            $event = Event::find($event_id);
            $event_going_user = json_decode($event->interested_users_id);
            array_push($event_going_user, $going_user_id);
            $event_going_user = json_encode($event_going_user);

            $event->interested_users_id = $event_going_user;
            $event->save();


            $response['alertMessage'] = get_phrase('Interested to Event');
            $response['showElem'] = "#dropdown_interest$event_id";
            $response['hideElem'] = "#btn_interest$event_id";
            $response['fadeOutElem'] = "#dropdown_interest$event_id .btn-interested";
            $response['fadeInElem'] = "#dropdown_interest$event_id .btn-going";

            $response['elemSelector'] = "#dropdown_interest$event_id .dropdown_event_label";
            $response['content'] = '<i class="fa-solid fa-star"></i> Interested';
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    // event notinterested
    public function event_notinterested(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $going_user_id = $user_id;
            $event_id = $id;
            $event = Event::find($event_id);
            $event_going_user = json_decode($event->interested_users_id, true);
            $this_user_key = array_search($going_user_id, $event_going_user);
            array_splice($event_going_user, $this_user_key);
            $event_going_user = json_encode($event_going_user);

            $event->interested_users_id = $event_going_user;
            $event->save();
            $response = array('alertMessage' => get_phrase('Not Interested to Event'), 'showElem' => "#interestedId$event_id", 'hideElem' => "#notInterestedId$event_id");
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    // event Cancel
    public function event_cancel(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $going_user_id = $user_id;
            $event_id = $id;
            $event = Event::find($event_id);


            $event_going_user = json_decode($event->interested_users_id, true);
            $this_user_key = array_search($going_user_id, $event_going_user);
            array_splice($event_going_user, $this_user_key);
            $event_going_user = json_encode($event_going_user);
            $event->interested_users_id = $event_going_user;

            $event_going_user = json_decode($event->going_users_id, true);
            $this_user_key = array_search($going_user_id, $event_going_user);
            array_splice($event_going_user, $this_user_key);
            $event_going_user = json_encode($event_going_user);
            $event->going_users_id = $event_going_user;

            $event->save();

            $response['alertMessage'] = get_phrase('Event has been Canceled');
            $response['showElem'] = "#btn_interest$event_id";
            $response['hideElem'] = "#dropdown_interest$event_id";
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }
    // public function events_discussion(Request $request, $event_id)
    // {
    //     $token = $request->bearerToken();
    //     $response = array();

    //     if (isset ($token) && $token != '') {
    //         $user_id = auth('sanctum')->user()->id;

    //         $event = Event::where('id', $event_id)->first();

    //         $response1 = []; // Initialize the response array

    //         if ($event) {
    //             // Fetch posts for the current member
    //             $posts = Posts::orderBy('created_at', 'desc')->where('publisher_id', $event->id)->where('publisher', 'event')->where('privacy', 'public')->get();

    //             foreach ($posts as $post) {
    //                 $user1 = User::where('id', $post->user_id)->first();

    //                 // Media section of posts
    //                 $media = Media_files::where('post_id', $post->post_id)->first();

    //                 // comment count 
    //                 $commentsCount = Comments::where('id_of_type', $post->post_id)->count();

    //                 // user react of each posts
    //                 $userReacts = json_decode($post->user_reacts, true);

    //                 // Initialize counters
    //                 $likeCount = 0;
    //                 $loveCount = 0;
    //                 $sadCount = 0;
    //                 $hahaCount = 0;
    //                 $angryCount = 0;

    //                 // Count occurrences of reactions
    //                 foreach ($userReacts as $react) {
    //                     switch ($react) {
    //                         case 'like':
    //                             $likeCount++;
    //                             break;
    //                         case 'love':
    //                             $loveCount++;
    //                             break;
    //                         case 'sad':
    //                             $sadCount++;
    //                             break;
    //                         case 'haha':
    //                             $hahaCount++;
    //                             break;
    //                         case 'angry':
    //                             $angryCount++;
    //                             break;
    //                         default:
    //                             // Do nothing or handle unexpected reactions
    //                             break;
    //                     }
    //                 }
    //                 // Calculate the total reactions
    //                 $totalReacts = $likeCount + $loveCount + $sadCount + $hahaCount + $angryCount;

    //                 $response[] = [
    //                     'post_id' => $post->post_id,
    //                     'user_id' => $post->user_id,
    //                     'name' => $user1->name,
    //                     'photo' => get_user_images($user1->id),
    //                     'publisher' => $post->publisher,
    //                     'post_type' => $post->post_type,
    //                     'privacy' => $post->privacy,
    //                     'post_image' => (!empty ($media->file_name)) ? get_post_images($media->file_name) : '',
    //                     'userReaction' => isset ($userReacts[$user_id]) ? $userReacts[$user_id] : null,
    //                     'description' => $post->description,
    //                     'commentsCount' => $commentsCount,
    //                     'reaction_counts' => [
    //                         'like' => $likeCount,
    //                         'love' => $loveCount,
    //                         'sad' => $sadCount,
    //                         'haha' => $hahaCount,
    //                         'angry' => $angryCount,
    //                         'total' => $totalReacts // Include total reactions count
    //                     ],

    //                     // Add other fields as needed
    //                 ];
    //             }



    //             // }
    //         } else {
    //             $response['success'] = false;
    //             $response['message'] = 'No event found';
    //         }
    //     }

    //     return response()->json($response);
    // }

    public function events_discussion(Request $request, $event_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $event = Event::where('id', $event_id)->first();

            if ($event) {
                // Fetch posts for the current event
                $posts = Posts::orderBy('created_at', 'desc')
                    ->where('publisher_id', $event->id)
                    ->where('publisher', 'event')
                    ->where('privacy', 'public')
                    ->get();

                foreach ($posts as $post) {
                    $user = User::find($post->user_id);

                    // Fetch media files associated with the post
                    $mediaFiles = Media_files::where('post_id', $post->post_id)->get();

                    // $media = Media_files::where('post_id', $post->post_id)->first();

                    // Initialize an array to store post images
                    $postImages = [];
                    $file = 'text';

                    // Loop through the media files and add them to the postImages array
                    foreach ($mediaFiles as $media) {

                        if ($media->file_type == "image") {
                            $postImages[] = get_post_images($media->file_name);
                        } else {
                            $postImages[] = get_post_videos($media->file_name);
                        }
                        $file = $media->file_type;
                    }

                    // comment count 
                    $commentsCount = Comments::where('id_of_type', $post->post_id)->count();

                    // user react of each post
                    $userReacts = json_decode($post->user_reacts, true);

                    // Initialize counters for reaction types
                    $reactionsCount = array_fill_keys(['like', 'love', 'sad', 'haha', 'angry'], 0);

                    // Count occurrences of reactions
                    foreach ($userReacts as $react) {
                        if (array_key_exists($react, $reactionsCount)) {
                            $reactionsCount[$react]++;
                        }
                    }

                    // Calculate the total reactions
                    $totalReacts = array_sum($reactionsCount);

                    // $formattedDate = date('M d \a\t H:i A', strtotime($post->posted_on));
                    $createdDate = Carbon::createFromTimestamp(strtotime($post->posted_on));
                    $daysDifference = $createdDate->diffInDays(Carbon::now());
                    if ($daysDifference < 7) {
                        // Show "time ago" format
                        $formattedDate = $createdDate->diffForHumans();
                    } else {
                        // Show the exact date and time
                        $formattedDate = $createdDate->toDayDateTimeString();
                        // Example format: 'Mon, Jan 1, 2024 12:00 AM'
                    }
                    $followers = Follower::where('user_id', $user_id)->get();
                    $follow = 'Follow';
                    foreach ($followers as $follo) {
                        if ($follo->follow_id == $post->user_id) {
                            $follow = 'Unfollow';
                        }
                    }

                    $taggedUserIds = json_decode($post->tagged_user_ids, true);
                    $taggedUsers = User::whereIn('id', $taggedUserIds)->get(['id', 'name']);
                    $taggedUserList = [];
                    foreach ($taggedUsers as $tags) {
                        $taggedUserList[] = [
                            'id' => $tags->id,
                            'name' => $tags->name
                        ];
                    }

                    // Construct the response array for the current post
                    $response[] = [
                        'post_id' => $post->post_id,
                        'user_id' => $post->user_id,
                        'name' => $user->name,
                        'photo' => get_user_images($user->id),
                        'publisher' => $post->publisher,
                        'publisherId' => $post->publisher_id,
                        'location' => $post->location != null ? $post->location : "",
                        'post_type' => $post->post_type,
                        'fileType' => $file,
                        'privacy' => $post->privacy,
                        'post_images' => $postImages, // Array of post images
                        'thumbnail' => $post->mobile_app_image != null ? get_post_images($post->mobile_app_image) : "",
                        // 'post_images' => (!empty ($media->file_name)) ? get_post_images($media->file_name) : '',
                        'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,
                        // 'description' => $post->description ? $post->description : "",
                        'description' => $post->description != null ? $post->description : "",
                        'commentsCount' => $commentsCount,
                        'reaction_counts' => $reactionsCount,
                        'total' => $totalReacts,
                        'created_at' => $formattedDate,
                        'follow' => $follow,
                        'taggedUserList' => $taggedUserList,
                        // Add other fields as needed
                    ];
                }
            } else {
                $response['success'] = false;
                $response['message'] = 'No event found';
            }
        }

        return response()->json($response);
    }
    public function blogs(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $blogs = Blog::orderBy('id', 'desc')->get();

            if ($blogs->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No blog found';
            } else {
                $blogArray = [];

                foreach ($blogs as $blog) {
                    $user = User::where('id', $blog->user_id)->first();
                    $category = Blogcategory::where('id', $blog->category_id)->first();
                    // comment count 
                    $commentsCount = Comments::where('id_of_type', $blog->id)->count();

                    $view = json_decode($blog->view, true);
                    $view = count($view);

                    $tag = json_decode($blog->tag, true);
                    $tags = implode(",", array_filter(json_decode($blog->tag, true)));

                    // Calculate the time difference
                    $createdAt = Carbon::parse($blog->created_at);
                    $timeDifference = $createdAt->diffForHumans();

                    $blogArray[] = [
                        'id' => $blog->id,
                        'user_id' => $blog->user_id,
                        'user' => $user->name,
                        'user_image' => get_user_images($user->id),
                        'title' => $blog->title,
                        'category_id' => $blog->category_id,
                        'category' => $category->name ?? '',
                        'my_blog' => $blog->user_id == $user_id ? "my_blog" : "not_my_blog",
                        'description' => $blog->description,
                        'coverphoto' => get_group_event_photos($blog->thumbnail, "thumbnail", "blog"),
                        'created_at' => date('d M Y', strtotime($blog->created_at)),
                        'longago' => $timeDifference,
                        'view' => $view,
                        'tags' => $tags,
                        'tag' => $tag,
                        'commentsCount' => $commentsCount,

                    ];
                }
                $response = $blogArray;
            }
        }

        return response()->json($response);
    }
    public function blog_category(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $blogs = Blogcategory::orderBy('id', 'desc')->get();

            if ($blogs->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No blog found';
            } else {
                $blogArray = [];

                foreach ($blogs as $blog) {

                    $blogArray[] = [

                        'category' => $blog->name,
                        'category_id' => $blog->id,


                    ];
                }
                $response = $blogArray;
            }
        }

        return response()->json($response);
    }

    public function create_blogs(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $request->validate([
                'title' => 'required|max:255',
                'category' => 'required',
            ]);

            if ($request->image && !empty($request->image)) {

                $file_name = FileUploader::upload($request->image, 'public/storage/blog/thumbnail', 370);
                FileUploader::upload($request->image, 'public/storage/blog/coverphoto/' . $file_name, 900);
            }

            $blog = new Blog();
            $blog->user_id = $user_id;
            $blog->title = $request->title;
            $blog->category_id = $request->category;

            // $tags = json_decode($request->tag, true);
            // $tag_array = array();
            // if (is_array($tags)) {
            //     foreach ($tags as $key => $tag) {
            //         $tag_array[$key] = $tag['value'];
            //     }
            // }
            $blog->tag = json_encode(array_filter(explode(",", $request->tag)));
            if ($request->description && !empty($request->description)) {
                $blog->description = $request->description;
            }
            if ($request->image && !empty($request->image)) {
                $blog->thumbnail = $file_name;
            }
            $blog->view = json_encode(array());
            $done = $blog->save();
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'create blog successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to create blog';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function update_blogs(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $request->validate([
                'title' => 'required|max:255',
                'category' => 'required',
            ]);

            if ($request->image && !empty($request->image)) {

                $file_name = FileUploader::upload($request->image, 'public/storage/blog/thumbnail', 370);
                FileUploader::upload($request->image, 'public/storage/blog/coverphoto/' . $file_name, 900);
            }

            $blog = Blog::find($id);

            $blog->user_id = $user_id;
            // store image name for delete file operation 
            $imagename = $blog->thumbnail;

            $blog->user_id = $user_id;
            $blog->title = $request->title;
            $blog->category_id = $request->category;
            // $tags = json_decode($request->tag, true);
            // $tag_array = array();

            // if (is_array($tags)) {
            //     foreach ($tags as $key => $tag) {
            //         $tag_array[$key] = $tag['value'];
            //     }
            // }
            // $blog->tag = json_encode($tag_array);
            $blog->tag = json_encode(array_filter(explode(",", $request->tag)));
            $blog->description = $request->description;
            !empty($request->image) ? $blog->thumbnail = $file_name : $blog->thumbnail;
            $done = $blog->save();
            if ($done) {
                // just put the file name and folder name nothing more :) 
                if (!empty($request->image)) {
                    removeFile('blog', $imagename);
                }

                $response['success'] = true;
                $response['message'] = 'update successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to update';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    // blog view 
    public function blog_view(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;



            //   $going_user_id = $user_id;
            $blog_id = $id;
            $blog = Blog::find($blog_id);
            if ($blog) {
                $blog_user = json_decode($blog->view);
                array_push($blog_user, $user_id);
                $blog_viewer = json_encode($blog_user);

                $blog->view = $blog_viewer;
                $blog->save();

                $response['success'] = true;
                $response['message'] = 'blog views';
            } else {
                $response['success'] = false;
                $response['message'] = 'blog not found';
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function blog_delete(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $blog = Blog::find($id);
            // store image name for delete file operation 
            $imagename = $blog->thumbnail;

            $done = $blog->delete();
            if ($done) {
                // just put the file name and folder name nothing more :) 
                removeFile('blog', $imagename);

                $response['success'] = true;
                $response['message'] = 'blog deleted successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'blog not found';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }
    public function paid_content(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $blog = PaidContentCreator::orderBy('id', 'desc')->where('user_id', $user_id)->first();

            if (!$blog) {
                $response['success'] = false;
                $response['message'] = 'No paid_content_Creator found';
            } else {
                $blogArray = [];

                // foreach ($blogs as $blog) {
                $user = User::where('id', $blog->user_id)->first();
                $social_accounts = json_decode($blog->social_accounts, true);
                $posts_data = array();

                // Fetch posts for the current member
                $member_posts = Posts::orderBy('created_at', 'desc')->where('user_id', $blog->user_id)->where('publisher', 'paid_content')->get();

                foreach ($member_posts as $post) {
                    $user1 = User::where('id', $post->user_id)->first();

                    // Media section of posts
                    $media = Media_files::where('post_id', $post->post_id)->first();

                    // comment count 
                    $commentsCount = Comments::where('id_of_type', $post->post_id)->count();

                    // user react of each posts
                    $userReacts = json_decode($post->user_reacts, true);

                    // Initialize counters
                    $likeCount = 0;
                    $loveCount = 0;
                    $sadCount = 0;
                    $hahaCount = 0;
                    $angryCount = 0;

                    // Count occurrences of reactions
                    foreach ($userReacts as $react) {
                        switch ($react) {
                            case 'like':
                                $likeCount++;
                                break;
                            case 'love':
                                $loveCount++;
                                break;
                            case 'sad':
                                $sadCount++;
                                break;
                            case 'haha':
                                $hahaCount++;
                                break;
                            case 'angry':
                                $angryCount++;
                                break;
                            default:
                                // Do nothing or handle unexpected reactions
                                break;
                        }
                    }
                    // Calculate the total reactions
                    $totalReacts = $likeCount + $loveCount + $sadCount + $hahaCount + $angryCount;

                    $posts_data[] = [
                        'id' => $post->post_id,
                        'user_id' => $post->user_id,
                        'name' => $user1->name,
                        'photo' => get_user_images($user1->id),
                        'publisher' => $post->publisher,
                        'privacy' => $post->privacy,
                        'post_image' => (!empty($media->file_name)) ? get_post_images($media->file_name) : '',
                        'userReaction' => isset($userReacts[$user_id]) ? $userReacts[$user_id] : null,
                        'description' => $post->description,
                        'commentsCount' => $commentsCount,
                        'reaction_counts' => [
                            'like' => $likeCount,
                            'love' => $loveCount,
                            'sad' => $sadCount,
                            'haha' => $hahaCount,
                            'angry' => $angryCount,
                            'total' => $totalReacts // Include total reactions count
                        ],

                        // Add other fields as needed
                    ];
                }

                $blogArray = [
                    'id' => $blog->id,
                    'user_id' => $blog->user_id,
                    'user' => $user->name,
                    'user_image' => get_user_images($user->id),
                    'title' => $blog->title,
                    'sub_title' => $blog->sub_title,
                    // 'category_id' => $blog->category_id,
                    // // 'category' => $category->name,
                    // 'my_blog' => $blog->user_id == $user_id ? "my_blog" : "not_my_blog",
                    'description' => $blog->description,
                    'bio' => $blog->bio,
                    'social_accounts' => $social_accounts,
                    'coverphoto' => get_all_assets_photos($blog->cover_photo, "images"),
                    'logo' => get_all_assets_photos($blog->logo, "images"),
                    'posts' => $posts_data,




                ];
                // }
                $response = $blogArray;
            }
        }

        return response()->json($response);
    }
    public function paid_content_package(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $paid_contents = PaidContentPackages::orderBy('id', 'desc')->where('user_id', $user_id)->get();

            if (!$paid_contents) {
                $response['success'] = false;
                $response['message'] = 'No paid_content_Creator found';
            } else {
                $PaidArray = [];

                foreach ($paid_contents as $paid_content) {
                    $user = User::where('id', $paid_content->user_id)->first();

                    $PaidArray[] = [
                        'id' => $paid_content->id,
                        'user_id' => $paid_content->user_id,
                        'user' => $user->name,
                        'user_image' => get_user_images($user->id),
                        'title' => $paid_content->title,
                        'description' => $paid_content->description,
                        'price' => $paid_content->price,

                    ];
                }
                $response = $PaidArray;
            }
        }

        return response()->json($response);
    }
    public function jobs(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $user_id1 = auth('sanctum')->user()->id;

            $blogs = Job::orderBy('id', 'desc')->where('status', 1)->get();

            if ($blogs->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No blog found';
            } else {
                $blogArray = [];

                foreach ($blogs as $blog) {
                    $user = User::where('id', $blog->user_id)->first();
                    $category = JobCategory::where('id', $blog->category_id)->first();
                    $wishlist = JobWishlist::where('job_id', $blog->id)->where('user_id', $user_id)->first();

                    // Compare the end date with the current date
                    $endDate = Carbon::parse($blog->end_date); // Parse end date from database
                    $isExpired = $endDate->isPast(); // Check if the end date is in the past


                    $blogArray[] = [
                        'id' => $blog->id,
                        'user_id' => $blog->user_id,
                        'user' => $user->name,
                        'user_image' => get_user_images($user->id),
                        'company' => $blog->company,
                        'title' => $blog->title,
                        'category_id' => $blog->category_id,
                        'category' => $category->name,
                        'starting_salary_range' => $blog->starting_salary_range,
                        'ending_salary_range' => $blog->ending_salary_range,
                        'type' => $blog->type,
                        'location' => $blog->location,
                        'status' => $blog->status,
                        'is_published' => $blog->is_published,
                        'my_blog' => $blog->user_id == $user_id ? "my_job" : "not_my_job",
                        'wishlist' => $wishlist ? "wishlist" : "not_wishlist",
                        'description' => $blog->description,
                        'thumbnail' => get_group_event_photos($blog->thumbnail, "thumbnail", "job"),
                        // 'created_at' => date('d-m-Y', strtotime($blog->created_at)),
                        'start_date' => $blog->start_date,
                        'end_date' => $blog->end_date,
                        'is_expired' => $isExpired ? 'expired' : 'active', // Add field indicating if the job is expired



                    ];
                }
                $response = $blogArray;
            }
        }

        return response()->json($response);
    }

    public function create_jobs(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $request->validate([
                'title' => 'required|max:255',
                'category' => 'required',
            ]);

            $job = new Job();
            $job->user_id = $user_id;
            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->company = $request->company;
            $job->starting_salary_range = $request->starting_salary_range;
            $job->ending_salary_range = $request->ending_salary_range;
            $job->type = $request->type;
            $job->status = 0;
            $job->is_published = 0;
            $job->location = $request->location;
            $job->description = $request->description;
            // if($request->image && !empty($request->image)){
            //     $job->thumbnail = $file_name;
            // }
            $job_create = $job->save();
            // $jobId = $job->id;
            if ($job_create) {
                $response['success'] = true;
                $response['message'] = 'job created successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'job not created';
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;

    }
    public function update_jobs(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $request->validate([
                'title' => 'required|max:255',
                'category' => 'required',
            ]);

            $job = Job::find($id);
            //    $job->thumbnail = $new_thumbnail;
            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->company = $request->company;
            $job->starting_salary_range = $request->starting_salary_range;
            $job->ending_salary_range = $request->ending_salary_range;
            $job->type = $request->type;
            $job->location = $request->location;

            if ($job['status'] == '1') {
                $job->status = 1;
            } else if ($job['status'] == '0') {
                $job->status = 0;
            }
            $job->description = $request->description;
            $done = $job->save();
            if ($done) {

                $response['success'] = true;
                $response['message'] = 'jobs updated';
            } else {
                $response['success'] = false;
                $response['message'] = 'jobs not updated';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function job_delete(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $job = Job::find($id);
            // $imagename = $job->thumbnail;
            $job_history = DB::table('payment_histories')->where('item_id', $job->id)->delete();
            $job_wishlist = JobWishlist::where('job_id', $job->id)->delete();
            $job_apply = JobApply::where('job_id', $job->id)->delete();

            // $thumbnailPathName = public_path('storage/job/thumbnail/') . $job->thumbnail;

            // if (file_exists($thumbnailPathName)) {
            //     unlink($thumbnailPathName);
            // }

            $done = $job->delete();

            if ($done) {

                $response['success'] = true;
                $response['message'] = 'Job Deleted Successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Job not Deleted Successfully';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    // Job Wishlist
    public function job_add_wishlist(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $data['user_id'] = $user_id;
            $data['job_id'] = $id;
            $data['created_at'] = Carbon::now();
            $data['updated_at'] = Carbon::now();
            $wishlist = JobWishlist::where('user_id', $user_id)->where('job_id', $id)->first();
            if ($wishlist) {
                JobWishlist::where('user_id', $user_id)->where('job_id', $id)->delete();
                $response['success'] = false;
                $response['message'] = 'Job delete from wishlist successfully';
            } else {
                JobWishlist::insert($data);
                $response['success'] = true;
                $response['message'] = 'job added wishlist successfully';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function JobApply(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $request->validate([
                'email' => 'required',
                'phone' => 'required',
                'image' => 'file|mimes:pdf|max:10240'
            ]);

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file = $request->file('image');
                $file_extension = $file->getClientOriginalExtension();
                $file_name = Str::random(40) . '.' . $file_extension;
                move_uploaded_file($file->getPathname(), 'public/storage/job/cv/' . $file_name);
            }

            $owner_id = Job::where('id', $id)->first()->user_id;
            $apply = new JobApply();
            $apply->job_id = $request->id;
            $apply->owner_id = $owner_id;
            $apply->user_id = $user_id;
            $apply->email = $request->email;
            $apply->phone = $request->phone;
            if ($request->image && !empty($request->image)) {
                $apply->attachment = $file_name;
            }
            $applies = $apply->save();
            if ($applies) {
                $response['success'] = true;
                $response['message'] = 'job apply successfully submited';
            } else {
                $response['success'] = false;
                $response['message'] = 'job apply is not submited';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }
    public function fundraisers(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            // $job_wishlist = JobWishlist::where('user_id', $user_id)->get();
            $fundraisers = Fundraiser::orderBy('id', 'desc')->where('status', 0)->get();

            if ($fundraisers->isEmpty()) {
                $response['success'] = false;
                $response['message'] = 'No fundraisers found';
            } else {
                $blogArray = [];

                foreach ($fundraisers as $fundraiser) {
                    $user = User::where('id', $fundraiser->user_id)->first();
                    $category = Fundraiser_category::where('id', $fundraiser->categories_id)->first();

                    // Calculate percentage raised and round it to the nearest integer
                    $percentageRaised = round(($fundraiser->raised_amount / $fundraiser->goal_amount) * 100);

                    $donate = Fundraiser_donation::join('fundraisers', 'fundraiser_donations.fundraiser_id', '=', 'fundraisers.id')
                        ->select('fundraiser_donations.*', 'fundraiser.*')
                        ->where('fundraiser_donations.fundraiser_id', $fundraiser->id)
                        ->count();

                    $days = round((strtotime($fundraiser->timestamp_end) - strtotime($fundraiser->created_at)) / (3600 * 24));
                    $days_left = $days;

                    $blogArray[] = [
                        'id' => $fundraiser->id,
                        'user_id' => $fundraiser->user_id,
                        'user' => $user->name,
                        'user_image' => get_user_images($user->id),
                        'goal_amount' => $fundraiser->goal_amount,
                        'raised_amount' => $fundraiser->raised_amount == null ? 0 : $fundraiser->raised_amount,
                        'percentage_raised' => $percentageRaised,
                        'progress' => $fundraiser->goal_amount == $fundraiser->raised_amount ? "complete" : "in_progress",
                        'fundraiser' => $fundraiser->user_id == $user_id ? 'my' : "not_my",
                        'title' => $fundraiser->title,
                        'description' => $fundraiser->description,
                        'category_id' => $fundraiser->categories_id,
                        'category' => $category->name,
                        'donar' => $donate,
                        'days' => $days_left,
                        'timestamp_end' => date('d F, Y', strtotime($fundraiser->timestamp_end)),
                        'cover_photo' => get_all_assets_photos($fundraiser->cover_photo, "campaign", "images"),
                        // 'created_at' => date('d-m-Y', strtotime($blog->created_at)),



                    ];
                }
                $response = $blogArray;
            }
        }

        return response()->json($response);
    }

    public function create_fundraiser(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $request->validate([
                'title' => 'required',
                'description' => 'required',
                'goal_amount' => 'required | numeric|min:1',
                'timestamp_end' => 'required',
                'categories_id' => 'required',
            ]);
            if ($request->cover_photo == '') {
                $image = '';
            } else {
                $item = $request->file('cover_photo');
                $image_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();
                $path = public_path('assets/frontend/images/campaign');
                if (!File::isDirectory($path)) {
                    File::makeDirectory($path, 0777, true, true);
                } else {
                    $item->move(public_path('assets/frontend/images/campaign/'), $image_name);
                }
                $image = $image_name;
            }
            $campaign = new Fundraiser();
            $campaign->user_id = $user_id;
            $campaign->title = $request->title;
            $campaign->description = $request->description;
            $campaign->goal_amount = $request->goal_amount;
            $campaign->timestamp_end = $request->timestamp_end;
            $campaign->categories_id = $request->categories_id;
            $campaign->cover_photo = $image;
            $done = $campaign->save();
            if ($done) {
                // $data['user_id'] = $user_id;
                // $data['privacy'] = "public";
                // $data['publisher'] = 'fundraiser';
                // $data['publisher_id'] = $campaign->id;
                // $data['post_type'] = "fundraiser";
                // $data['status'] = 'active';
                // $data['description'] = $request->title;
                // $data['user_reacts'] = json_encode(array());
                // $data['user_reacts'] = json_encode(array());
                // $data['tagged_user_ids'] = json_encode(array());
                // $data['created_at'] = time();
                // $data['updated_at'] = $data['created_at'];
                // Posts::create($data);
                $response['success'] = true;
                $response['message'] = 'fundraisers created  successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'fundraisers is not created successfully';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }
    public function update_fundraiser(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            if ($request->cover_photo != '') {
                $item = $request->file('cover_photo');
                $image_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();

                $path = public_path('assets/frontend/images/campaign');
                if (!File::isDirectory($path)) {
                    File::makeDirectory($path, 0777, true, true);
                } else {
                    $item->move(public_path('assets/frontend/images/campaign/'), $image_name);
                }
                $image = $image_name;
                $data['cover_photo'] = $image;
            }

            // $data['user_id'] = $request->up_user_id;
            $data['title'] = $request->title;
            $data['description'] = $request->description;
            $data['goal_amount'] = $request->goal_amount;
            $data['timestamp_end'] = $request->timestamp_end;
            $data['categories_id'] = $request->categories_id;

            $update = Fundraiser::where('id', $id)->update($data);
            if ($update) {
                $response['success'] = true;
                $response['message'] = 'fundraisers updated successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'fundraisers is not updated';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;

    }

    public function invited_fundraiser(Request $request, $invited_friend_id, $fundraiser_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $invitations = Fundraiser::where('id', $fundraiser_id)->value('invited');

            if ($invitations != '') {
                $invited = json_decode($invitations);
            } else {
                $invited = [];
            }
            array_push($invited, $invited_friend_id);
            $invited = json_encode($invited);

            Fundraiser::where('id', $fundraiser_id)->update(['invited' => $invited]);

            $invite = new Invite();
            $invite->invite_reciver_id = $invited_friend_id;
            $invite->invite_sender_id = $user_id;
            $invite->fundraiser_id = $fundraiser_id;
            $done = $invite->save();
            if ($done) {
                $notify = new Notification();
                $notify->sender_user_id = $user_id;
                $notify->reciver_user_id = $invited_friend_id;
                $notify->type = 'fundraiser';
                $notify->fundraiser_id = $fundraiser_id;
                $notify->save();

                $response['success'] = true;
                $response['message'] = 'invited successfully';
            } else {
                $response['success'] = false;
                $response['message'] = ' not invited';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function notifications(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $date = Carbon::today();
            $new_notification = Notification::where('reciver_user_id', $user_id)->where('status', '0')
                ->orderBy('id', 'DESC')->get();
            $newnoti = [];
            foreach ($new_notification as $post) {
                $user = User::find($post->sender_user_id);


                // Convert the Unix timestamp to a "time ago" format using Carbon
                $createdDate = Carbon::createFromTimestamp(strtotime($post->created_at));
                $formattedDate = $createdDate->diffForHumans();
                $eventName = "";

                // Check if event_id is not null
                if (!is_null($post->event_id)) {
                    // Fetch event details based on event_id
                    $event = Event::find($post->event_id);
                    // If event is found, set the event name
                    $eventName = $event ? $event->title : "";
                }
                // Initialize event name variable
                $groupName = "";

                // Check if event_id is not null
                if (!is_null($post->group_id)) {
                    // Fetch event details based on event_id
                    $group = Group::find($post->group_id);
                    // If event is found, set the event name
                    $groupName = $group ? $group->title : "";
                }
                // Initialize event name variable
                $pageName = "";

                // Check if event_id is not null
                if (!is_null($post->page_id)) {
                    // Fetch event details based on event_id
                    $page = Page::find($post->page_id);
                    // If event is found, set the event name
                    $pageName = $page ? $page->title : "";
                }
                // Construct the response array for the current post
                $newnoti[] = [
                    'id' => $post->id,
                    'sender_user_id' => $post->sender_user_id,
                    'reciver_user_id' => $post->reciver_user_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->id),
                    'type' => $post->type,
                    'event_id' => $post->event_id,
                    'event_name' => $eventName,
                    'page_id' => $post->page_id,
                    'pageName' => $pageName,
                    'group_id' => $post->group_id,
                    'groupName' => $groupName,
                    'status' => $post->status,
                    'view' => $post->view,
                    // 'created_at' => $post->created_at,
                    // 'updated_at' => $post->updated_at,
                    // 'fundraiser_id' => $post->fundraiser_id,
                    'created_at' => $formattedDate, // Use the formatted date string
                    // Add other fields as needed
                ];
            }
            $older_notification = Notification::where('reciver_user_id', $user_id)->where('created_at', '<', $date)->orderBy('id', 'DESC')->get();
            $oldnoti = [];
            foreach ($older_notification as $post) {
                $user = User::find($post->sender_user_id);

                // Convert the Unix timestamp to a "time ago" format using Carbon
                $createdDate = Carbon::createFromTimestamp(strtotime($post->created_at));
                $formattedDate = $createdDate->diffForHumans();

                // Initialize event name variable
                $eventName = "";

                // Check if event_id is not null
                if (!is_null($post->event_id)) {
                    // Fetch event details based on event_id
                    $event = Event::find($post->event_id);
                    // If event is found, set the event name
                    $eventName = $event ? $event->title : "";
                }
                // Initialize event name variable
                $groupName = "";

                // Check if event_id is not null
                if (!is_null($post->group_id)) {
                    // Fetch event details based on event_id
                    $group = Group::find($post->group_id);
                    // If event is found, set the event name
                    $groupName = $group ? $group->title : "";
                }
                // Initialize event name variable
                $pageName = "";

                // Check if event_id is not null
                if (!is_null($post->page_id)) {
                    // Fetch event details based on event_id
                    $page = Page::find($post->page_id);
                    // If event is found, set the event name
                    $pageName = $page ? $page->title : "";
                }

                // Construct the response array for the current post
                $oldnoti[] = [
                    'id' => $post->id,
                    'sender_user_id' => $post->sender_user_id,
                    'reciver_user_id' => $post->reciver_user_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->id),
                    'type' => $post->type,
                    'event_id' => $post->event_id,
                    'event_name' => $eventName,
                    'page_id' => $post->page_id,
                    'pageName' => $pageName,
                    'group_id' => $post->group_id,
                    'groupName' => $groupName,
                    'status' => $post->status,
                    'view' => $post->view,
                    'created_at' => $formattedDate, // Use the formatted date string
                    // Add other fields as needed
                ];
            }

            // Construct the response
            $response['new_notifications'] = $newnoti;
            $response['older_notifications'] = $oldnoti;
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function accept_friend_notification(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $is_updated = Friendships::where('requester', $id)->where('accepter', $user_id)->update(['is_accepted' => '1']);
            Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->update(['status' => '1', 'view' => '1']);

            if ($is_updated == 1) {
                //update my id to my friend list
                $my_friends = User::where('id', $user_id)->value('friends');
                $my_friends = json_decode($my_friends);
                if (is_array($my_friends)) {
                    array_push($my_friends, (int) $id);
                } else {
                    $my_friends = [(int) $id];
                }
                $my_friends = json_encode($my_friends);

                User::where('id', $user_id)->update(['friends' => $my_friends]);

                //update my id to my friend list
                $my_friends_of_friends = User::where('id', $id)->value('friends');
                $my_friends_of_friends = json_decode($my_friends_of_friends);

                if (is_array($my_friends_of_friends)) {
                    array_push($my_friends_of_friends, (int) $user_id);
                } else {
                    $my_friends_of_friends = [(int) $user_id];
                }
                $my_friends_of_friends = json_encode($my_friends_of_friends);

                User::where('id', $id)->update(['friends' => $my_friends_of_friends]);

            }

            $notify = new Notification();
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $id;
            $notify->type = "friend_request_accept";
            $save = $notify->save();
            if ($save) {
                $response['success'] = true;
                $response['message'] = 'Friend request accept';
            } else {
                $response['success'] = false;
                $response['message'] = 'not found request';
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }
    public function decline_friend_notification(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $friendship = Friendships::where('requester', $id)->where('accepter', $user_id)->delete();
            $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->delete();
            $response['success'] = true;
            $response['message'] = 'successfully decline';
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function accept_group_notification(Request $request, $id, $group_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('group_id', $group_id)->update(['is_accepted' => '1']);
            $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->update(['status' => '1', 'view' => '1']);

            $notify = new Notification();
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $id;
            $notify->type = "group_invitation_accept";
            $save = $notify->save();
            if ($save) {
                $response['success'] = true;
                $response['message'] = 'Group request accept';
            } else {
                $response['success'] = false;
                $response['message'] = 'not found request';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;

    }

    public function decline_group_notification(Request $request, $id, $group_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('group_id', $group_id)->delete();
            $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->delete();

            $response['success'] = true;
            $response['message'] = 'group notification decline';
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;

    }
    public function accept_event_notification(Request $request, $id, $event_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('event_id', $event_id)->update(['is_accepted' => '1']);
            $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->update(['status' => '1', 'view' => '1']);

            if ($is_updated == '1') {
                //update my friends id to my friend list
                $going_users_id = Event::where('id', $event_id)->value('going_users_id');
                $going_users_id = json_decode($going_users_id);
                array_push($going_users_id, (int) $id);
                $going_users_id = json_encode($going_users_id);

                Event::where('id', $event_id)->update(['going_users_id' => $going_users_id]);

            }

            $notify = new Notification();
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $id;
            $notify->type = "event_invitation_accept";
            $save = $notify->save();
            if ($save) {
                $response['success'] = true;
                $response['message'] = 'event invite request accept';
            } else {
                $response['success'] = false;
                $response['message'] = 'not request found ';
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function decline_event_notification(Request $request, $id, $event_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('event_id', $event_id)->delete();
            $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->delete();
            if ($notify) {
                $response['success'] = true;
                $response['message'] = 'event request decline';
            } else {
                $response['success'] = false;
                $response['message'] = 'not found request';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function mark_as_read(Request $request, $id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $done = Notification::where('id', $id)->update(['status' => '1', 'view' => '1']);
            if ($done) {
                $response['success'] = true;
                $response['message'] = 'mark as read';
            } else {
                $response['success'] = false;
                $response['message'] = 'not found';
            }

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    //fundraiser................

    public function accept_fundraiser_notification(Request $request, $id, $fundraiser_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('fundraiser_id', $fundraiser_id)->update(['is_accepted' => '1']);
            $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->update(['status' => '1', 'view' => '1']);

            $notify = new Notification();
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $id;
            $notify->type = "fundraiser_request_accept";
            $notify->save();

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function decline_fundraiser_notification(Request $request, $id, $fundraiser_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $is_updated = Invite::where('invite_sender_id', $id)->where('invite_reciver_id', $user_id)->where('fundraiser_id', $fundraiser_id)->delete();
            $notify = Notification::where('sender_user_id', $id)->where('reciver_user_id', $user_id)->delete();

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    public function chat(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $previousChatList = Message_thrade::where('reciver_id', auth('sanctum')->user()->id)->orWhere('sender_id', auth('sanctum')->user()->id)->orderBy('id', 'DESC')->get();

            $chatList = [];
            foreach ($previousChatList as $chat) {

                $profile_id = $chat->reciver_id == $user_id ? $chat->sender_id : $chat->reciver_id;
                $user = User::find($profile_id);
                $last_chat = Chat::where('message_thrade', $chat->id)->orderBy('id', 'DESC')->first();
                $last_chat1 = Chat::where('reciver_id', $user_id)->where('read_status', 0)->first();
                if ($last_chat) {
                    $createdDate = Carbon::createFromTimestamp(strtotime($last_chat->created_at));
                    $formattedDate = $createdDate->format('h:i A');
                } else {
                    $createdDate = Carbon::createFromTimestamp(strtotime($chat->created_at));
                    $formattedDate = $createdDate->format('h:i A');
                }

                // Set $read based on the conditions
                $read = $last_chat ? ($last_chat->sender_id == $user_id ? 1 : 0) : 1;


                $chatList[] = [
                    "id" => $chat->id,
                    "reciver_id" => $chat->reciver_id,
                    "sender_id" => $chat->sender_id,
                    "profile_id" => $profile_id,
                    "profile_name" => $user->name,
                    "profile_photo" => get_user_images($user->photo),
                    "msg_sender" => $last_chat ? ($last_chat->sender_id == $user_id ? "You" : "") : "",
                    "last_msg" => $last_chat ? $last_chat->message : "",
                    "last_thumbs" => $last_chat ? $last_chat->thumbsup : 0,
                    "msg_time" => $formattedDate,
                    "read" => $read,
                    // "rstatus" => $read,
                ];
            }
        }
        $response = $chatList;
        // $response = $message;

        return $response;
    }
    public function chat_msg(Request $request, $msg_thrade)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $allchat = Chat::where('message_thrade', $msg_thrade)->get();

            $chatList = [];
            foreach ($allchat as $chat) {

                // $profile_id = $chat->reciver_id == $user_id ? $chat->sender_id : $chat->reciver_id;
                $user = User::find($chat->sender_id);
                // $last_chat = Chat::where('message_thrade', $chat->id)->orderBy('id', 'DESC')->first();

                $createdDate = Carbon::createFromTimestamp(strtotime($chat->created_at));
                $formattedDate = $createdDate->format('h:i A');

                $chatList[] = [
                    "id" => $chat->id,
                    "message_thrade" => $chat->message_thrade,
                    "reciver_id" => $chat->reciver_id,
                    "sender_id" => $chat->sender_id,
                    "sender" => $chat->sender_id == $user_id ? "my" : " not_mine",
                    "profile_name" => $user->name,
                    "profile_photo" => get_user_images($user->photo),
                    "message" => $chat->message,
                    "thumbs" => $chat->thumbsup,
                    "react" => $chat->react,
                    "msg_time" => $formattedDate,
                    "read" => $chat->read_status,
                ];
            }
        }
        $response = $chatList;
        // $response = $allchat;

        return $response;
    }

    public function chat_save(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $reciver = $request->reciver_id;

            $firstmessageThrade = Message_thrade::where(function ($query) use ($reciver, $user_id) {
                $query->where('sender_id', $reciver)
                    ->where('reciver_id', $user_id);
            })->orWhere(function ($query) use ($reciver, $user_id) {
                $query->where('sender_id', $user_id)
                    ->where('reciver_id', $reciver);
            })
                ->first();


            $messageThradeCount = Message_thrade::where(function ($query) use ($reciver, $user_id) {
                $query->where('sender_id', $reciver)
                    ->where('reciver_id', $user_id);
            })->orWhere(function ($query) use ($reciver, $user_id) {
                $query->where('sender_id', $user_id)
                    ->where('reciver_id', $reciver);
            })
                ->count();

            if ($messageThradeCount <= 0) {
                $messageThrade = new Message_thrade();
                $messageThrade->sender_id = auth('sanctum')->user()->id;
                $messageThrade->reciver_id = $request->reciver_id;
                $messageThrade->chatcenter = $request->messagecenter;
                $done = $messageThrade->save();
                if ($done) {
                    $chat = new Chat();
                    $chat->reciver_id = $request->reciver_id;
                    $chat->sender_id = auth('sanctum')->user()->id;
                    $chat->chatcenter = $request->messagecenter;
                    $chat->message = $request->message;
                    $chat->message_thrade = $messageThrade->id;
                    $chat->thumbsup = $request->thumbsup;
                    $chat->file = '1';
                    $chat->save();
                    $last_chat_id = $chat->id;

                    if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
                        //Data validation
                        $rules = array('multiple_files' => 'mimes:jpeg,jpg,png,gif,jfif,mp4,mov,wmv,mkv,webm,avi');
                        $validator = Validator::make($request->multiple_files, $rules);
                        if ($validator->fails()) {
                            return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
                        }

                        foreach ($request->multiple_files as $key => $media_file) {
                            $file_name = random(40);
                            $file_extention = strtolower($media_file->getClientOriginalExtension());
                            if ($file_extention == 'avi' || $file_extention == 'mp4' || $file_extention == 'webm' || $file_extention == 'mov' || $file_extention == 'wmv' || $file_extention == 'mkv') {
                                $media_file->move('storage/chat/videos/', $file_name . '.' . $file_extention);
                                $file_type = 'video';
                            } else {
                                FileUploader::upload($media_file, 'public/storage/chat/images/' . $file_name, 1000, null, 300);
                                $file_type = 'image';
                            }
                            $file_name = $file_name . '.' . $file_extention;


                            $media_file_data = array('user_id' => auth('sanctum')->user()->id, 'chat_id' => $last_chat_id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => 'public');
                            $media_file_data['created_at'] = time();
                            $media_file_data['updated_at'] = $media_file_data['created_at'];
                            Media_files::create($media_file_data);
                        }
                    }
                    $page_data['message'] = Chat::where('message_thrade', $messageThrade->id)->orderBy('id', 'DESC')->limit('1')->get();
                    $message = view('frontend.chat.single-message', $page_data)->render();
                    $url = url('/') . '/chat/inbox/' . $request->reciver_id;
                    if (isset($request->product_id) && !empty($request->product_id)) {
                        $response = array('appendElement' => '#message_body', 'content' => $message, 'clickTo' => '#messageResetBox', 'replaceUrl' => '#message_body', 'url' => $url);
                    } else {
                        $response = array('appendElement' => '#message_body', 'content' => $message, 'clickTo' => '#messageResetBox');
                    }

                    // return $response;
                }
            } else {
                $chat = new Chat();
                $chat->reciver_id = $request->reciver_id;
                $chat->sender_id = auth('sanctum')->user()->id;
                $chat->chatcenter = $request->messagecenter;
                $chat->message = $request->message;
                $chat->message_thrade = $firstmessageThrade->id;
                $chat->thumbsup = $request->thumbsup;
                $chat->file = '1';
                $chat->save();
                $last_chat_id = $chat->id;

                if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
                    //Data validation
                    $rules = array('multiple_files' => 'mimes:jpeg,jpg,png,gif,jfif,mp4,mov,wmv,mkv,webm,avi');
                    $validator = Validator::make($request->multiple_files, $rules);
                    if ($validator->fails()) {
                        return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
                    }

                    foreach ($request->multiple_files as $key => $media_file) {
                        $file_name = random(40);
                        $file_extention = strtolower($media_file->getClientOriginalExtension());
                        if ($file_extention == 'avi' || $file_extention == 'mp4' || $file_extention == 'webm' || $file_extention == 'mov' || $file_extention == 'wmv' || $file_extention == 'mkv') {
                            $media_file->move('storage/chat/videos/', $file_name . '.' . $file_extention);
                            $file_type = 'video';
                        } else {
                            FileUploader::upload($media_file, 'public/storage/chat/images/' . $file_name, 1000, null, 300);
                            $file_type = 'image';
                        }
                        $file_name = $file_name . '.' . $file_extention;


                        $media_file_data = array('user_id' => auth('sanctum')->user()->id, 'chat_id' => $last_chat_id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => 'public');

                        $media_file_data['chat_id'] = $chat->id;
                        $media_file_data['created_at'] = time();
                        $media_file_data['updated_at'] = $media_file_data['created_at'];
                        Media_files::create($media_file_data);
                    }
                }


            }
        }
        return $response;
    }
    public function thread_save(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $messageThrade = new Message_thrade();
            $messageThrade->sender_id = auth('sanctum')->user()->id;
            $messageThrade->reciver_id = $request->reciver_id;
            $messageThrade->chatcenter = $request->messagecenter;
            $done = $messageThrade->save();

        }
        return $response;
    }

    public function remove_chat(Request $request, $chat_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $chat = Chat::find($chat_id);
            $delete = $chat->delete();
            if ($delete) {
                $response['success'] = true;
                $response['message'] = 'Delete successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'not found';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }

    // public function chat_read_option(Request $request, $user_id)
    // {
    //     $token = $request->bearerToken();
    //     $response = array();

    //     if (isset($token) && $token != '') {
    //         // $user_id = auth('sanctum')->user()->id;
    //         $messageThrade = Message_thrade::whereIn('sender_id', [auth('sanctum')->user()->id, $user_id])->whereIn('reciver_id', [auth('sanctum')->user()->id, $user_id])->first();
    //         if (!empty($messageThrade)) {
    //             $done = Chat::where('message_thrade', $messageThrade->id)->where('read_status', '0')->where('reciver_id', auth('sanctum')->user()->id)->update(['read_status' => '1']);
    //             if ($done) {
    //                 $response['success'] = true;
    //                 $response['message'] = 'read successfully';
    //             } else {
    //                 $response['success'] = false;
    //                 $response['message'] = 'not found';
    //             }
    //         }
    //     } else {
    //         $response['success'] = false;
    //         $response['message'] = 'Unauthorized access';
    //     }
    //     return $response;
    // }
    // public function chat_read_option(Request $request, $user_id)
    // {
    //     $token = $request->bearerToken();
    //     $response = array();

    //     if (isset($token) && $token != '') {
    //         $authUserId = auth('sanctum')->user()->id;

    //         // Retrieve message thread
    //         $messageThrade = Message_thrade::whereIn('sender_id', [$authUserId, $user_id])
    //             ->whereIn('reciver_id', [$authUserId, $user_id])
    //             ->first();

    //         if (!empty($messageThrade)) {
    //             // Mark messages as read
    //             $done = Chat::where('message_thrade', $messageThrade->id)
    //                 ->where('read_status', '0')
    //                 ->where('reciver_id', $authUserId)
    //                 ->update(['read_status' => '1']);

    //             if ($done !== false) {
    //                 $response['success'] = true;
    //                 $response['message'] = 'Messages marked as read successfully';
    //             } else {
    //                 $response['success'] = false;
    //                 $response['message'] = 'Failed to mark messages as read';
    //             }
    //         } else {
    //             $response['success'] = false;
    //             $response['message'] = 'Message thread not found';
    //         }
    //     } else {
    //         $response['success'] = false;
    //         $response['message'] = 'Unauthorized access';
    //     }

    //     return $response;
    // }

    public function chat_read_option(Request $request, $user_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $authUserId = auth('sanctum')->user()->id;

            // Retrieve message thread
            $messageThrade = Message_thrade::whereIn('sender_id', [$authUserId, $user_id])
                ->whereIn('reciver_id', [$authUserId, $user_id])
                ->first();

            if (!empty($messageThrade)) {
                // Mark messages as read
                $done = Chat::where('message_thrade', $messageThrade->id)
                    ->where('read_status', '0')
                    ->where('reciver_id', $authUserId)
                    ->update(['read_status' => '1']);

                if ($done) {
                    $response['success'] = true;
                    $response['message'] = 'Messages marked as read successfully';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Failed to mark messages as read';
                }
            } else {
                $response['success'] = false;
                $response['message'] = 'Message thread not found';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }
    public function react_chat(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {

            $form_data = $request->all();
            $chat = Chat::find($form_data['messageId']);

            $reactionValue = $chat->react;

            if ($form_data['react'] == "none") {
                $chat->react = null;
            } else {

                $chat->react = $form_data['react'];
            }
            $chat->save();


        }
        return $response;
    }
    public function all_user(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;
            $user = User::where('id', '!=', $user_id)->where('email_verified_at', '!=', null)->get();
            $all_user = [];

            foreach ($user as $users) {
                $profile_id = 'not_chat'; // Initialize profile ID as 0
                $msgthread_id = 0; // Initialize profile ID as 0

                // Get chat messages involving the current user
                $chats = Message_thrade::where('reciver_id', $user_id)
                    ->orWhere('sender_id', $user_id)
                    ->get();

                // Loop through chat messages to find matching profile ID
                foreach ($chats as $chat) {
                    if ($chat->reciver_id == $users->id || $chat->sender_id == $users->id) {
                        // Set the profile ID to the matching user ID
                        $profile_id = "chat";
                        $msgthread_id = $chat->id;
                        // Break the loop once a match is found
                        break;
                    }
                }

                $all_user[] = [
                    "id" => $users->id,
                    "isChat" => $profile_id,
                    "msgthread_id" => $msgthread_id,
                    "name" => $users->name,
                    "photo" => get_user_images($users->photo),
                ];
            }
            $response = $all_user;
        }
        return $response;
    }
    public function invite(Request $request, $group_event_id)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            // Retrieve all users with verified email
            $users = User::whereNotNull('email_verified_at')->get();
            $all_users = [];

            foreach ($users as $user) {
                $group_status = 'not_invited'; // Initialize group status as not invited
                $event_status = 'not_invited'; // Initialize event status as not invited
                $group_id = '';
                $event_id = '';

                // Check if the user is invited to the group
                $group_invite = Invite::where('group_id', $group_event_id)
                    ->where(function ($query) use ($user_id, $user) {
                        $query->where('invite_reciver_id', $user->id)
                            ->orWhere('invite_sender_id', $user->id);
                    })
                    ->first();

                // If the user is invited to the group, update group status and group ID
                if ($group_invite) {
                    $group_status = 'invited';
                    $group_id = $group_event_id;
                }

                // Check if the user is invited to the event
                $event_invite = Invite::where('event_id', $group_event_id)
                    ->where(function ($query) use ($user_id, $user) {
                        $query->where('invite_reciver_id', $user->id)
                            ->orWhere('invite_sender_id', $user->id);
                    })
                    ->first();

                // If the user is invited to the event, update event status and event ID
                if ($event_invite) {
                    $event_status = 'invited';
                    $event_id = $group_event_id;
                }

                // Store user details along with invitation status
                $all_users[] = [
                    "id" => $user->id,
                    "group_id" => $group_id,
                    "group" => $group_status,
                    "event_id" => $event_id,
                    "event" => $event_status,
                    "name" => $user->name,
                    "photo" => get_user_images($user->photo),
                ];
            }

            $response = $all_users;
        }

        return $response;
    }

    public function group_invition(Request $request)
    {
        // return $request->all();
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            // $invited_group_users_id = $request->invited_group_users_id;
            // $count = count($invited_group_users_id);

            // for ($i = 0; $i < $count; $i++) {
            $invite = new Invite();
            $invite->invite_sender_id = $user_id;
            $invite->invite_reciver_id = $request->invited_user_id;
            $invite->is_accepted = '0';
            $invite->group_id = $request->group_id;
            $invite->save();

            $notify = new Notification();
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $request->invited_user_id;
            $notify->type = 'group';
            $notify->group_id = $request->group_id;
            $notify->save();

            // }
            $response['success'] = true;
            $response['message'] = 'Invitations sent successfully';
        }
        return $response;
    }
    public function event_invition(Request $request)
    {
        // return $request->all();
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            // $invited_event_users_id = $request->invited_event_users_id;
            // $count = count($invited_event_users_id);

            // for ($i = 0; $i < $count; $i++) {
            $invite = new Invite();
            $invite->invite_sender_id = $user_id;
            $invite->invite_reciver_id = $request->invited_user_id;
            $invite->is_accepted = '0';
            $invite->event_id = $request->event_id;
            $invite->save();

            $notify = new Notification();
            $notify->sender_user_id = $user_id;
            $notify->reciver_user_id = $request->invited_user_id;
            $notify->type = 'event';
            $notify->event_id = $request->event_id;
            $notify->save();

            // }
            $response['success'] = true;
            $response['message'] = 'Invitations sent successfully';
        }
        return $response;
    }

    public function count_notification(Request $request)
    {
        $token = $request->bearerToken();
        $response = array();

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            $new_notification = Notification::where('reciver_user_id', $user_id)->where('status', '0')
                ->orderBy('id', 'DESC')->count();

            $chats = Chat::where('reciver_id', $user_id)->where('read_status', 0)->count();

            $response["notification"] = $new_notification;
            $response["chat"] = $chats;

        } else {
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }
        return $response;
    }
    // public function roomName(Request $request)
    // {
    //     $token = $request->bearerToken();
    //     $response = array();

    //     if (isset($token) && $token != '') {
    //         $user_id = auth('sanctum')->user()->id;

    //         // $new_notification = Notification::where('reciver_user_id', $user_id)->where('status', '0')
    //         //     ->orderBy('id', 'DESC')->count();

    //         // $chats = Chat::where('reciver_id', $user_id)->where('read_status', 0)->count();
    //         $jitsis = json_decode(get_settings('zitsi_configuration'), true);
    //         $room = get_settings('system_name');
    //         $roomName = $jitsis->jitsi_app_id + '/' + $room;

    //         $response = $roomName;



    //     } else {
    //         $response['success'] = false;
    //         $response['message'] = 'Unauthorized access';
    //     }
    //     return $response;
    // }

    public function roomName(Request $request)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            // Fetching necessary settings
            $jitsiConfig = json_decode(get_settings('zitsi_configuration'), true);

            if ($jitsiConfig) {
                $jitsiAppId = $jitsiConfig['jitsi_app_id'];
                $jitsiToken = $jitsiConfig['jitsi_jwt'];
                $roomName = $jitsiAppId . '/' . get_settings('system_name');


                $response['jitsiAppId'] = $jitsiAppId;
                $response['jitsiToken'] = $jitsiToken;
                $response['roomName'] = $roomName;
            } else {
                // Error handling if settings are not available
                $response['success'] = false;
                $response['message'] = 'zitci not found not found';
            }
        } else {
            // Error handling for unauthorized access
            $response['success'] = false;
            $response['message'] = 'Unauthorized access';
        }

        return $response;
    }
    public function about_policy(Request $request)
    {
        $token = $request->bearerToken();
        $response = [];

        if (isset($token) && $token != '') {
            $user_id = auth('sanctum')->user()->id;

            // Fetching necessary settings
            // $setting = Setting::first();

            // if ($setting) {
            $response['about'] = Setting::where('type', 'about')->value('description');
            $response['policy'] = Setting::where('type', 'policy')->value('description');

        }
        // else {
        //     // Error handling for unauthorized access
        //     $response['success'] = false;
        //     $response['message'] = 'Unauthorized access';
        // }

        return $response;
    }










}
