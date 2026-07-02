<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\PageController;
use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdatePageRequest;
use App\Models\Page;
use App\Models\Pagecategory;
use App\Models\PageLike;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class PageSecurityPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_write_actions_use_form_requests(): void
    {
        $this->assertControllerRequestType('store', StorePageRequest::class);
        $this->assertControllerRequestType('update', UpdatePageRequest::class);
    }

    public function test_web_user_cannot_load_another_users_page_owner_modals(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $page = $this->page($owner);

        foreach ([
            'frontend.pages.edit-modal',
            'frontend.pages.edit-cover-photo',
            'frontend.pages.edit-page-info',
        ] as $viewPath) {
            $response = $this
                ->actingAs($otherUser)
                ->get(route('load_modal_content', [
                    'view_path' => $viewPath,
                    'page_id' => $page->id,
                ]));

            $response->assertForbidden();
        }
    }

    public function test_web_user_cannot_update_another_users_page(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $category = Pagecategory::factory()->create();
        $page = $this->page($owner, [
            'title' => 'Original owner page',
            'description' => 'Original safe description.',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->post(route('page.update', ['id' => $page->id]), $this->pagePayload($category, [
                'name' => 'Hijacked page',
                'description' => 'Attacker description.',
            ]));

        $response->assertForbidden();

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'user_id' => $owner->id,
            'title' => 'Original owner page',
            'description' => 'Original safe description.',
        ]);
    }

    public function test_page_update_ignores_sensitive_payload_fields(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $category = Pagecategory::factory()->create();
        $page = $this->page($owner, [
            'status' => '1',
        ]);

        $response = $this
            ->actingAs($owner)
            ->post(route('page.update', ['id' => $page->id]), $this->pagePayload($category, [
                'name' => 'Owner updated page',
                'description' => 'Updated by owner.',
                'status' => '0',
                'user_id' => $otherUser->id,
            ]));

        $response
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'user_id' => $owner->id,
            'status' => '1',
            'title' => 'Owner updated page',
            'description' => 'Updated by owner.',
        ]);
    }

    public function test_web_user_cannot_update_another_users_page_profile_fields(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $page = $this->page($owner, [
            'job' => 'Original job',
            'lifestyle' => 'Original lifestyle',
            'location' => 'Original location',
        ]);

        $infoResponse = $this
            ->actingAs($otherUser)
            ->post(route('page.update.info', ['id' => $page->id]), [
                'job' => 'Hijacked job',
                'lifestyle' => 'Hijacked lifestyle',
                'location' => 'Hijacked location',
            ]);

        $coverResponse = $this
            ->actingAs($otherUser)
            ->post(route('page.coverphoto', ['id' => $page->id]), []);

        $infoResponse->assertForbidden();
        $coverResponse->assertForbidden();

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'job' => 'Original job',
            'lifestyle' => 'Original lifestyle',
            'location' => 'Original location',
        ]);
    }

    public function test_page_store_validates_category_and_keeps_legacy_error_shape(): void
    {
        $user = $this->activeUser();

        $response = $this
            ->actingAs($user)
            ->post(route('page.store'), [
                'name' => '',
                'category' => 999999,
                'description' => str_repeat('x', 5001),
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'name',
                    'category',
                    'description',
                ],
            ]);
    }

    public function test_page_edit_modal_escapes_description(): void
    {
        $owner = $this->activeUser();
        $payload = '<script>alert("page-xss")</script><b>safe text</b>';
        $page = $this->page($owner, [
            'description' => $payload,
        ]);

        $response = $this
            ->actingAs($owner)
            ->get(route('load_modal_content', [
                'view_path' => 'frontend.pages.edit-modal',
                'page_id' => $page->id,
            ]));

        $response
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee(e($payload), false);
    }

    public function test_page_modal_blades_do_not_query_models_or_render_raw_descriptions(): void
    {
        $editModal = File::get(resource_path('views/frontend/pages/edit-modal.blade.php'));

        foreach ([
            $editModal,
            File::get(resource_path('views/frontend/pages/create_page.blade.php')),
            File::get(resource_path('views/frontend/pages/edit-cover-photo.blade.php')),
            File::get(resource_path('views/frontend/pages/edit-page-info.blade.php')),
        ] as $blade) {
            $this->assertStringNotContainsString('Pagecategory::all()', $blade);
            $this->assertStringNotContainsString('Page::find', $blade);
        }

        $this->assertStringNotContainsString('{!! script_checker($page->description, false) !!}', $editModal);
    }

    public function test_page_listing_blades_use_preloaded_like_aggregates(): void
    {
        foreach ([
            resource_path('views/frontend/pages/single-page.blade.php'),
            resource_path('views/frontend/pages/suggested.blade.php'),
            resource_path('views/frontend/pages/liked-page.blade.php'),
        ] as $path) {
            $blade = File::get($path);

            $this->assertStringNotContainsString('PageLike::where', $blade);
            $this->assertStringNotContainsString('Page::find', $blade);
        }
    }

    public function test_page_profile_blades_use_preloaded_view_data(): void
    {
        foreach ([
            resource_path('views/frontend/pages/bio.blade.php'),
            resource_path('views/frontend/pages/timeline-header.blade.php'),
            resource_path('views/frontend/pages/page-timeline.blade.php'),
        ] as $path) {
            $blade = File::get($path);

            $this->assertStringNotContainsString('App\\Models\\', $blade);
            $this->assertStringNotContainsString('PageLike::where', $blade);
            $this->assertStringNotContainsString('Posts::where', $blade);
            $this->assertStringNotContainsString('DB::table', $blade);
        }
    }

    public function test_single_page_renders_escaped_intro_and_preloaded_counts(): void
    {
        $viewer = $this->activeUser();
        $owner = $this->activeUser();
        $page = $this->page($owner, [
            'description' => '<script>alert("page-xss")</script> Public page intro',
            'job' => 'Community organizer',
            'location' => 'Vilnius',
            'lifestyle' => 'Accessible events',
        ]);

        PageLike::factory()->forPage($page)->count(2)->create();
        PageLike::factory()->forUser($viewer)->forPage($page)->create();
        Posts::factory()->forOwner($owner)->create([
            'publisher' => 'page',
            'publisher_id' => $page->id,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('single.page', ['id' => $page->id]));

        $response
            ->assertOk()
            ->assertDontSee('<script>alert("page-xss")</script>', false)
            ->assertSee(e('alert("page-xss") Public page intro'), false)
            ->assertSee('3', false)
            ->assertSee(get_phrase('Posts'), false);
    }

    public function test_pages_index_batches_page_like_lookups_for_rendered_cards(): void
    {
        $viewer = $this->activeUser();
        $category = Pagecategory::factory()->create();
        $viewerPages = Page::factory()
            ->count(5)
            ->forOwner($viewer)
            ->forCategory($category)
            ->create();
        $suggestedPages = Page::factory()
            ->count(5)
            ->forCategory($category)
            ->create();
        $likedPages = Page::factory()
            ->count(3)
            ->forCategory($category)
            ->create();

        foreach ($viewerPages->concat($suggestedPages)->concat($likedPages) as $page) {
            PageLike::factory()->forPage($page)->create();
        }

        foreach ($likedPages as $page) {
            PageLike::factory()->forUser($viewer)->forPage($page)->create();
        }

        $pageLikeQueries = $this->countQueriesForTable('page_likes', function () use ($viewer): void {
            $this
                ->actingAs($viewer)
                ->get(route('pages'))
                ->assertOk();
        });

        $this->assertLessThanOrEqual(5, $pageLikeQueries);
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function page(User $owner, array $attributes = []): Page
    {
        return Page::factory()
            ->forOwner($owner)
            ->create([
                'title' => 'Original page',
                'description' => 'Original page description.',
                ...$attributes,
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function pagePayload(Pagecategory $category, array $overrides = []): array
    {
        return [
            'name' => 'Updated page title',
            'category' => $category->id,
            'description' => 'Updated page description.',
            ...$overrides,
        ];
    }

    private function assertControllerRequestType(string $method, string $requestClass): void
    {
        $parameterType = (new ReflectionMethod(PageController::class, $method))
            ->getParameters()[0]
            ->getType();

        $this->assertSame($requestClass, $parameterType?->getName());
        $this->assertTrue(method_exists($requestClass, 'authorize'));
    }

    private function countQueriesForTable(string $table, callable $callback): int
    {
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries, $table): void {
            $sql = strtolower($query->sql);

            if (str_contains($sql, 'from "'.$table.'"') || str_contains($sql, 'join "'.$table.'"')) {
                $queries[] = $sql;
            }
        });

        $callback();

        return count($queries);
    }
}
