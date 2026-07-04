<?php

namespace Tests\Feature\Api\Contracts;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;

class ApiRouteContractTest extends ApiContractTestCase
{
    #[DataProvider('importantApiRoutes')]
    public function test_important_legacy_api_routes_keep_name_uri_and_method(string $name, string $method, string $uri): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Expected route [{$name}] to be registered.");
        $this->assertContains($method, $route->methods(), "Expected route [{$name}] to allow [{$method}].");
        $this->assertSame(ltrim($uri, '/'), $route->uri(), "Unexpected URI for route [{$name}].");
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function importantApiRoutes(): array
    {
        return [
            'public data' => ['api.data.index', 'GET', '/api/data'],
            'login' => ['api.auth.login', 'POST', '/api/login'],
            'signup' => ['api.auth.signup', 'POST', '/api/signup'],
            'forgot password' => ['api.password.forgot', 'POST', '/api/forgot_password'],
            'logout' => ['api.auth.logout', 'POST', '/api/logout'],
            'update password' => ['api.password.update', 'POST', '/api/update_password'],
            'me feed' => ['api.me.feed', 'GET', '/api/user'],
            'me posts' => ['api.me.posts', 'GET', '/api/user_post'],

            'friends index' => ['api.friends.index', 'GET', '/api/friends'],
            'friends store' => ['api.friends.store', 'POST', '/api/add_friend/{id}'],
            'friends destroy' => ['api.friends.destroy', 'POST', '/api/unfriend/{id}'],
            'follows store' => ['api.follows.store', 'POST', '/api/follow/{id}'],
            'follows destroy' => ['api.follows.destroy', 'POST', '/api/unfollow/{id}'],
            'friend requests index' => ['api.friend_requests.index', 'GET', '/api/friend_request'],

            'timeline index' => ['api.timeline.index', 'GET', '/api/timeline'],
            'timeline load' => ['api.timeline.load', 'GET', '/api/load_timeline'],
            'posts store' => ['api.posts.store', 'POST', '/api/create_post'],
            'posts update' => ['api.posts.update', 'POST', '/api/edit_post/{id}'],
            'posts destroy' => ['api.posts.destroy', 'POST', '/api/delete_post/{id}'],
            'posts show' => ['api.posts.show', 'GET', '/api/single_post/{post_id}'],
            'post media show' => ['api.posts.media.show', 'GET', '/api/post_media_file/{id}'],
            'post media destroy' => ['api.posts.media.destroy', 'POST', '/api/delete_media_file/{id}'],
            'post reports store' => ['api.post_reports.store', 'POST', '/api/save_post_report'],

            'stories index' => ['api.stories.index', 'GET', '/api/stories'],
            'stories store' => ['api.stories.store', 'POST', '/api/create_story'],
            'reactions store' => ['api.reactions.store', 'POST', '/api/reaction'],
            'comments store' => ['api.comments.store', 'POST', '/api/post_comment'],
            'comments index' => ['api.comments.index', 'GET', '/api/get_comment/{postId}'],
            'comments destroy' => ['api.comments.destroy', 'POST', '/api/comment_delete/{comment_id}'],
            'comment reactions store' => ['api.comment_reactions.store', 'POST', '/api/comment_reaction'],

            'profile show' => ['api.me.profile.show', 'GET', '/api/profile'],
            'other profile show' => ['api.profiles.show', 'GET', '/api/other_profile/{id}'],
            'profile update' => ['api.me.profile.update', 'POST', '/api/edit_profile'],
            'profile photo update' => ['api.me.profile_photo.update', 'POST', '/api/update_profile_pic'],
            'cover photo update' => ['api.me.cover_photo.update', 'POST', '/api/update_cover_pic'],
            'profile photos' => ['api.me.photos.index', 'GET', '/api/profile_photos'],
            'other profile photos' => ['api.profiles.photos.index', 'GET', '/api/other_profile_photos/{id}'],
            'profile videos' => ['api.me.videos.index', 'GET', '/api/profile_videos'],

            'albums store' => ['api.albums.store', 'POST', '/api/create_album'],
            'album photos' => ['api.albums.photos.index', 'GET', '/api/album_photos/{id}'],
            'albums destroy actual id route' => ['api.albums.destroy', 'POST', '/api/delete_album/{id}'],
            'album images store' => ['api.albums.images.store', 'POST', '/api/add_album_image'],

            'groups index' => ['api.groups.index', 'GET', '/api/groups'],
            'groups show' => ['api.groups.show', 'GET', '/api/groups_details/{id}'],
            'groups store' => ['api.groups.store', 'POST', '/api/create_group'],
            'groups update' => ['api.groups.update', 'POST', '/api/update_group/{group_id}'],
            'groups cover update' => ['api.groups.cover.update', 'POST', '/api/updatecoverphoto_group/{group_id}'],
            'groups invitations store' => ['api.groups.invitations.store', 'POST', '/api/group_invition'],
            'groups members store' => ['api.groups.members.store', 'POST', '/api/groups_join/{id}'],
            'groups members destroy' => ['api.groups.members.destroy', 'POST', '/api/groups_join_remove/{id}'],
            'groups discussions index' => ['api.groups.discussions.index', 'GET', '/api/groups_discussion/{group_id}'],
            'groups people index' => ['api.groups.people.index', 'GET', '/api/groups_people/{group_id}'],
            'groups events index' => ['api.groups.events.index', 'GET', '/api/groups_event/{group_id}'],
            'groups photos index' => ['api.groups.photos.index', 'GET', '/api/group_photos/{group_id}'],

            'pages index' => ['api.pages.index', 'GET', '/api/pages'],
            'pages show' => ['api.pages.show', 'GET', '/api/pages_details/{id}'],
            'page categories' => ['api.pages.categories.index', 'GET', '/api/page_category'],
            'pages store' => ['api.pages.store', 'POST', '/api/pages_create'],
            'pages update' => ['api.pages.update', 'POST', '/api/pages_update/{id}'],
            'pages destroy' => ['api.pages.destroy', 'POST', '/api/page_delete/{id}'],
            'page likes store' => ['api.pages.likes.store', 'POST', '/api/page_like/{id}'],
            'page likes destroy' => ['api.pages.likes.destroy', 'POST', '/api/page_dislike/{id}'],
            'page cover update' => ['api.pages.cover.update', 'POST', '/api/update_page_coverphoto/{id}'],
            'page timeline' => ['api.pages.timeline.index', 'GET', '/api/pages_timeline/{page_id}'],
            'page photos' => ['api.pages.photos.index', 'GET', '/api/page_photos/{id}'],

            'marketplace index' => ['api.marketplace.index', 'GET', '/api/marketplace'],
            'marketplace categories' => ['api.marketplace.categories.index', 'GET', '/api/marketplace_category'],
            'marketplace brands' => ['api.marketplace.brands.index', 'GET', '/api/marketplace_brand'],
            'currencies' => ['api.currencies.index', 'GET', '/api/currencies'],
            'marketplace filter' => ['api.marketplace.filter', 'GET', '/api/filter'],
            'marketplace store' => ['api.marketplace.store', 'POST', '/api/create_marketplace'],
            'marketplace update' => ['api.marketplace.update', 'POST', '/api/update_marketplace/{id}'],
            'marketplace destroy' => ['api.marketplace.destroy', 'POST', '/api/delete_marketplace/{product_id}'],
            'marketplace save store' => ['api.marketplace.saves.store', 'POST', '/api/save_for_later/{id}'],
            'marketplace save destroy' => ['api.marketplace.saves.destroy', 'POST', '/api/unsave_for_later/{id}'],

            'videos index' => ['api.videos.index', 'GET', '/api/videos'],
            'videos store' => ['api.videos.store', 'POST', '/api/create_videos'],
            'video views store' => ['api.videos.views.store', 'POST', '/api/view_videos/{id}'],
            'video saves store' => ['api.videos.saves.store', 'POST', '/api/save_for_later_videos/{id}'],
            'video saves destroy' => ['api.videos.saves.destroy', 'POST', '/api/unsave_for_later_videos/{id}'],
            'videos destroy' => ['api.videos.destroy', 'POST', '/api/delete_videos/{id}'],

            'events index' => ['api.events.index', 'GET', '/api/events'],
            'events show' => ['api.events.show', 'GET', '/api/events_details/{id}'],
            'events store' => ['api.events.store', 'POST', '/api/create_event'],
            'events invitations store' => ['api.events.invitations.store', 'POST', '/api/event_invition'],
            'events update' => ['api.events.update', 'POST', '/api/update_event/{id}'],
            'events destroy' => ['api.events.destroy', 'POST', '/api/delete_event/{id}'],
            'events going store' => ['api.events.going.store', 'POST', '/api/event_going/{id}'],
            'events going destroy' => ['api.events.going.destroy', 'POST', '/api/event_notgoing/{id}'],
            'events interested store' => ['api.events.interested.store', 'POST', '/api/event_interested/{id}'],
            'events interested destroy' => ['api.events.interested.destroy', 'POST', '/api/event_notinterested/{id}'],
            'events cancel' => ['api.events.cancel', 'POST', '/api/event_cancel/{id}'],
            'events discussions' => ['api.events.discussions.index', 'GET', '/api/events_discussion/{event_id}'],

            'blogs index' => ['api.blogs.index', 'GET', '/api/blogs'],
            'blog categories' => ['api.blogs.categories.index', 'GET', '/api/blog_category'],
            'blogs store' => ['api.blogs.store', 'POST', '/api/create_blogs'],
            'blogs update' => ['api.blogs.update', 'POST', '/api/update_blogs/{id}'],
            'blog views store' => ['api.blogs.views.store', 'POST', '/api/blog_view/{id}'],
            'blogs destroy' => ['api.blogs.destroy', 'POST', '/api/blog_delete/{id}'],

            'paid content' => ['api.paid_content.index', 'GET', '/api/paid_content'],
            'paid content packages' => ['api.paid_content.packages.index', 'GET', '/api/paid_content_package'],
            'jobs index' => ['api.jobs.index', 'GET', '/api/jobs'],
            'jobs store' => ['api.jobs.store', 'POST', '/api/create_jobs'],
            'jobs update' => ['api.jobs.update', 'POST', '/api/update_jobs/{id}'],
            'jobs destroy' => ['api.jobs.destroy', 'POST', '/api/job_delete/{id}'],
            'jobs wishlist index' => ['api.jobs.wishlist.index', 'GET', '/api/job_wishlist'],
            'jobs wishlist store' => ['api.jobs.wishlist.store', 'POST', '/api/job_add_wishlist/{id}'],
            'jobs applications store' => ['api.jobs.applications.store', 'POST', '/api/JobApply/{id}'],
            'fundraisers index' => ['api.fundraisers.index', 'GET', '/api/fundraisers'],
            'fundraisers store' => ['api.fundraisers.store', 'POST', '/api/create_fundraiser'],
            'fundraisers update' => ['api.fundraisers.update', 'POST', '/api/update_fundraiser/{id}'],
            'fundraisers invitations store' => ['api.fundraisers.invitations.store', 'POST', '/api/invited_fundraiser/{invited_friend_id}/{fundraiser_id}'],

            'notifications index' => ['api.notifications.index', 'GET', '/api/notifications'],
            'notifications friends accept' => ['api.notifications.friends.accept', 'POST', '/api/accept_friend_notification/{id}'],
            'notifications friends decline' => ['api.notifications.friends.decline', 'POST', '/api/decline_friend_notification/{id}'],
            'notifications groups accept' => ['api.notifications.groups.accept', 'POST', '/api/accept_group_notification/{id}/{group_id}'],
            'notifications groups decline' => ['api.notifications.groups.decline', 'POST', '/api/decline_group_notification/{id}/{group_id}'],
            'notifications events accept' => ['api.notifications.events.accept', 'POST', '/api/accept_event_notification/{id}/{event_id}'],
            'notifications events decline' => ['api.notifications.events.decline', 'POST', '/api/decline_event_notification/{id}/{event_id}'],
            'notifications read' => ['api.notifications.read', 'POST', '/api/mark_as_read/{id}'],
            'notifications fundraisers accept' => ['api.notifications.fundraisers.accept', 'POST', '/api/accept_fundraiser_notification/{id}/{fundraiser_id}'],
            'notifications fundraisers decline' => ['api.notifications.fundraisers.decline', 'POST', '/api/decline_fundraiser_notification/{id}/{fundraiser_id}'],
            'notifications count' => ['api.notifications.count', 'GET', '/api/count_notification'],

            'chat index' => ['api.chat.index', 'GET', '/api/chat'],
            'chat messages index' => ['api.chat.messages.index', 'GET', '/api/chat_msg/{message_thread}'],
            'chat messages store' => ['api.chat.messages.store', 'POST', '/api/chat_save'],
            'chat threads store' => ['api.chat.threads.store', 'POST', '/api/thread_save'],
            'chat messages destroy' => ['api.chat.messages.destroy', 'POST', '/api/remove_chat/{chat_id}'],
            'chat read store' => ['api.chat.read.store', 'POST', '/api/chat_read_option/{user_id}'],
            'chat reactions store' => ['api.chat.reactions.store', 'POST', '/api/react_chat'],

            'users index' => ['api.users.index', 'GET', '/api/all_user'],
            'invitations show' => ['api.invitations.show', 'GET', '/api/invite/{group_event_id}'],
            'rooms name' => ['api.rooms.name', 'GET', '/api/roomName'],
            'policies about' => ['api.policies.about', 'GET', '/api/about_policy'],
        ];
    }
}
