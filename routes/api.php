<?php

use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use App\Http\Controllers\ApiController;
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

Route::name('api.')->group(function () {
    Route::get('/data', [ApiController::class, 'userdata'])->name('data.index');

    Route::post('/login', [ApiController::class, 'login'])->name('auth.login');
    Route::post('/signup', [ApiController::class, 'signup'])->name('auth.signup');
    Route::post('/forgot_password', [ApiController::class, 'forgot_password'])->name('password.forgot');
    Route::post('/update_password', [ApiController::class, 'update_password'])->name('password.update');

    Route::get('/user', [ApiController::class, 'user'])->name('me.feed');
    Route::get('/user_post', [ApiController::class, 'user_post'])->name('me.posts');

    Route::get('/friends', [ApiController::class, 'friends'])->name('friends.index');
    Route::post('/add_friend/{id}', [ApiController::class, 'add_friend'])->name('friends.store');
    Route::post('/unfriend/{id}', [ApiController::class, 'unfriend'])->name('friends.destroy');
    Route::post('/follow/{id}', [ApiController::class, 'follow'])->name('follows.store');
    Route::post('/unfollow/{id}', [ApiController::class, 'unfollow'])->name('follows.destroy');
    Route::get('/friend_request', [ApiController::class, 'friend_request'])->name('friend_requests.index');

    Route::get('/getPostReactions/{postId}', [ApiController::class, 'getPostReactions'])->name('post_reactions.index');
    Route::get('/timeline', [ApiController::class, 'timeline'])->name('timeline.index');
    Route::get('/load_timeline', [ApiController::class, 'load_timeline'])->name('timeline.load');
    Route::get('/stories', [ApiController::class, 'stories'])->name('stories.index');
    Route::post('/create_story', [ApiController::class, 'create_story'])->name('stories.store');
    Route::post('/reaction', [ApiController::class, 'reaction'])->name('reactions.store');
    Route::post('/create_post', [ApiController::class, 'create_post'])->name('posts.store');
    Route::post('/edit_post/{id}', [ApiController::class, 'edit_post'])->name('posts.update');
    Route::post('/delete_post/{id}', [ApiController::class, 'delete_post'])->name('posts.destroy');
    Route::get('/post_media_file/{id}', [ApiController::class, 'post_media_file'])->name('posts.media.show');
    Route::post('/delete_media_file/{id}', [ApiController::class, 'delete_media_file'])->name('posts.media.destroy');
    Route::post('/save_post_report', [ApiController::class, 'save_post_report'])->name('post_reports.store');

    Route::get('/profile', [ApiController::class, 'profile'])->name('me.profile.show');
    Route::get('/other_profile/{id}', [ApiController::class, 'other_profile'])->name('profiles.show');
    Route::post('/edit_profile', [ApiController::class, 'edit_profile'])->name('me.profile.update');
    Route::post('/update_profile_pic', [ApiController::class, 'update_profile_pic'])->name('me.profile_photo.update');
    Route::post('/update_cover_pic', [ApiController::class, 'update_cover_pic'])->name('me.cover_photo.update');
    Route::get('/profile_photos', [ApiController::class, 'profile_photos'])->name('me.photos.index');
    Route::get('/other_profile_photos/{id}', [ApiController::class, 'other_profile_photos'])->name('profiles.photos.index');
    Route::get('/single_post/{post_id}', [ApiController::class, 'single_post'])->name('posts.show');
    Route::get('/profile_videos', [ApiController::class, 'profile_videos'])->name('me.videos.index');

    Route::post('/create_album', [ApiController::class, 'create_album'])->name('albums.store');
    Route::get('/album_photos/{id}', [ApiController::class, 'album_photos'])->name('albums.photos.index');
    Route::post('/delete_album/{id}', [ApiController::class, 'delete_album'])->name('albums.destroy');
    Route::post('/add_album_image', [ApiController::class, 'add_album_image'])->name('albums.images.store');

    Route::post('/post_comment', [ApiController::class, 'post_comment'])->name('comments.store');
    Route::post('/comment_reaction', [ApiController::class, 'comment_reaction'])->name('comment_reactions.store');
    Route::get('/get_comment/{postId}', [ApiController::class, 'get_comment'])->name('comments.index');
    Route::post('/comment_delete/{comment_id}', [ApiController::class, 'comment_delete'])->name('comments.destroy');

    Route::get('/groups', [ApiController::class, 'groups'])->name('groups.index');
    Route::get('/groups_details/{id}', [ApiController::class, 'groups_details'])->name('groups.show');
    Route::post('/create_group', [ApiController::class, 'create_group'])->name('groups.store');
    Route::post('/update_group/{group_id}', [ApiController::class, 'update_group'])->name('groups.update');
    Route::post('/updatecoverphoto_group/{group_id}', [ApiController::class, 'updatecoverphoto_group'])->name('groups.cover.update');
    Route::post('/group_invition', [ApiController::class, 'group_invition'])->name('groups.invitations.store');
    Route::post('/groups_join/{id}', [ApiController::class, 'groups_join'])->name('groups.members.store');
    Route::post('/groups_join_remove/{id}', [ApiController::class, 'groups_join_remove'])->name('groups.members.destroy');
    Route::get('/groups_discussion/{group_id}', [ApiController::class, 'groups_discussion'])->name('groups.discussions.index');
    Route::get('/groups_people/{group_id}', [ApiController::class, 'groups_people'])->name('groups.people.index');
    Route::get('/groups_event/{group_id}', [ApiController::class, 'groups_event'])->name('groups.events.index');
    Route::get('/group_photos/{group_id}', [ApiController::class, 'group_photos'])->name('groups.photos.index');

    Route::get('/pages', [ApiController::class, 'pages'])->name('pages.index');
    Route::get('/pages_details/{id}', [ApiController::class, 'pages_details'])->name('pages.show');
    Route::get('/page_category', [ApiController::class, 'page_category'])->name('pages.categories.index');
    Route::post('/pages_update/{id}', [ApiController::class, 'pages_update'])->name('pages.update');
    Route::post('/page_delete/{id}', [ApiController::class, 'page_delete'])->name('pages.destroy');
    Route::post('/pages_create', [ApiController::class, 'pages_create'])->name('pages.store');
    Route::post('/page_like/{id}', [ApiController::class, 'page_like'])->name('pages.likes.store');
    Route::post('/page_dislike/{id}', [ApiController::class, 'page_dislike'])->name('pages.likes.destroy');
    Route::post('/update_page_coverphoto/{id}', [ApiController::class, 'update_page_coverphoto'])->name('pages.cover.update');
    Route::get('/pages_timeline/{page_id}', [ApiController::class, 'pages_timeline'])->name('pages.timeline.index');
    Route::get('/page_photos/{id}', [ApiController::class, 'page_photos'])->name('pages.photos.index');

    Route::get('/marketplace', [ApiController::class, 'marketplace'])->name('marketplace.index');
    Route::get('/marketplace_category', [ApiController::class, 'marketplace_category'])->name('marketplace.categories.index');
    Route::get('/marketplace_brand', [ApiController::class, 'marketplace_brand'])->name('marketplace.brands.index');
    Route::get('/currencies', [ApiController::class, 'currencies'])->name('currencies.index');
    Route::get('/filter', [ApiController::class, 'filter'])->name('marketplace.filter');
    Route::post('/create_marketplace', [ApiController::class, 'create_marketplace'])->name('marketplace.store');
    Route::post('/update_marketplace/{id}', [ApiController::class, 'update_marketplace'])->name('marketplace.update');
    Route::post('/delete_marketplace/{product_id}', [ApiController::class, 'delete_marketplace'])->name('marketplace.destroy');
    Route::post('/save_for_later/{id}', [ApiController::class, 'save_for_later'])->name('marketplace.saves.store');
    Route::post('/unsave_for_later/{id}', [ApiController::class, 'unsave_for_later'])->name('marketplace.saves.destroy');

    Route::get('/videos', [ApiController::class, 'videos'])->name('videos.index');
    Route::post('/create_videos', [ApiController::class, 'create_videos'])->name('videos.store');
    Route::post('/view_videos/{id}', [ApiController::class, 'view_videos'])->name('videos.views.store');
    Route::post('/save_for_later_videos/{id}', [ApiController::class, 'save_for_later_videos'])->name('videos.saves.store');
    Route::post('/unsave_for_later_videos/{id}', [ApiController::class, 'unsave_for_later_videos'])->name('videos.saves.destroy');
    Route::post('/delete_videos/{id}', [ApiController::class, 'delete_videos'])->name('videos.destroy');

    Route::get('/events', [ApiController::class, 'events'])->name('events.index');
    Route::get('/events_details/{id}', [ApiController::class, 'events_details'])->name('events.show');
    Route::post('/create_event', [ApiController::class, 'create_event'])->name('events.store');
    Route::post('/event_invition', [ApiController::class, 'event_invition'])->name('events.invitations.store');
    Route::post('/update_event/{id}', [ApiController::class, 'update_event'])->name('events.update');
    Route::post('/delete_event/{id}', [ApiController::class, 'delete_event'])->name('events.destroy');
    Route::post('/event_going/{id}', [ApiController::class, 'event_going'])->name('events.going.store');
    Route::post('/event_notgoing/{id}', [ApiController::class, 'event_notgoing'])->name('events.going.destroy');
    Route::post('/event_interested/{id}', [ApiController::class, 'event_interested'])->name('events.interested.store');
    Route::post('/event_notinterested/{id}', [ApiController::class, 'event_notinterested'])->name('events.interested.destroy');
    Route::post('/event_cancel/{id}', [ApiController::class, 'event_cancel'])->name('events.cancel');
    Route::get('/events_discussion/{event_id}', [ApiController::class, 'events_discussion'])->name('events.discussions.index');

    Route::get('/blogs', [ApiController::class, 'blogs'])->name('blogs.index');
    Route::get('/blog_category', [ApiController::class, 'blog_category'])->name('blogs.categories.index');
    Route::post('/create_blogs', [ApiController::class, 'create_blogs'])->name('blogs.store');
    Route::post('/update_blogs/{id}', [ApiController::class, 'update_blogs'])->name('blogs.update');
    Route::post('/blog_view/{id}', [ApiController::class, 'blog_view'])->name('blogs.views.store');
    Route::post('/blog_delete/{id}', [ApiController::class, 'blog_delete'])->name('blogs.destroy');

    Route::get('/paid_content', [ApiController::class, 'paid_content'])->name('paid_content.index');
    Route::get('/paid_content_package', [ApiController::class, 'paid_content_package'])->name('paid_content.packages.index');

    Route::get('/jobs', [ApiController::class, 'jobs'])->name('jobs.index');
    Route::post('/create_jobs', [ApiController::class, 'create_jobs'])->name('jobs.store');
    Route::post('/update_jobs/{id}', [ApiController::class, 'update_jobs'])->name('jobs.update');
    Route::post('/job_delete/{id}', [ApiController::class, 'job_delete'])->name('jobs.destroy');
    Route::get('/job_wishlist', [ApiController::class, 'job_wishlist'])->name('jobs.wishlist.index');
    Route::post('/job_add_wishlist/{id}', [ApiController::class, 'job_add_wishlist'])->name('jobs.wishlist.store');
    Route::post('/JobApply/{id}', [ApiController::class, 'JobApply'])->name('jobs.applications.store');

    Route::get('/fundraisers', [ApiController::class, 'fundraisers'])->name('fundraisers.index');
    Route::post('/create_fundraiser', [ApiController::class, 'create_fundraiser'])->name('fundraisers.store');
    Route::post('/update_fundraiser/{id}', [ApiController::class, 'update_fundraiser'])->name('fundraisers.update');
    Route::post('/invited_fundraiser/{invited_friend_id}/{fundraiser_id}', [ApiController::class, 'invited_fundraiser'])->name('fundraisers.invitations.store');

    Route::get('/notifications', [ApiNotificationController::class, 'notifications'])->name('notifications.index');
    Route::post('/accept_friend_notification/{id}', [ApiNotificationController::class, 'accept_friend_notification'])->name('notifications.friends.accept');
    Route::post('/decline_friend_notification/{id}', [ApiNotificationController::class, 'decline_friend_notification'])->name('notifications.friends.decline');
    Route::post('/accept_group_notification/{id}/{group_id}', [ApiNotificationController::class, 'accept_group_notification'])->name('notifications.groups.accept');
    Route::post('/decline_group_notification/{id}/{group_id}', [ApiNotificationController::class, 'decline_group_notification'])->name('notifications.groups.decline');
    Route::post('/accept_event_notification/{id}/{event_id}', [ApiNotificationController::class, 'accept_event_notification'])->name('notifications.events.accept');
    Route::post('/decline_event_notification/{id}/{event_id}', [ApiNotificationController::class, 'decline_event_notification'])->name('notifications.events.decline');
    Route::post('/mark_as_read/{id}', [ApiNotificationController::class, 'mark_as_read'])->name('notifications.read');
    Route::post('/accept_fundraiser_notification/{id}/{fundraiser_id}', [ApiNotificationController::class, 'accept_fundraiser_notification'])->name('notifications.fundraisers.accept');
    Route::post('/decline_fundraiser_notification/{id}/{fundraiser_id}', [ApiNotificationController::class, 'decline_fundraiser_notification'])->name('notifications.fundraisers.decline');

    Route::get('/chat', [ApiController::class, 'chat'])->name('chat.index');
    Route::get('/chat_msg/{msg_thrade}', [ApiController::class, 'chat_msg'])->name('chat.messages.index');
    Route::post('/chat_save', [ApiController::class, 'chat_save'])->name('chat.messages.store');
    Route::post('/thread_save', [ApiController::class, 'thread_save'])->name('chat.threads.store');
    Route::post('/remove_chat/{chat_id}', [ApiController::class, 'remove_chat'])->name('chat.messages.destroy');
    Route::post('/chat_read_option/{user_id}', [ApiController::class, 'chat_read_option'])->name('chat.read.store');
    Route::post('/react_chat', [ApiController::class, 'react_chat'])->name('chat.reactions.store');

    Route::get('/all_user', [ApiController::class, 'all_user'])->name('users.index');
    Route::get('/invite/{group_event_id}', [ApiController::class, 'invite'])->name('invitations.show');
    Route::get('/count_notification', [ApiController::class, 'count_notification'])->name('notifications.count');

    Route::get('/roomName', [ApiController::class, 'roomName'])->name('rooms.name');
    Route::get('/about_policy', [ApiController::class, 'about_policy'])->name('policies.about');
});
