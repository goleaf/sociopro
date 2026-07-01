<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/data', [ApiController::class,'userdata']);

Route::post('/login', [ApiController::class,'login']);
Route::post('/signup', [ApiController::class,'signup']);
Route::post('/forgot_password', [ApiController::class,'forgot_password']);
Route::post('/update_password', [ApiController::class,'update_password']);

Route::get('/user', [ApiController::class,'user']);
Route::get('/user_post', [ApiController::class,'user_post']);

Route::get('/friends', [ApiController::class,'friends']);
Route::post('/add_friend/{id}', [ApiController::class,'add_friend']);
Route::post('/unfriend/{id}', [ApiController::class,'unfriend']);
Route::post('/follow/{id}', [ApiController::class,'follow']);
Route::post('/unfollow/{id}', [ApiController::class,'unfollow']);
Route::get('/friend_request', [ApiController::class,'friend_request']);

Route::get('/getPostReactions/{postId}', [ApiController::class,'getPostReactions']);
Route::get('/timeline', [ApiController::class,'timeline']);
Route::get('/load_timeline', [ApiController::class,'load_timeline']);
Route::get('/stories', [ApiController::class,'stories']);
Route::post('/create_story', [ApiController::class,'create_story']);
Route::post('/reaction', [ApiController::class,'reaction']);
Route::post('/create_post', [ApiController::class,'create_post']);
Route::post('/edit_post/{id}', [ApiController::class,'edit_post']);
Route::post('/delete_post/{id}', [ApiController::class,'delete_post']);
Route::get('/post_media_file/{id}', [ApiController::class,'post_media_file']);
Route::post('/delete_media_file/{id}', [ApiController::class,'delete_media_file']);
Route::post('/save_post_report', [ApiController::class,'save_post_report']);

Route::get('/profile', [ApiController::class,'profile']);
Route::get('/other_profile/{id}', [ApiController::class,'other_profile']);
Route::post('/edit_profile', [ApiController::class,'edit_profile']);
Route::post('/update_profile_pic', [ApiController::class,'update_profile_pic']);
Route::post('/update_cover_pic', [ApiController::class,'update_cover_pic']);
Route::get('/profile_photos', [ApiController::class,'profile_photos']);
Route::get('/other_profile_photos/{id}', [ApiController::class,'other_profile_photos']);
Route::get('/single_post/{post_id}', [ApiController::class,'single_post']);
Route::get('/profile_videos', [ApiController::class,'profile_videos']);

Route::post('/create_album', [ApiController::class,'create_album']);
Route::get('/album_photos/{id}', [ApiController::class,'album_photos']);
Route::post('/delete_album/{id}', [ApiController::class,'delete_album']);
Route::post('/add_album_image', [ApiController::class,'add_album_image']);

Route::post('/post_comment', [ApiController::class,'post_comment']);
Route::post('/comment_reaction', [ApiController::class,'comment_reaction']);
Route::get('/get_comment/{postId}', [ApiController::class,'get_comment']);
Route::post('/comment_delete/{comment_id}', [ApiController::class,'comment_delete']);

Route::get('/groups', [ApiController::class,'groups']);
Route::get('/groups_details/{id}', [ApiController::class,'groups_details']);
Route::post('/create_group', [ApiController::class,'create_group']);
Route::post('/update_group/{group_id}', [ApiController::class,'update_group']);
Route::post('/updatecoverphoto_group/{group_id}', [ApiController::class,'updatecoverphoto_group']);
Route::post('/group_invition', [ApiController::class,'group_invition']);
Route::post('/groups_join/{id}', [ApiController::class,'groups_join']);
Route::post('/groups_join_remove/{id}', [ApiController::class,'groups_join_remove']);
Route::get('/groups_discussion/{group_id}', [ApiController::class,'groups_discussion']);
Route::get('/groups_people/{group_id}', [ApiController::class,'groups_people']);
Route::get('/groups_event/{group_id}', [ApiController::class,'groups_event']);
Route::get('/group_photos/{group_id}', [ApiController::class,'group_photos']);

Route::get('/pages', [ApiController::class,'pages']);
Route::get('/pages_details/{id}', [ApiController::class,'pages_details']);
Route::get('/page_category', [ApiController::class,'page_category']);
Route::post('/pages_update/{id}', [ApiController::class,'pages_update']);
Route::post('/page_delete/{id}', [ApiController::class,'page_delete']);
Route::post('/pages_create', [ApiController::class,'pages_create']);
Route::post('/page_like/{id}', [ApiController::class,'page_like']);
Route::post('/page_dislike/{id}', [ApiController::class,'page_dislike']);
Route::post('/update_page_coverphoto/{id}', [ApiController::class,'update_page_coverphoto']);
Route::get('/pages_timeline/{page_id}', [ApiController::class,'pages_timeline']);
Route::get('/page_photos/{id}', [ApiController::class,'page_photos']);

