<?php

namespace Tests\Feature;

use App\Http\Controllers\ApiController;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class ApiControllerSurfaceTest extends TestCase
{
    /**
     * @var array<string, array{0: string, 1: string}>
     */
    private const PUBLIC_ROUTE_HANDLERS = [
        'login' => ['api.auth.login', 'POST'],
        'signup' => ['api.auth.signup', 'POST'],
        'forgot_password' => ['api.password.forgot', 'POST'],
    ];

    /**
     * @var array<string, array{0: string, 1: string, 2?: list<int>}>
     */
    private const PROTECTED_ROUTE_HANDLERS = [
        'logout' => ['api.auth.logout', 'POST'],
        'update_password' => ['api.password.update', 'POST'],
        'user' => ['api.me.feed', 'GET'],
        'user_post' => ['api.me.posts', 'GET'],
        'timeline' => ['api.timeline.index', 'GET'],
        'stories' => ['api.stories.index', 'GET'],
        'create_story' => ['api.stories.store', 'POST'],
        'load_timeline' => ['api.timeline.load', 'GET'],
        'friends' => ['api.friends.index', 'GET'],
        'add_friend' => ['api.friends.store', 'POST', [1]],
        'unfriend' => ['api.friends.destroy', 'POST', [1]],
        'friend_request' => ['api.friend_requests.index', 'GET'],
        'follow' => ['api.follows.store', 'POST', [1]],
        'unfollow' => ['api.follows.destroy', 'POST', [1]],
        'create_post' => ['api.posts.store', 'POST'],
        'edit_post' => ['api.posts.update', 'POST', [1]],
        'delete_post' => ['api.posts.destroy', 'POST', [1]],
        'save_post_report' => ['api.post_reports.store', 'POST'],
        'post_media_file' => ['api.posts.media.show', 'GET', [1]],
        'delete_media_file' => ['api.posts.media.destroy', 'POST', [1]],
        'profile' => ['api.me.profile.show', 'GET'],
        'other_profile' => ['api.profiles.show', 'GET', [1]],
        'edit_profile' => ['api.me.profile.update', 'POST'],
        'update_profile_pic' => ['api.me.profile_photo.update', 'POST'],
        'update_cover_pic' => ['api.me.cover_photo.update', 'POST'],
        'profile_photos' => ['api.me.photos.index', 'GET'],
        'other_profile_photos' => ['api.profiles.photos.index', 'GET', [1]],
        'single_post' => ['api.posts.show', 'GET', [1]],
        'reaction' => ['api.reactions.store', 'POST'],
        'post_comment' => ['api.comments.store', 'POST'],
        'get_comment' => ['api.comments.index', 'GET', [1]],
        'comment_delete' => ['api.comments.destroy', 'POST', [1]],
        'groups' => ['api.groups.index', 'GET'],
        'groups_details' => ['api.groups.show', 'GET', [1]],
        'create_group' => ['api.groups.store', 'POST'],
        'update_group' => ['api.groups.update', 'POST', [1]],
        'updatecoverphoto_group' => ['api.groups.cover.update', 'POST', [1]],
        'groups_join' => ['api.groups.members.store', 'POST', [1]],
        'groups_discussion' => ['api.groups.discussions.index', 'GET', [1]],
        'groups_people' => ['api.groups.people.index', 'GET', [1]],
        'groups_event' => ['api.groups.events.index', 'GET', [1]],
        'group_photos' => ['api.groups.photos.index', 'GET', [1]],
        'pages' => ['api.pages.index', 'GET'],
        'pages_details' => ['api.pages.show', 'GET', [1]],
        'pages_update' => ['api.pages.update', 'POST', [1]],
        'page_delete' => ['api.pages.destroy', 'POST', [1]],
        'page_like' => ['api.pages.likes.store', 'POST', [1]],
        'pages_create' => ['api.pages.store', 'POST'],
        'update_page_coverphoto' => ['api.pages.cover.update', 'POST', [1]],
        'page_category' => ['api.pages.categories.index', 'GET'],
        'pages_timeline' => ['api.pages.timeline.index', 'GET', [1]],
        'page_photos' => ['api.pages.photos.index', 'GET', [1]],
        'create_album' => ['api.albums.store', 'POST'],
        'delete_album' => ['api.albums.destroy', 'POST', [1]],
        'album_photos' => ['api.albums.photos.index', 'GET', [1]],
        'add_album_image' => ['api.albums.images.store', 'POST'],
        'marketplace' => ['api.marketplace.index', 'GET'],
        'create_marketplace' => ['api.marketplace.store', 'POST'],
        'update_marketplace' => ['api.marketplace.update', 'POST', [1]],
        'delete_marketplace' => ['api.marketplace.destroy', 'POST', [1]],
        'marketplace_brand' => ['api.marketplace.brands.index', 'GET'],
        'marketplace_category' => ['api.marketplace.categories.index', 'GET'],
        'currencies' => ['api.currencies.index', 'GET'],
        'filter' => ['api.marketplace.filter', 'GET'],
        'save_for_later' => ['api.marketplace.saves.store', 'POST', [1]],
        'unsave_for_later' => ['api.marketplace.saves.destroy', 'POST', [1]],
        'videos' => ['api.videos.index', 'GET'],
        'view_videos' => ['api.videos.views.store', 'POST', [1]],
        'create_videos' => ['api.videos.store', 'POST'],
        'save_for_later_videos' => ['api.videos.saves.store', 'POST', [1]],
        'unsave_for_later_videos' => ['api.videos.saves.destroy', 'POST', [1]],
        'delete_videos' => ['api.videos.destroy', 'POST', [1]],
        'events' => ['api.events.index', 'GET'],
        'events_details' => ['api.events.show', 'GET', [1]],
        'create_event' => ['api.events.store', 'POST'],
        'update_event' => ['api.events.update', 'POST', [1]],
        'delete_event' => ['api.events.destroy', 'POST', [1]],
        'event_going' => ['api.events.going.store', 'POST', [1]],
        'event_notgoing' => ['api.events.going.destroy', 'POST', [1]],
        'event_interested' => ['api.events.interested.store', 'POST', [1]],
        'event_notinterested' => ['api.events.interested.destroy', 'POST', [1]],
        'event_cancel' => ['api.events.cancel', 'POST', [1]],
        'events_discussion' => ['api.events.discussions.index', 'GET', [1]],
        'blogs' => ['api.blogs.index', 'GET'],
        'blog_category' => ['api.blogs.categories.index', 'GET'],
        'create_blogs' => ['api.blogs.store', 'POST'],
        'update_blogs' => ['api.blogs.update', 'POST', [1]],
        'blog_view' => ['api.blogs.views.store', 'POST', [1]],
        'blog_delete' => ['api.blogs.destroy', 'POST', [1]],
        'paid_content' => ['api.paid_content.index', 'GET'],
        'paid_content_package' => ['api.paid_content.packages.index', 'GET'],
        'jobs' => ['api.jobs.index', 'GET'],
        'create_jobs' => ['api.jobs.store', 'POST'],
        'update_jobs' => ['api.jobs.update', 'POST', [1]],
        'job_delete' => ['api.jobs.destroy', 'POST', [1]],
        'job_add_wishlist' => ['api.jobs.wishlist.store', 'POST', [1]],
        'JobApply' => ['api.jobs.applications.store', 'POST', [1]],
        'fundraisers' => ['api.fundraisers.index', 'GET'],
        'create_fundraiser' => ['api.fundraisers.store', 'POST'],
        'update_fundraiser' => ['api.fundraisers.update', 'POST', [1]],
        'invited_fundraiser' => ['api.fundraisers.invitations.store', 'POST', [1, 2]],
        'chat' => ['api.chat.index', 'GET'],
        'chat_msg' => ['api.chat.messages.index', 'GET', [1]],
        'chat_save' => ['api.chat.messages.store', 'POST'],
        'thread_save' => ['api.chat.threads.store', 'POST'],
        'remove_chat' => ['api.chat.messages.destroy', 'POST', [1]],
        'chat_read_option' => ['api.chat.read.store', 'POST', [1]],
        'react_chat' => ['api.chat.reactions.store', 'POST'],
        'all_user' => ['api.users.index', 'GET'],
        'invite' => ['api.invitations.show', 'GET', [1]],
        'group_invition' => ['api.groups.invitations.store', 'POST'],
        'event_invition' => ['api.events.invitations.store', 'POST'],
        'count_notification' => ['api.notifications.count', 'GET'],
        'roomName' => ['api.rooms.name', 'GET'],
        'about_policy' => ['api.policies.about', 'GET'],
    ];

    /**
     * @var list<string>
     */
    private const INTERNAL_HELPERS = [
        'apiTokenAbilities',
        'apiTokenExpiresAt',
        'create_profile_photo_post',
        'storeMarketplace',
        'marketplaceCollectionResponse',
        'applyMarketplaceSorting',
        'marketplaceMessageThreadId',
        'chatReceiverIdFromRequest',
    ];

    public function test_listed_api_controller_surface_is_present_with_expected_visibility(): void
    {
        $controller = new ReflectionClass(ApiController::class);

        foreach (self::PUBLIC_ROUTE_HANDLERS as $method => $route) {
            $this->assertTrue($controller->hasMethod($method), "{$method} is missing from ApiController.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "{$method} must stay public for route {$route[0]}.");
        }

        foreach (self::PROTECTED_ROUTE_HANDLERS as $method => $route) {
            $this->assertTrue($controller->hasMethod($method), "{$method} is missing from ApiController.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "{$method} must stay public for route {$route[0]}.");
        }

        foreach (self::INTERNAL_HELPERS as $method) {
            $this->assertTrue($controller->hasMethod($method), "{$method} helper is missing from ApiController.");
            $this->assertTrue($controller->getMethod($method)->isPrivate(), "{$method} must not be routable public controller surface.");
        }
    }

    public function test_listed_public_route_handlers_are_registered_as_named_api_routes(): void
    {
        foreach (self::PUBLIC_ROUTE_HANDLERS as $method => [$routeName, $verb]) {
            $this->assertApiControllerRoute($method, $routeName, $verb);
        }

        foreach (self::PROTECTED_ROUTE_HANDLERS as $method => [$routeName, $verb]) {
            $this->assertApiControllerRoute($method, $routeName, $verb);
        }
    }

    public function test_internal_helpers_are_not_registered_as_routes(): void
    {
        $registeredActions = [];

        foreach (Route::getRoutes() as $route) {
            $registeredActions[] = $route->getActionName();
        }

        foreach (self::INTERNAL_HELPERS as $method) {
            $this->assertNotContains(
                ApiController::class.'@'.$method,
                $registeredActions,
                "{$method} is an internal helper and must not be registered directly as an API route."
            );
        }
    }

    public function test_listed_protected_route_handlers_reject_missing_bearer_tokens_with_legacy_json(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        foreach (self::PROTECTED_ROUTE_HANDLERS as $method => $routeDefinition) {
            [$routeName, $verb] = $routeDefinition;
            $parameters = $routeDefinition[2] ?? [];

            $response = match ($verb) {
                'GET' => $this->getJson(route($routeName, $parameters)),
                'POST' => $this->postJson(route($routeName, $parameters)),
                default => $this->fail("Unexpected verb {$verb} for {$routeName}."),
            };

            $response
                ->assertOk()
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ]);

            $this->assertStringContainsString(
                'application/json',
                (string) $response->headers->get('content-type'),
                "{$method} must return JSON when the bearer token is missing."
            );
        }
    }

    public function test_public_auth_route_handlers_remain_public_json_endpoints(): void
    {
        foreach (self::PUBLIC_ROUTE_HANDLERS as $method => [$routeName]) {
            $response = $this->postJson(route($routeName), []);

            $this->assertNotSame(
                ['success' => false, 'message' => 'Unauthorized access'],
                $response->json(),
                "{$method} must remain a public authentication endpoint."
            );
            $this->assertStringContainsString('application/json', (string) $response->headers->get('content-type'));
            $this->assertFalse($response->baseResponse->isRedirection(), "{$method} must not redirect API clients.");
        }
    }

    private function assertApiControllerRoute(string $method, string $routeName, string $verb): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route, "{$routeName} route is missing for {$method}.");
        $this->assertSame(
            ApiController::class.'@'.$method,
            $route->getActionName(),
            "{$routeName} must point to ApiController::{$method}."
        );
        $this->assertContains($verb, $route->methods(), "{$routeName} must support {$verb}.");
    }
}
