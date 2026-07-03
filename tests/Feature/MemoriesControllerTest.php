<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Http\Controllers\MemoriesController;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class MemoriesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string}>
     */
    private const ROUTES = [
        'memories' => ['memories', ['GET', 'HEAD'], 'memories'],
        'load.memories' => ['load_memories', ['GET', 'HEAD'], 'load/memories'],
    ];

    public function test_memories_controller_methods_keep_expected_visibility(): void
    {
        $controller = new ReflectionClass(MemoriesController::class);

        foreach (['__construct', 'memories', 'load_memories'] as $method) {
            $this->assertTrue($controller->hasMethod($method), "MemoriesController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "MemoriesController::{$method} should stay public.");
        }
    }

    public function test_memories_routes_keep_expected_actions_methods_uris_and_middleware(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(MemoriesController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");

            foreach (['auth', 'user', 'verified', 'activity', 'prevent-back-history'] as $middleware) {
                $this->assertContains($middleware, $route->middleware(), "Route [{$routeName}] lost [{$middleware}] middleware.");
            }
        }
    }

    public function test_memories_page_shows_only_current_users_past_year_visible_memories(): void
    {
        $viewer = $this->activeUser();
        $otherUser = $this->activeUser();

        $visibleMemory = $this->memoryPost($viewer, 'Feature visible memory');
        $videoMemory = $this->memoryPost($viewer, 'Feature video memory', [
            'publisher' => 'video_and_shorts',
        ]);
        $otherMemory = $this->memoryPost($otherUser, 'Other user memory');
        $currentYearMemory = $this->memoryPost($viewer, 'Current year memory', [
            'posted_on' => now()->toDateTimeString(),
        ]);
        $privateMemory = $this->memoryPost($viewer, 'Private memory', [
            'privacy' => Visibility::Private->value,
        ]);
        $reportedMemory = $this->memoryPost($viewer, 'Reported memory', [
            'report_status' => 1,
        ]);
        $inactiveMemory = $this->memoryPost($viewer, 'Inactive memory', [
            'status' => ContentStatus::Inactive->value,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('memories'));

        $response
            ->assertOk()
            ->assertSee($this->postMarker($visibleMemory), false)
            ->assertSee($this->postMarker($videoMemory), false)
            ->assertDontSee($this->postMarker($otherMemory), false)
            ->assertDontSee($this->postMarker($currentYearMemory), false)
            ->assertDontSee($this->postMarker($privateMemory), false)
            ->assertDontSee($this->postMarker($reportedMemory), false)
            ->assertDontSee($this->postMarker($inactiveMemory), false);
    }

    public function test_load_memories_uses_offset_and_returns_memory_partial_for_current_user(): void
    {
        $viewer = $this->activeUser();
        $otherUser = $this->activeUser();

        $firstMemory = $this->memoryPost($viewer, 'First loaded memory');
        $secondMemory = $this->memoryPost($viewer, 'Second loaded memory');
        $thirdMemory = $this->memoryPost($viewer, 'Third loaded memory');
        $fourthMemory = $this->memoryPost($viewer, 'Fourth loaded memory');
        $otherMemory = $this->memoryPost($otherUser, 'Other loaded memory');

        $response = $this
            ->actingAs($viewer)
            ->get(route('load.memories', ['offset' => 1]));

        $response
            ->assertOk()
            ->assertSee($this->postMarker($thirdMemory), false)
            ->assertSee($this->postMarker($secondMemory), false)
            ->assertSee($this->postMarker($firstMemory), false)
            ->assertDontSee($this->postMarker($fourthMemory), false)
            ->assertDontSee($this->postMarker($otherMemory), false);
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
     * @param  array<string, mixed>  $overrides
     */
    private function memoryPost(User $owner, string $description, array $overrides = []): Posts
    {
        return Posts::factory()
            ->forOwner($owner)
            ->create([
                'publisher' => 'post',
                'publisher_id' => $owner->id,
                'post_type' => 'general',
                'privacy' => Visibility::Public->value,
                'description' => $description,
                'status' => ContentStatus::Active->value,
                'report_status' => 0,
                'posted_on' => now()->subYear()->toDateTimeString(),
                'created_at' => (string) time(),
                'updated_at' => (string) time(),
                ...$overrides,
            ]);
    }

    private function postMarker(Posts $post): string
    {
        return 'copy_post_'.$post->post_id;
    }
}
