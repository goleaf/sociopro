<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\VideoCategory;
use App\Enums\Visibility;
use App\Http\Controllers\VideoController;
use App\Models\Posts;
use App\Models\SaveForLater;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class VideoControllerTest extends TestCase
{
    use RefreshDatabase;

    private const METHODS = [
        'videos',
        'store',
        'videoinfo',
        'load_videos_by_scrolling',
        'shorts',
        'load_shorts_by_scrolling',
        'save_for_later',
        'unsave_for_later',
        'save_all',
        'video_delete',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string}>
     */
    private const ROUTES = [
        'videos' => ['videos', ['GET', 'HEAD'], 'videos'],
        'videos.store' => ['store', ['POST'], 'videos/sorts/store'],
        'video.detail.info' => ['videoinfo', ['GET', 'HEAD'], 'video/details/info/{id}'],
        'shorts' => ['shorts', ['GET', 'HEAD'], 'shorts'],
        'save.video.later' => ['save_for_later', ['GET', 'HEAD'], 'save/video/short/{id}'],
        'load_videos_by_scrolling' => ['load_videos_by_scrolling', ['GET', 'HEAD'], 'load_videos_by_scrolling'],
        'load_shorts_by_scrolling' => ['load_shorts_by_scrolling', ['GET', 'HEAD'], 'load_shorts_by_scrolling'],
        'unsave.video.later' => ['unsave_for_later', ['GET', 'HEAD'], 'unsave/video/short/{id}'],
        'save.all.view' => ['save_all', ['GET', 'HEAD'], 'saved/video/view'],
        'video.delete' => ['video_delete', ['GET', 'HEAD'], 'video/delete'],
    ];

    public function test_requested_video_controller_methods_stay_public(): void
    {
        $controller = new ReflectionClass(VideoController::class);

        foreach (self::METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "VideoController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "VideoController::{$method} should stay public.");
        }
    }

    public function test_requested_video_routes_keep_expected_actions_methods_and_uris(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(VideoController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");
        }
    }

    public function test_video_and_short_listing_routes_render_public_category_items(): void
    {
        $viewer = $this->activeUser();
        $owner = $this->activeUser();
        $video = $this->videoWithPost($owner, 'Feature Public Video');
        $hiddenVideo = $this->videoWithPost($owner, 'Feature Hidden Video', VideoCategory::Video, Visibility::Private);
        $short = $this->videoWithPost($owner, 'Feature Public Short', VideoCategory::Shorts);

        $this
            ->actingAs($viewer)
            ->get(route('videos'))
            ->assertOk()
            ->assertViewIs('frontend.index')
            ->assertSee('id="video-'.$video->id.'"', false)
            ->assertDontSee('id="video-'.$hiddenVideo->id.'"', false)
            ->assertDontSee('id="video-'.$short->id.'"', false);

        $this
            ->actingAs($viewer)
            ->get(route('load_videos_by_scrolling', ['offset' => 0]))
            ->assertOk()
            ->assertViewIs('frontend.video-shorts.single-video')
            ->assertSee('id="video-'.$video->id.'"', false)
            ->assertDontSee('id="video-'.$hiddenVideo->id.'"', false)
            ->assertDontSee('id="video-'.$short->id.'"', false);

        $this
            ->actingAs($viewer)
            ->get(route('shorts'))
            ->assertOk()
            ->assertViewIs('frontend.index')
            ->assertSee($short->title)
            ->assertDontSee($video->title)
            ->assertDontSee($hiddenVideo->title);

        $this
            ->actingAs($viewer)
            ->get(route('load_shorts_by_scrolling', ['offset' => 0]))
            ->assertOk()
            ->assertViewIs('frontend.video-shorts.shorts-single')
            ->assertSee($short->title)
            ->assertDontSee($video->title)
            ->assertDontSee($hiddenVideo->title);
    }

    public function test_video_detail_renders_and_records_current_user_view_once(): void
    {
        $viewer = $this->activeUser();
        $owner = $this->activeUser();
        $video = $this->videoWithPost($owner, 'Feature Detail Video');

        $this
            ->actingAs($viewer)
            ->get(route('video.detail.info', ['id' => $video->id]))
            ->assertOk()
            ->assertViewIs('frontend.index')
            ->assertSee($video->title);

        $video->refresh();
        $this->assertSame([$viewer->id], json_decode($video->view, true));

        $this
            ->actingAs($viewer)
            ->get(route('video.detail.info', ['id' => $video->id]))
            ->assertOk();

        $video->refresh();
        $this->assertSame([$viewer->id], json_decode($video->view, true));
    }

    public function test_store_creates_video_and_companion_post_from_uploaded_media(): void
    {
        Storage::fake('public');

        $viewer = $this->activeUser();

        $response = $this
            ->actingAs($viewer)
            ->post(route('videos.store'), [
                'title' => 'Feature Uploaded Video',
                'privacy' => Visibility::Public->value,
                'category' => VideoCategory::Video->value,
                'video' => UploadedFile::fake()->create('feature-upload.mp4', 128, 'video/mp4'),
                'mobile_app_image' => UploadedFile::fake()->create('feature-cover.jpg', 8, 'image/jpeg'),
            ]);

        $response
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $video = Video::query()
            ->where('user_id', $viewer->id)
            ->where('title', 'Feature Uploaded Video')
            ->firstOrFail();

        $this->assertSame(Visibility::Public->value, $video->privacy);
        $this->assertSame(VideoCategory::Video->value, $video->category);
        $this->assertNotEmpty($video->file);
        $this->assertNotEmpty($video->mobile_app_image);

        Storage::disk('public')->assertExists('videos/'.$video->file);
        Storage::disk('public')->assertExists('videos/'.$video->mobile_app_image);

        $this->assertDatabaseHas('posts', [
            'user_id' => $viewer->id,
            'publisher' => 'video_and_shorts',
            'publisher_id' => $video->id,
            'post_type' => VideoCategory::Video->value,
            'privacy' => Visibility::Public->value,
            'description' => 'Feature Uploaded Video',
            'status' => ContentStatus::Active->value,
        ]);
    }

    public function test_save_routes_are_idempotent_and_scoped_to_current_user(): void
    {
        $viewer = $this->activeUser();
        $otherUser = $this->activeUser();
        $owner = $this->activeUser();
        $video = $this->videoWithPost($owner, 'Feature Saved Video');
        $otherVideo = $this->videoWithPost($owner, 'Feature Other Saved Video');
        $this->saveVideoFor($otherUser, $video);
        $this->saveVideoFor($otherUser, $otherVideo);

        $this
            ->actingAs($viewer)
            ->get(route('save.video.later', ['id' => $video->id]))
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this
            ->actingAs($viewer)
            ->get(route('save.video.later', ['id' => $video->id]))
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this->assertSame(1, SaveForLater::query()
            ->where('user_id', $viewer->id)
            ->where('video_id', $video->id)
            ->count());

        $this
            ->actingAs($viewer)
            ->get(route('save.all.view'))
            ->assertOk()
            ->assertViewIs('frontend.index')
            ->assertSee($video->title)
            ->assertDontSee($otherVideo->title);

        $this
            ->actingAs($viewer)
            ->get(route('unsave.video.later', ['id' => $video->id]))
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this->assertDatabaseMissing('saveforlaters', [
            'user_id' => $viewer->id,
            'video_id' => $video->id,
        ]);
        $this->assertDatabaseHas('saveforlaters', [
            'user_id' => $otherUser->id,
            'video_id' => $video->id,
        ]);
    }

    public function test_video_delete_removes_only_current_users_video(): void
    {
        $viewer = $this->activeUser();
        $otherUser = $this->activeUser();
        $ownVideo = $this->videoWithPost($viewer, 'Feature Own Delete Video', file: 'feature-own-delete.mp4');
        $otherVideo = $this->videoWithPost($otherUser, 'Feature Other Delete Video', file: 'feature-other-delete.mp4');
        $this->putLegacyVideoDeleteFiles($ownVideo->file);

        $this
            ->actingAs($viewer)
            ->get(route('video.delete', ['video_id' => $otherVideo->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('videos', ['id' => $otherVideo->id]);

        $this
            ->actingAs($viewer)
            ->get(route('video.delete', ['video_id' => $ownVideo->id]))
            ->assertOk()
            ->assertJson([
                'alertMessage' => 'Video Deleted Successfully',
                'fadeOutElem' => '#video-'.$ownVideo->id,
            ]);

        $this->assertDatabaseMissing('videos', ['id' => $ownVideo->id]);
        $this->assertFileDoesNotExist(public_path('storage/video/coverphoto/'.$ownVideo->file));
        $this->assertFileDoesNotExist(public_path('storage/video/thumbnail/'.$ownVideo->file));
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

    private function videoWithPost(
        User $owner,
        string $title,
        VideoCategory $category = VideoCategory::Video,
        Visibility $privacy = Visibility::Public,
        ?string $file = null,
    ): Video {
        $video = Video::factory()
            ->forOwner($owner)
            ->create([
                'title' => $title,
                'category' => $category->value,
                'privacy' => $privacy->value,
                'file' => $file ?? str($title)->slug()->append('.mp4')->toString(),
                'view' => json_encode([]),
            ]);

        Posts::factory()
            ->forOwner($owner)
            ->create([
                'publisher' => 'video_and_shorts',
                'publisher_id' => $video->id,
                'post_type' => $category->value,
                'privacy' => $privacy->value,
                'description' => $title,
                'tagged_user_ids' => json_encode([]),
                'activity_id' => 0,
                'user_reacts' => json_encode([]),
                'status' => ContentStatus::Active->value,
            ]);

        return $video;
    }

    private function saveVideoFor(User $user, Video $video): SaveForLater
    {
        $save = new SaveForLater;
        $save->forceFill([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'group_id' => null,
            'post_id' => null,
            'marketplace_id' => null,
            'event_id' => null,
            'blog_id' => null,
        ]);
        $save->save();

        return $save;
    }

    private function putLegacyVideoDeleteFiles(string $fileName): void
    {
        foreach (['coverphoto', 'thumbnail'] as $directory) {
            $path = public_path('storage/video/'.$directory.'/'.$fileName);

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }

            file_put_contents($path, 'video-delete-fixture');
        }
    }
}