Route::get('/marketplace', [ApiController::class,'marketplace']);
Route::get('/marketplace_category', [ApiController::class,'marketplace_category']);
Route::get('/marketplace_brand', [ApiController::class,'marketplace_brand']);
Route::get('/currencies', [ApiController::class,'currencies']);
Route::get('/filter', [ApiController::class,'filter']);
Route::post('/create_marketplace', [ApiController::class,'create_marketplace']);
Route::post('/update_marketplace/{id}', [ApiController::class,'update_marketplace']);
Route::post('/delete_marketplace/{product_id}', [ApiController::class,'delete_marketplace']);
Route::post('/save_for_later/{id}', [ApiController::class,'save_for_later']);
Route::post('/unsave_for_later/{id}', [ApiController::class,'unsave_for_later']);

Route::get('/videos', [ApiController::class,'videos']);
Route::post('/create_videos', [ApiController::class,'create_videos']);
Route::post('/view_videos/{id}', [ApiController::class,'view_videos']);
Route::post('/save_for_later_videos/{id}', [ApiController::class,'save_for_later_videos']);
Route::post('/unsave_for_later_videos/{id}', [ApiController::class,'unsave_for_later_videos']);
Route::post('/delete_videos/{id}', [ApiController::class,'delete_videos']);

Route::get('/events', [ApiController::class,'events']);
Route::get('/events_details/{id}', [ApiController::class,'events_details']);
Route::post('/create_event', [ApiController::class,'create_event']);
Route::post('/event_invition', [ApiController::class,'event_invition']);
Route::post('/update_event/{id}', [ApiController::class,'update_event']);
Route::post('/delete_event/{id}', [ApiController::class,'delete_event']);
Route::post('/event_going/{id}', [ApiController::class,'event_going']);
Route::post('/event_notgoing/{id}', [ApiController::class,'event_notgoing']);
Route::post('/event_interested/{id}', [ApiController::class,'event_interested']);
Route::post('/event_notinterested/{id}', [ApiController::class,'event_notinterested']);
Route::post('/event_cancel/{id}', [ApiController::class,'event_cancel']);
Route::get('/events_discussion/{event_id}', [ApiController::class,'events_discussion']);

Route::get('/blogs', [ApiController::class,'blogs']);
Route::get('/blog_category', [ApiController::class,'blog_category']);
Route::post('/create_blogs', [ApiController::class,'create_blogs']);
Route::post('/update_blogs/{id}', [ApiController::class,'update_blogs']);
Route::post('/blog_view/{id}', [ApiController::class,'blog_view']);
Route::post('/blog_delete/{id}', [ApiController::class,'blog_delete']);

Route::get('/paid_content', [ApiController::class,'paid_content']);
Route::get('/paid_content_package', [ApiController::class,'paid_content_package']);

Route::get('/jobs', [ApiController::class,'jobs']);
Route::post('/create_jobs', [ApiController::class,'create_jobs']);
Route::post('/update_jobs/{id}', [ApiController::class,'update_jobs']);
Route::post('/job_delete/{id}', [ApiController::class,'job_delete']);
Route::get('/job_wishlist', [ApiController::class,'job_wishlist']);
Route::post('/job_add_wishlist/{id}', [ApiController::class,'job_add_wishlist']);
Route::post('/JobApply/{id}', [ApiController::class,'JobApply']);

Route::get('/fundraisers', [ApiController::class,'fundraisers']);
Route::post('/create_fundraiser', [ApiController::class,'create_fundraiser']);
Route::post('/update_fundraiser/{id}', [ApiController::class,'update_fundraiser']);
Route::post('/invited_fundraiser/{invited_friend_id}/{fundraiser_id}', [ApiController::class,'invited_fundraiser']);

Route::get('/notifications', [ApiController::class,'notifications']);
Route::post('/accept_friend_notification/{id}', [ApiController::class,'accept_friend_notification']);
Route::post('/decline_friend_notification/{id}', [ApiController::class,'decline_friend_notification']);
Route::post('/accept_group_notification/{id}/{group_id}', [ApiController::class,'accept_group_notification']);
Route::post('/decline_group_notification/{id}/{group_id}', [ApiController::class,'decline_group_notification']);
Route::post('/accept_event_notification/{id}/{event_id}', [ApiController::class,'accept_event_notification']);
Route::post('/decline_event_notification/{id}/{event_id}', [ApiController::class,'decline_event_notification']);
Route::post('/mark_as_read/{id}', [ApiController::class,'mark_as_read']);
Route::post('/accept_fundraiser_notification/{id}/{fundraiser_id}', [ApiController::class,'accept_fundraiser_notification']);
Route::post('/decline_fundraiser_notification/{id}/{fundraiser_id}', [ApiController::class,'decline_fundraiser_notification']);

Route::get('/chat', [ApiController::class,'chat']);
Route::get('/chat_msg/{msg_thrade}', [ApiController::class,'chat_msg']);
Route::post('/chat_save', [ApiController::class,'chat_save']);
Route::post('/thread_save', [ApiController::class,'thread_save']);
Route::post('/remove_chat/{chat_id}', [ApiController::class,'remove_chat']);
Route::post('/chat_read_option/{user_id}', [ApiController::class,'chat_read_option']);
Route::post('/react_chat', [ApiController::class,'react_chat']);


Route::get('/all_user', [ApiController::class,'all_user']);
Route::get('/invite/{group_event_id}', [ApiController::class,'invite']);
Route::get('/count_notification', [ApiController::class,'count_notification']);


Route::get('/roomName', [ApiController::class,'roomName']);
Route::get('/about_policy', [ApiController::class,'about_policy']);








