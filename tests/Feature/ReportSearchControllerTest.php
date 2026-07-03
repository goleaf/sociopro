<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\VideoCategory;
use App\Enums\Visibility;
use App\Http\Controllers\Report\SearchController;
use App\Models\Event;
use App\Models\Group;
use App\Models\Marketplace;
use App\Models\Page;
use App\Models\Posts;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use ReflectionClass;
use Tests\TestCase;

class ReportSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    private const METHODS = [
        'search',
        'search_people',
        'search_post',
        'search_video',
        'search_product',
        'search_page',
        'search_group',
        'search_event',
    ];

    /**
     * @var array<string, array{0: string, 1: string}>
     */
    private const ROUTES = [
        'search' => ['search', 'search'],
        'search.people' => ['search_people', 'search/people'],
        'search.post' => ['search_post', 'search/post'],
        'search.video' => ['search_video', 'search/video'],
        'search.product' => ['search_product', 'search/product'],
        'search.page' => ['search_page', 'search/page'],
        'search.group.specific' => ['search_group', 'search/group'],
        'search.event' => ['search_event', 'search/event'],
    ];

    protected function tearDown(): void
    {
        unset($_GET['search']);

        parent::tearDown();
    }

    public function test_requested_report_search_controller_methods_stay_public(): void
    {
        $controller = new ReflectionClass(SearchController::class);

        foreach (self::METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "SearchController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "SearchController::{$method} should stay public.");
        }
    }

    public function test_report_search_routes_keep_expected_actions_uris_and_middleware(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(SearchController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame(['GET', 'HEAD'], $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");
            $this->assertContains('auth', $route->gatherMiddleware(), "Route [{$routeName}] should require auth.");
            $this->assertContains('user', $route->gatherMiddleware(), "Route [{$routeName}] should require user middleware.");
            $this->assertContains('verified', $route->gatherMiddleware(), "Route [{$routeName}] should require verified users.");
            $this->assertContains('activity', $route->gatherMiddleware(), "Route [{$routeName}] should require activity middleware.");
            $this->assertContains('prevent-back-history', $route->gatherMiddleware(), "Route [{$routeName}] should prevent back history.");
            $this->assertContains('throttle:search', $route->gatherMiddleware(), "Route [{$routeName}] should use search throttling.");
        }
    }

    public function test_search_collects_matching_records_for_every_result_bucket(): void
    {
        $user = $this->activeUser();
        $fixtures = $this->createSearchFixtures($user, 'Searchable');
        $this->actingAs($user);

        $response = $this->getSearchRoute('search', 'Searchable')->assertOk();

        $this->assertSame('frontend.search.searchview', $response->viewData('view_path'));
        $this->assertFalse($response->viewData('is_hashtag_search'));
        $this->assertTrue($response->viewData('peoples')->pluck('id')->contains($fixtures['person']->id));
        $this->assertTrue($response->viewData('posts')->pluck('post_id')->contains($fixtures['post']->post_id));
        $this->assertTrue($response->viewData('products')->pluck('id')->contains($fixtures['product']->id));
        $this->assertTrue($response->viewData('pages')->pluck('id')->contains($fixtures['page']->id));
        $this->assertTrue($response->viewData('groups')->pluck('id')->contains($fixtures['public_group']->id));
        $this->assertFalse($response->viewData('groups')->pluck('id')->contains($fixtures['private_group']->id));
        $this->assertTrue($response->viewData('events')->pluck('id')->contains($fixtures['public_event']->id));
        $this->assertFalse($response->viewData('events')->pluck('id')->contains($fixtures['private_event']->id));
        $this->assertTrue($response->viewData('videos')->pluck('id')->contains($fixtures['public_video']->id));
        $this->assertFalse($response->viewData('videos')->pluck('id')->contains($fixtures['private_video']->id));
    }

    public function test_search_hashtag_branch_counts_matching_post_hashtags(): void
    {
        $user = $this->activeUser();
        $taggedPost = $this->postFor($user, [
            'description' => 'Launch post',
            'hashtag' => '#SearchLaunch',
        ]);
        $this->postFor($user, [
            'description' => 'Other post',
            'hashtag' => '#OtherTag',
        ]);
        $this->actingAs($user);

        $response = $this->getSearchRoute('search', '#SearchLaunch')->assertOk();

        $this->assertSame('frontend.search.searchview', $response->viewData('view_path'));
        $this->assertTrue($response->viewData('is_hashtag_search'));
        $this->assertSame('SearchLaunch', $response->viewData('hashtag'));
        $this->assertSame(1, $response->viewData('hashtag_count'));
        $this->assertTrue($response->viewData('posts')->pluck('post_id')->contains($taggedPost->post_id));
    }

    public function test_specific_search_endpoints_return_their_expected_view_data(): void
    {
        $user = $this->activeUser();
        $fixtures = $this->createSearchFixtures($user, 'Focused');
        $this->actingAs($user);

        $people = $this->getSearchRoute('search.people', 'Focused')->assertOk();
        $this->assertSame('frontend.search.people', $people->viewData('view_path'));
        $this->assertTrue($people->viewData('peoples')->pluck('id')->contains($fixtures['person']->id));

        $posts = $this->getSearchRoute('search.post', 'Focused')->assertOk();
        $this->assertSame('frontend.search.post', $posts->viewData('view_path'));
        $this->assertTrue($posts->viewData('posts')->pluck('post_id')->contains($fixtures['post']->post_id));

        $videos = $this->getSearchRoute('search.video', 'Focused')->assertOk();
        $this->assertSame('frontend.search.video', $videos->viewData('view_path'));
        $this->assertSame([$fixtures['public_video']->id], $videos->viewData('videos')->pluck('id')->all());

        $products = $this->getSearchRoute('search.product', 'Focused')->assertOk();
        $this->assertSame('frontend.search.product', $products->viewData('view_path'));
        $this->assertSame([$fixtures['product']->id], $products->viewData('products')->pluck('id')->all());

        $pages = $this->getSearchRoute('search.page', 'Focused')->assertOk();
        $this->assertSame('frontend.search.page', $pages->viewData('view_path'));
        $this->assertSame([$fixtures['page']->id], $pages->viewData('pages')->pluck('id')->all());

        $groups = $this->getSearchRoute('search.group.specific', 'Focused')->assertOk();
        $this->assertSame('frontend.search.group', $groups->viewData('view_path'));
        $this->assertSame([$fixtures['public_group']->id], $groups->viewData('groups')->pluck('id')->all());

        $events = $this->getSearchRoute('search.event', 'Focused')->assertOk();
        $this->assertSame('frontend.search.event', $events->viewData('view_path'));
        $this->assertSame([$fixtures['public_event']->id], $events->viewData('events')->pluck('id')->all());
    }

    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Search Test User',
            'email_verified_at' => now(),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
            'about' => 'Search test bio',
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function createSearchFixtures(User $owner, string $term): array
    {
        $person = $this->activeUser([
            'name' => "{$term} Person",
            'about' => "{$term} person bio",
        ]);
        $post = $this->postFor($owner, [
            'description' => "{$term} post body",
            'location' => "{$term} City",
        ]);
        $product = Marketplace::factory()->forOwner($owner)->create([
            'title' => "{$term} Product",
            'status' => '1',
        ]);
        $page = Page::factory()->forOwner($owner)->create([
            'title' => "{$term} Page",
        ]);
        $publicGroup = Group::factory()->create([
            'user_id' => $owner->id,
            'title' => "{$term} Public Group",
            'privacy' => Visibility::Public->value,
        ]);
        $privateGroup = Group::factory()->create([
            'user_id' => $owner->id,
            'title' => "{$term} Private Group",
            'privacy' => Visibility::Private->value,
        ]);
        $publicEvent = Event::factory()->forOwner($owner)->create([
            'title' => "{$term} Public Event",
            'privacy' => Visibility::Public->value,
        ]);
        $privateEvent = Event::factory()->forOwner($owner)->create([
            'title' => "{$term} Private Event",
            'privacy' => Visibility::Private->value,
        ]);
        $publicVideo = $this->videoFor($owner, "{$term} Public Video", Visibility::Public);
        $privateVideo = $this->videoFor($owner, "{$term} Private Video", Visibility::Private);

        return [
            'person' => $person,
            'post' => $post,
            'product' => $product,
            'page' => $page,
            'public_group' => $publicGroup,
            'private_group' => $privateGroup,
            'public_event' => $publicEvent,
            'private_event' => $privateEvent,
            'public_video' => $publicVideo,
            'private_video' => $privateVideo,
        ];
    }

    private function postFor(User $user, array $overrides = []): Posts
    {
        $post = Posts::factory()->forOwner($user)->create(array_merge([
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => PostType::General->value,
            'privacy' => Visibility::Public->value,
            'tagged_user_ids' => json_encode([]),
            'activity_id' => 0,
            'location' => '',
            'description' => 'Search post',
            'user_reacts' => json_encode([]),
            'status' => ContentStatus::Active->value,
        ], $overrides));

        if (array_key_exists('hashtag', $overrides)) {
            $post->forceFill(['hashtag' => $overrides['hashtag']])->save();
        }

        return $post;
    }

    private function videoFor(User $user, string $title, Visibility $visibility): Video
    {
        $video = Video::factory()->forOwner($user)->create([
            'title' => $title,
            'privacy' => $visibility->value,
            'category' => VideoCategory::Video->value,
            'view' => json_encode([]),
        ]);

        Posts::factory()->forOwner($user)->create([
            'publisher' => 'video_and_shorts',
            'publisher_id' => $video->id,
            'post_type' => VideoCategory::Video->value,
            'privacy' => $visibility->value,
            'description' => $title,
            'tagged_user_ids' => json_encode([]),
            'activity_id' => 0,
            'user_reacts' => json_encode([]),
            'status' => ContentStatus::Active->value,
        ]);

        return $video;
    }

    private function getSearchRoute(string $routeName, string $term): TestResponse
    {
        $_GET['search'] = $term;

        return $this->get(route($routeName).'?'.http_build_query(['search' => $term]));
    }
}
