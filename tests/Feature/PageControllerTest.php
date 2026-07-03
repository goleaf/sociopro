<?php

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\PageController;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageLike;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    private const METHODS = [
        '__construct',
        'pages',
        'store',
        'update',
        'updatecoverphoto',
        'updateinfo',
        'load_page_by_scrolling',
        'single_page',
        'page_photos',
        'videos',
        'load_videos',
        'like',
        'dislike',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string}>
     */
    private const ROUTES = [
        'pages' => ['pages', ['GET', 'HEAD'], 'pages'],
        'page.store' => ['store', ['POST'], 'page/store'],
        'page.update' => ['update', ['POST'], 'update/page/{id}'],
        'page.coverphoto' => ['updatecoverphoto', ['POST'], 'update/coverphoto/page/{id}'],
        'page.update.info' => ['updateinfo', ['POST'], 'update/info/page/{id}'],
        'load_page_by_scrolling' => ['load_page_by_scrolling', ['GET', 'HEAD'], 'load_page_by_scrolling'],
        'single.page' => ['single_page', ['GET', 'HEAD'], 'page/view/{id}'],
        'single.page.photos' => ['page_photos', ['GET', 'HEAD'], 'page/photo/view/{id}'],
        'page.videos' => ['videos', ['GET', 'HEAD'], 'page/videos/{id}'],
        'page.load_videos' => ['load_videos', ['GET', 'HEAD'], 'page/load_videos'],
        'page.like' => ['like', ['GET', 'HEAD'], 'page/like/{id}'],
        'page.dislike' => ['dislike', ['GET', 'HEAD'], 'page/dislike/{id}'],
    ];

    public function test_requested_page_controller_methods_stay_public(): void
    {
        $controller = new ReflectionClass(PageController::class);

        foreach (self::METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "PageController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "PageController::{$method} should stay public.");
        }
    }

    public function test_requested_page_routes_keep_expected_actions_methods_uris_and_middleware(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(PageController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");

            foreach (['auth', 'user', 'verified', 'activity', 'prevent-back-history'] as $middleware) {
                $this->assertContains($middleware, $route->middleware(), "Route [{$routeName}] lost [{$middleware}] middleware.");
            }
        }
    }

    public function test_pages_index_splits_owned_suggested_and_liked_pages(): void
    {
        $viewer = $this->activeUser();
        $ownedPage = $this->page($viewer, ['title' => 'Owned feature page']);
        $suggestedPage = $this->page($this->activeUser(), ['title' => 'Suggested feature page']);
        $likedPage = $this->page($this->activeUser(), ['title' => 'Liked feature page']);
        PageLike::factory()->forUser($viewer)->forPage($likedPage)->create();

        $response = $this
            ->actingAs($viewer)
            ->get(route('pages'));

        $response
            ->assertOk()
            ->assertViewIs('frontend.index')
            ->assertViewHas('view_path', 'frontend.pages.pages');

        $mypages = $response->viewData('mypages');
        $suggestedpages = $response->viewData('suggestedpages');
        $likedpage = $response->viewData('likedpage');

        $this->assertSame([$ownedPage->id], $mypages->pluck('id')->all());
        $this->assertContains($suggestedPage->id, $suggestedpages->pluck('id')->all());
        $this->assertNotContains($ownedPage->id, $suggestedpages->pluck('id')->all());
        $this->assertNotContains($likedPage->id, $suggestedpages->pluck('id')->all());
        $this->assertSame([$likedPage->id], $likedpage->pluck('id')->all());
    }

    public function test_store_update_cover_and_info_routes_mutate_only_current_users_page(): void
    {
        Storage::fake('public');

        $owner = $this->activeUser();
        $category = PageCategory::factory()->create();
        $nextCategory = PageCategory::factory()->create();

        $this
            ->actingAs($owner)
            ->post(route('page.store'), [
                'name' => 'Created feature page',
                'category' => $category->id,
                'description' => 'Created feature description.',
            ])
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $page = Page::query()
            ->where('user_id', $owner->id)
            ->where('title', 'Created feature page')
            ->firstOrFail();

        $this
            ->actingAs($owner)
            ->post(route('page.update', ['id' => $page->id]), [
                'name' => 'Updated feature page',
                'category' => $nextCategory->id,
                'description' => 'Updated feature description.',
            ])
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this
            ->actingAs($owner)
            ->post(route('page.coverphoto', ['id' => $page->id]), [
                'cover_photo' => UploadedFile::fake()->image('page-cover.jpg', 20, 20),
            ])
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this
            ->actingAs($owner)
            ->from(route('single.page', ['id' => $page->id]))
            ->post(route('page.update.info', ['id' => $page->id]), [
                'job' => 'Feature job',
                'lifestyle' => 'Feature lifestyle',
                'location' => 'Feature location',
            ])
            ->assertRedirect(route('single.page', ['id' => $page->id]));

        $page->refresh();

        $this->assertSame($owner->id, (int) $page->user_id);
        $this->assertSame('Updated feature page', $page->title);
        $this->assertSame($nextCategory->id, (int) $page->category_id);
        $this->assertSame('Updated feature description.', $page->description);
        $this->assertSame('Feature job', $page->job);
        $this->assertSame('Feature lifestyle', $page->lifestyle);
        $this->assertSame('Feature location', $page->location);
        $this->assertNotEmpty($page->coverphoto);
        Storage::disk('public')->assertExists('pages/coverphoto/'.$page->coverphoto);
    }

    public function test_scroll_profile_photo_and_video_routes_return_scoped_page_data(): void
    {
        $viewer = $this->activeUser();
        $owner = $this->activeUser();
        $ownedPage = $this->page($viewer, ['title' => 'Scrolling owned page']);
        $otherPage = $this->page($owner, ['title' => 'Scrolling other page']);
        $profilePage = $this->page($owner, ['title' => 'Profile feature page']);
        $post = Posts::factory()->forOwner($owner)->create([
            'publisher' => 'page',
            'publisher_id' => $profilePage->id,
        ]);
        $pageImage = MediaFile::factory()->image()->create([
            'user_id' => $owner->id,
            'page_id' => $profilePage->id,
            'post_id' => $post->post_id,
            'file_name' => 'feature-page-image.jpg',
        ]);
        $pageVideo = MediaFile::factory()->video()->create([
            'user_id' => $owner->id,
            'page_id' => $profilePage->id,
            'post_id' => $post->post_id,
            'file_name' => 'feature-page-video.mp4',
        ]);
        $profileVideo = MediaFile::factory()->video()->create([
            'user_id' => $viewer->id,
            'page_id' => null,
            'post_id' => $post->post_id,
            'file_name' => 'feature-user-video.mp4',
        ]);
        MediaFile::factory()->video()->create([
            'user_id' => $owner->id,
            'page_id' => null,
            'post_id' => $post->post_id,
            'file_name' => 'feature-other-user-video.mp4',
        ]);

        $this
            ->actingAs($viewer)
            ->get(route('load_page_by_scrolling', ['offset' => 0]))
            ->assertOk()
            ->assertViewIs('frontend.pages.single-page')
            ->assertViewHas('mypages', fn ($pages): bool => $pages->pluck('id')->contains($ownedPage->id)
                && ! $pages->pluck('id')->contains($otherPage->id));

        $this
            ->actingAs($viewer)
            ->get(route('single.page', ['id' => $profilePage->id]))
            ->assertOk()
            ->assertViewIs('frontend.index')
            ->assertViewHas('view_path', 'frontend.pages.page-timeline');

        $this
            ->actingAs($viewer)
            ->get(route('single.page.photos', ['id' => $profilePage->id]))
            ->assertOk()
            ->assertViewIs('frontend.index')
            ->assertViewHas('view_path', 'frontend.pages.photos')
            ->assertViewHas('all_photos', fn ($photos): bool => $photos->pluck('id')->contains($pageImage->id));

        $this
            ->actingAs($viewer)
            ->get(route('page.videos', ['id' => $profilePage->id]))
            ->assertOk()
            ->assertViewIs('frontend.index')
            ->assertViewHas('view_path', 'frontend.pages.video')
            ->assertViewHas('all_videos', fn ($videos): bool => $videos->pluck('id')->contains($pageVideo->id));

        $this
            ->actingAs($viewer)
            ->get(route('page.load_videos', ['offset' => 0]))
            ->assertOk()
            ->assertViewIs('frontend.profile.video_single')
            ->assertViewHas('all_videos', fn ($videos): bool => $videos->pluck('id')->all() === [$profileVideo->id]);
    }

    public function test_like_and_dislike_are_idempotent_and_scoped_to_current_user(): void
    {
        $viewer = $this->activeUser();
        $otherUser = $this->activeUser();
        $page = $this->page($otherUser);
        PageLike::factory()->forUser($otherUser)->forPage($page)->create();

        $this
            ->actingAs($viewer)
            ->get(route('page.like', ['id' => $page->id]))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this
            ->actingAs($viewer)
            ->get(route('page.like', ['id' => $page->id]))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertSame(1, PageLike::query()
            ->where('user_id', $viewer->id)
            ->where('page_id', $page->id)
            ->where('role', MembershipRole::General->value)
            ->count());
        $this->assertSame(1, PageLike::query()
            ->where('user_id', $otherUser->id)
            ->where('page_id', $page->id)
            ->count());

        $this
            ->actingAs($viewer)
            ->get(route('page.dislike', ['id' => $page->id]))
            ->assertOk()
            ->assertSee('"reload":1', false);

        $this->assertDatabaseMissing('page_likes', [
            'user_id' => $viewer->id,
            'page_id' => $page->id,
        ]);
        $this->assertDatabaseHas('page_likes', [
            'user_id' => $otherUser->id,
            'page_id' => $page->id,
        ]);

        $this
            ->actingAs($viewer)
            ->get(route('page.dislike', ['id' => $page->id]))
            ->assertOk()
            ->assertSee('"reload":0', false);
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function page(User $owner, array $attributes = []): Page
    {
        return Page::factory()
            ->forOwner($owner)
            ->create($attributes);
    }
}
