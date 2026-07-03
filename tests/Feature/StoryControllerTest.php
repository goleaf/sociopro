<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Http\Controllers\StoryController;
use App\Models\MediaFile;
use App\Models\Stories;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class StoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private const METHODS = [
        '__construct',
        'stories',
        'story_details',
        'single_story_details',
        'create_story',
    ];

    /**
     * @var array<string, array{0: string, 1: string, 2: list<string>}>
     */
    private const ROUTES = [
        'create_story' => ['create_story', 'create_story', ['POST']],
        'stories' => ['stories', 'stories/{offset?}/{limit?}', ['GET', 'POST']],
        'story_details' => ['story_details', 'story_details/{story_id}/{offset?}/{limit?}', ['GET', 'POST']],
        'single_story_details' => ['single_story_details', 'single_story_details/{story_id}', ['GET', 'POST']],
    ];

    public function test_requested_story_controller_methods_stay_public(): void
    {
        $controller = new ReflectionClass(StoryController::class);

        foreach (self::METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "StoryController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "StoryController::{$method} should stay public.");
        }
    }

    public function test_story_routes_keep_expected_contracts(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $uri, $verbs]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(StoryController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");

            foreach ($verbs as $verb) {
                $this->assertContains($verb, $route->methods(), "Route [{$routeName}] should support [{$verb}].");
            }

            foreach (['auth', 'user', 'verified', 'activity'] as $middleware) {
                $this->assertContains($middleware, $route->gatherMiddleware(), "Route [{$routeName}] should include [{$middleware}].");
            }
        }
    }

    public function test_story_partials_return_visible_story_data(): void
    {
        $viewer = $this->activeUser(['name' => 'Story Viewer']);
        $friend = $this->activeUser(['name' => 'Story Friend', 'friends' => json_encode([(int) $viewer->id])]);
        $stranger = $this->activeUser(['name' => 'Story Stranger']);
        $visibleStory = Stories::factory()->forUser($friend)->text('Feature Visible Story')->create([
            'privacy' => Visibility::Friends->value,
        ]);
        $secondStory = Stories::factory()->forUser($friend)->text('Feature Second Story')->create([
            'privacy' => Visibility::Public->value,
        ]);
        Stories::factory()->forUser($stranger)->text('Feature Hidden Stranger Story')->create([
            'privacy' => Visibility::Public->value,
        ]);

        $this->actingAs($viewer);

        $stories = $this->get(route('stories', ['offset' => 0, 'limit' => 5]))->assertOk();
        $this->assertSame('frontend.story.single_story', $stories->getOriginalContent()->name());
        $this->assertSame([$secondStory->story_id, $visibleStory->story_id], $stories->viewData('stories')->pluck('story_id')->all());
        $stories->assertSee('Feature Visible Story');
        $stories->assertDontSee('Feature Hidden Stranger Story');

        $details = $this->get(route('story_details', ['story_id' => $visibleStory->story_id]))->assertOk();
        $this->assertSame('frontend.story.story_details', $details->getOriginalContent()->name());
        $this->assertTrue($visibleStory->is($details->viewData('story_details')));
        $this->assertSame([$secondStory->story_id], $details->viewData('stories')->pluck('story_id')->all());
        $details->assertSee('Feature Visible Story');

        $single = $this->get(route('single_story_details', ['story_id' => $visibleStory->story_id]))->assertOk();
        $this->assertSame('frontend.story.single_story_details', $single->getOriginalContent()->name());
        $this->assertTrue($visibleStory->is($single->viewData('story_details')));
        $single->assertSee('Feature Visible Story');
    }

    public function test_create_story_stores_text_story_for_authenticated_user(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post(route('create_story'), [
                'publisher' => 'user',
                'content_type' => 'text',
                'color' => 'ffffff',
                'bg-color' => '112233',
                'description' => 'Feature Created Text Story',
                'privacy' => Visibility::Friends->value,
            ])
            ->assertRedirect(route('timeline'));

        $story = Stories::query()->where('description', 'like', '%Feature Created Text Story%')->firstOrFail();

        $this->assertSame($user->id, $story->user_id);
        $this->assertSame($user->id, $story->publisher_id);
        $this->assertSame('user', $story->publisher);
        $this->assertSame('text', $story->content_type);
        $this->assertSame(Visibility::Friends->value, $story->privacy);
        $this->assertSame(ContentStatus::Active->value, $story->status);
        $this->assertSame([
            'color' => 'ffffff',
            'bg-color' => '112233',
            'text' => 'Feature Created Text Story',
        ], json_decode($story->description, true));
    }

    public function test_create_story_redirects_without_empty_text_story(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post(route('create_story'), [
                'publisher' => 'user',
                'content_type' => 'text',
                'color' => 'ffffff',
                'bg-color' => '112233',
                'description' => '',
                'privacy' => Visibility::Public->value,
            ])
            ->assertRedirect(route('timeline'));

        $this->assertDatabaseCount('stories', 0);
    }

    public function test_create_story_stores_uploaded_story_media(): void
    {
        Storage::fake('public');
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post(route('create_story'), [
                'publisher' => 'user',
                'content_type' => 'file',
                'privacy' => Visibility::Public->value,
                'story_files' => [
                    UploadedFile::fake()->image('story-photo.jpg', 600, 400),
                ],
            ])
            ->assertRedirect(route('timeline'));

        $story = Stories::query()->where('user_id', $user->id)->firstOrFail();
        $media = MediaFile::query()->where('story_id', $story->story_id)->firstOrFail();

        $this->assertSame($user->id, $media->user_id);
        $this->assertSame('image', $media->file_type);
        $this->assertSame(Visibility::Public->value, $media->privacy);
        Storage::disk('public')->assertExists('story/images/'.$media->file_name);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ], $overrides));
    }
}
