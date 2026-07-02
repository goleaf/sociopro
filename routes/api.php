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
    // Legacy unversioned API surface. Add versioned groups beside this one only
    // after client migration tests prove the public URLs remain supported.
    Route::controller(ApiController::class)->group(function () {
        Route::get('/data', 'userdata')->middleware('throttle:api-expensive')->name('data.index');

        Route::post('/login', 'login')->middleware('throttle:api-token')->name('auth.login');
        Route::post('/signup', 'signup')->middleware('throttle:api-registration')->name('auth.signup');
        Route::post('/forgot_password', 'forgot_password')->middleware('throttle:api-password-reset')->name('password.forgot');
    });

    Route::middleware(['api.token', 'throttle:api-authenticated'])->group(function () {
        Route::controller(ApiController::class)->group(function () {
            Route::post('/logout', 'logout')->name('auth.logout');
            Route::post('/update_password', 'update_password')->name('password.update');

            Route::get('/user', 'user')->middleware('throttle:api-expensive')->name('me.feed');
            Route::get('/user_post', 'user_post')->middleware('throttle:api-expensive')->name('me.posts');

            Route::get('/friends', 'friends')->name('friends.index');
            Route::post('/add_friend/{id}', 'add_friend')->name('friends.store');
            Route::post('/unfriend/{id}', 'unfriend')->name('friends.destroy');
            Route::post('/follow/{id}', 'follow')->name('follows.store');
            Route::post('/unfollow/{id}', 'unfollow')->name('follows.destroy');
            Route::get('/friend_request', 'friend_request')->name('friend_requests.index');

            Route::get('/getPostReactions/{postId}', 'getPostReactions')->name('post_reactions.index');
            Route::get('/timeline', 'timeline')->middleware('throttle:api-expensive')->name('timeline.index');
            Route::get('/load_timeline', 'load_timeline')->middleware('throttle:api-expensive')->name('timeline.load');
            Route::get('/stories', 'stories')->name('stories.index');
            Route::post('/create_story', 'create_story')->name('stories.store');
            Route::post('/reaction', 'reaction')->name('reactions.store');
            Route::post('/create_post', 'create_post')->name('posts.store');
            Route::post('/edit_post/{id}', 'edit_post')->name('posts.update');
            Route::post('/delete_post/{id}', 'delete_post')->name('posts.destroy');
            Route::get('/post_media_file/{id}', 'post_media_file')->name('posts.media.show');
            Route::post('/delete_media_file/{id}', 'delete_media_file')->name('posts.media.destroy');
            Route::post('/save_post_report', 'save_post_report')->name('post_reports.store');

            Route::get('/profile', 'profile')->name('me.profile.show');
            Route::get('/other_profile/{id}', 'other_profile')->name('profiles.show');
            Route::post('/edit_profile', 'edit_profile')->name('me.profile.update');
            Route::post('/update_profile_pic', 'update_profile_pic')->name('me.profile_photo.update');
            Route::post('/update_cover_pic', 'update_cover_pic')->name('me.cover_photo.update');
            Route::get('/profile_photos', 'profile_photos')->name('me.photos.index');
            Route::get('/other_profile_photos/{id}', 'other_profile_photos')->name('profiles.photos.index');
            Route::get('/single_post/{post_id}', 'single_post')->name('posts.show');
            Route::get('/profile_videos', 'profile_videos')->name('me.videos.index');

            Route::post('/create_album', 'create_album')->name('albums.store');
            Route::get('/album_photos/{id}', 'album_photos')->name('albums.photos.index');
            Route::post('/delete_album/{id}', 'delete_album')->name('albums.destroy');
            Route::post('/add_album_image', 'add_album_image')->name('albums.images.store');

            Route::post('/post_comment', 'post_comment')->name('comments.store');
            Route::post('/comment_reaction', 'comment_reaction')->name('comment_reactions.store');
            Route::get('/get_comment/{postId}', 'get_comment')->name('comments.index');
            Route::post('/comment_delete/{comment_id}', 'comment_delete')->name('comments.destroy');

            Route::get('/groups', 'groups')->name('groups.index');
            Route::get('/groups_details/{id}', 'groups_details')->name('groups.show');
            Route::post('/create_group', 'create_group')->name('groups.store');
            Route::post('/update_group/{group_id}', 'update_group')->name('groups.update');
            Route::post('/updatecoverphoto_group/{group_id}', 'updatecoverphoto_group')->name('groups.cover.update');
            Route::post('/group_invition', 'group_invition')->name('groups.invitations.store');
            Route::post('/groups_join/{id}', 'groups_join')->name('groups.members.store');
            Route::post('/groups_join_remove/{id}', 'groups_join_remove')->name('groups.members.destroy');
            Route::get('/groups_discussion/{group_id}', 'groups_discussion')->name('groups.discussions.index');
            Route::get('/groups_people/{group_id}', 'groups_people')->name('groups.people.index');
            Route::get('/groups_event/{group_id}', 'groups_event')->name('groups.events.index');
            Route::get('/group_photos/{group_id}', 'group_photos')->name('groups.photos.index');

            Route::get('/pages', 'pages')->name('pages.index');
            Route::get('/pages_details/{id}', 'pages_details')->name('pages.show');
            Route::get('/page_category', 'page_category')->name('pages.categories.index');
            Route::post('/pages_update/{id}', 'pages_update')->name('pages.update');
            Route::post('/page_delete/{id}', 'page_delete')->name('pages.destroy');
            Route::post('/pages_create', 'pages_create')->name('pages.store');
            Route::post('/page_like/{id}', 'page_like')->name('pages.likes.store');
            Route::post('/page_dislike/{id}', 'page_dislike')->name('pages.likes.destroy');
            Route::post('/update_page_coverphoto/{id}', 'update_page_coverphoto')->name('pages.cover.update');
            Route::get('/pages_timeline/{page_id}', 'pages_timeline')->name('pages.timeline.index');
            Route::get('/page_photos/{id}', 'page_photos')->name('pages.photos.index');

            Route::get('/marketplace', 'marketplace')->middleware('throttle:api-expensive')->name('marketplace.index');
            Route::get('/marketplace_category', 'marketplace_category')->name('marketplace.categories.index');
            Route::get('/marketplace_brand', 'marketplace_brand')->name('marketplace.brands.index');
            Route::get('/currencies', 'currencies')->name('currencies.index');
            Route::get('/filter', 'filter')->middleware('throttle:api-search')->name('marketplace.filter');
            Route::post('/create_marketplace', 'create_marketplace')->name('marketplace.store');
            Route::post('/update_marketplace/{id}', 'update_marketplace')->name('marketplace.update');
            Route::post('/delete_marketplace/{product_id}', 'delete_marketplace')->name('marketplace.destroy');
            Route::post('/save_for_later/{id}', 'save_for_later')->name('marketplace.saves.store');
            Route::post('/unsave_for_later/{id}', 'unsave_for_later')->name('marketplace.saves.destroy');

            Route::get('/videos', 'videos')->name('videos.index');
            Route::post('/create_videos', 'create_videos')->name('videos.store');
            Route::post('/view_videos/{id}', 'view_videos')->name('videos.views.store');
            Route::post('/save_for_later_videos/{id}', 'save_for_later_videos')->name('videos.saves.store');
            Route::post('/unsave_for_later_videos/{id}', 'unsave_for_later_videos')->name('videos.saves.destroy');
            Route::post('/delete_videos/{id}', 'delete_videos')->name('videos.destroy');

            Route::get('/events', 'events')->name('events.index');
            Route::get('/events_details/{id}', 'events_details')->name('events.show');
            Route::post('/create_event', 'create_event')->name('events.store');
            Route::post('/event_invition', 'event_invition')->name('events.invitations.store');
            Route::post('/update_event/{id}', 'update_event')->name('events.update');
            Route::post('/delete_event/{id}', 'delete_event')->name('events.destroy');
            Route::post('/event_going/{id}', 'event_going')->name('events.going.store');
            Route::post('/event_notgoing/{id}', 'event_notgoing')->name('events.going.destroy');
            Route::post('/event_interested/{id}', 'event_interested')->name('events.interested.store');
            Route::post('/event_notinterested/{id}', 'event_notinterested')->name('events.interested.destroy');
            Route::post('/event_cancel/{id}', 'event_cancel')->name('events.cancel');
            Route::get('/events_discussion/{event_id}', 'events_discussion')->name('events.discussions.index');

            Route::get('/blogs', 'blogs')->name('blogs.index');
            Route::get('/blog_category', 'blog_category')->name('blogs.categories.index');
            Route::post('/create_blogs', 'create_blogs')->name('blogs.store');
            Route::post('/update_blogs/{id}', 'update_blogs')->name('blogs.update');
            Route::post('/blog_view/{id}', 'blog_view')->name('blogs.views.store');
            Route::post('/blog_delete/{id}', 'blog_delete')->name('blogs.destroy');

            Route::get('/paid_content', 'paid_content')->name('paid_content.index');
            Route::get('/paid_content_package', 'paid_content_package')->name('paid_content.packages.index');

            Route::get('/jobs', 'jobs')->name('jobs.index');
            Route::post('/create_jobs', 'create_jobs')->name('jobs.store');
            Route::post('/update_jobs/{id}', 'update_jobs')->name('jobs.update');
            Route::post('/job_delete/{id}', 'job_delete')->name('jobs.destroy');
            Route::get('/job_wishlist', 'job_wishlist')->name('jobs.wishlist.index');
            Route::post('/job_add_wishlist/{id}', 'job_add_wishlist')->name('jobs.wishlist.store');
            Route::post('/JobApply/{id}', 'JobApply')->name('jobs.applications.store');

            Route::get('/fundraisers', 'fundraisers')->name('fundraisers.index');
            Route::post('/create_fundraiser', 'create_fundraiser')->name('fundraisers.store');
            Route::post('/update_fundraiser/{id}', 'update_fundraiser')->name('fundraisers.update');
            Route::post('/invited_fundraiser/{invited_friend_id}/{fundraiser_id}', 'invited_fundraiser')->name('fundraisers.invitations.store');
        });

        Route::controller(ApiNotificationController::class)->group(function () {
            Route::get('/notifications', 'notifications')->middleware('throttle:api-expensive')->name('notifications.index');
            Route::post('/accept_friend_notification/{id}', 'accept_friend_notification')->name('notifications.friends.accept');
            Route::post('/decline_friend_notification/{id}', 'decline_friend_notification')->name('notifications.friends.decline');
            Route::post('/accept_group_notification/{id}/{group_id}', 'accept_group_notification')->name('notifications.groups.accept');
            Route::post('/decline_group_notification/{id}/{group_id}', 'decline_group_notification')->name('notifications.groups.decline');
            Route::post('/accept_event_notification/{id}/{event_id}', 'accept_event_notification')->name('notifications.events.accept');
            Route::post('/decline_event_notification/{id}/{event_id}', 'decline_event_notification')->name('notifications.events.decline');
            Route::post('/mark_as_read/{id}', 'mark_as_read')->name('notifications.read');
            Route::post('/accept_fundraiser_notification/{id}/{fundraiser_id}', 'accept_fundraiser_notification')->name('notifications.fundraisers.accept');
            Route::post('/decline_fundraiser_notification/{id}/{fundraiser_id}', 'decline_fundraiser_notification')->name('notifications.fundraisers.decline');
        });

        Route::controller(ApiController::class)->group(function () {
            Route::get('/chat', 'chat')->middleware('throttle:api-expensive')->name('chat.index');
            Route::get('/chat_msg/{msg_thrade}', 'chat_msg')->middleware('throttle:api-expensive')->name('chat.messages.index');
            Route::post('/chat_save', 'chat_save')->name('chat.messages.store');
            Route::post('/thread_save', 'thread_save')->name('chat.threads.store');
            Route::post('/remove_chat/{chat_id}', 'remove_chat')->name('chat.messages.destroy');
            Route::post('/chat_read_option/{user_id}', 'chat_read_option')->name('chat.read.store');
            Route::post('/react_chat', 'react_chat')->name('chat.reactions.store');

            Route::get('/all_user', 'all_user')->middleware('throttle:api-expensive')->name('users.index');
            Route::get('/invite/{group_event_id}', 'invite')->name('invitations.show');
            Route::get('/count_notification', 'count_notification')->middleware('throttle:api-expensive')->name('notifications.count');

            Route::get('/roomName', 'roomName')->name('rooms.name');
            Route::get('/about_policy', 'about_policy')->name('policies.about');
        });
    });
});
