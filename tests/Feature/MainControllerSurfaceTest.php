<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Http\Controllers\MainController;
use App\Models\BlockUser;
use App\Models\Comments;
use App\Models\MediaFile;
use App\Models\Posts;
use App\Models\PostShare;
use App\Models\Report;
use App\Models\User;
use App\Rules\PostMediaFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class MainControllerSurfaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const PUBLIC_METHODS = [
        '__construct',
        'timeline',
        'load_post_by_scrolling',
        'create_post',
        'live_streaming',
        'edit_post_form',
        'edit_post',
        'create_live_streaming',
        'live',
        'live_ended',
        'search_friends_for_tagging',
        'my_react',
        'my_comment_react',
        'load_post_comments',
        'post_comment',
        'preview_post',
        'post_comment_count',
        'single_post',
        'save_post_report',
        'comment_delete',
        'share_group',
        'share_my_timeline',
        'post_delete',
        'custom_shared_post_view',
        'delete_media_file',
        'addons_manager',
        'user_settings',
        'save_user_settings',
        'updateThemeColor',
        'details_album',
        'block_user',
        'block_user_post',
        'unblock_user',
        'save_post',
        'unsave_post',
    ];

    /**
     * @var list<string>
     */
    private const PRIVATE_HELPERS = [
        'postMediaValidationErrors',
        'hasPostMediaFiles',
        'zoomApiKey',
        'zoomSdkSignature',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string}>
     */
    private const ROUTES = [
        'timeline' => ['timeline', ['GET', 'HEAD'], '/'],
        'create_post' => ['create_post', ['POST'], 'create_post'],
        'edit_post_form' => ['edit_post_form', ['GET', 'HEAD'], 'edit_post_form/{id}'],
        'edit_post' => ['edit_post', ['POST'], 'edit_post/{id}'],
        'load_post_by_scrolling' => ['load_post_by_scrolling', ['GET', 'HEAD'], 'load_post_by_scrolling'],
        'my_react' => ['my_react', ['POST'], 'my_react'],
        'my_comment_react' => ['my_comment_react', ['GET', 'HEAD'], 'my_comment_react'],
        'post_comment' => ['post_comment', ['GET', 'HEAD'], 'post_comment'],
        'load_post_comments' => ['load_post_comments', ['GET', 'HEAD'], 'load_post_comments'],
        'search_friends_for_tagging' => ['search_friends_for_tagging', ['GET', 'HEAD'], 'search_friends_for_tagging'],
        'save_post' => ['save_post', ['GET', 'HEAD'], 'save-post/{id}'],
        'unsave_post' => ['unsave_post', ['GET', 'HEAD'], 'unsave-post/{id}'],
        'live' => ['live', ['GET', 'HEAD'], 'live/{post_id}'],
        'zoom-meeting-leave-url' => ['live_ended', ['GET', 'HEAD'], 'live-ended/{post_id}'],
        'single.post' => ['single_post', ['GET', 'HEAD'], 'view/single/post/{id?}'],
        'preview_post' => ['preview_post', ['GET', 'HEAD'], 'preview_post'],
        'post_comment_count' => ['post_comment_count', ['GET', 'HEAD'], 'post_comment_count'],
        'save.post.report' => ['save_post_report', ['POST'], 'post/report/save'],
        'post.delete' => ['post_delete', ['GET', 'HEAD'], 'delete/my/post'],
        'comment.delete' => ['comment_delete', ['GET', 'HEAD'], 'comment/delete'],
        'share.group.post' => ['share_group', ['POST'], 'share/on/group'],
        'share.my.timeline' => ['share_my_timeline', ['POST'], 'share/on/my/timeline'],
        'custom.shared.post.view' => ['custom_shared_post_view', ['GET', 'HEAD'], 'custom/shared/post/view/{id}'],
        'media.file.delete' => ['delete_media_file', ['GET', 'HEAD'], 'media/file/delete/{id}'],
        'addons.manager' => ['addons_manager', ['GET', 'HEAD'], 'addons/manager'],
        'user.settings' => ['user_settings', ['GET', 'HEAD'], 'user/settings'],
        'save.payment.settings' => ['save_user_settings', ['POST'], 'save/user/settings'],
        'go.live' => ['live_streaming', ['GET', 'HEAD'], 'streaming/live/{id}'],
        'update-theme-color' => ['updateThemeColor', ['POST'], 'update-theme-color'],
        'album.details.page_show' => ['details_album', ['GET', 'HEAD'], 'album/details/page_show/{id}'],
        'block_user' => ['block_user', ['GET', 'HEAD'], 'block_user/{id}'],
        'block_user_post' => ['block_user_post', ['POST'], 'block_user_post/{id}'],
        'unblock_user' => ['unblock_user', ['GET', 'HEAD'], 'unblock_user/{id}'],
    ];

    public function test_requested_main_controller_methods_keep_expected_visibility(): void
    {
        $controller = new ReflectionClass(MainController::class);

        foreach (self::PUBLIC_METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "MainController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "MainController::{$method} should stay public.");
        }

        foreach (self::PRIVATE_HELPERS as $method) {
            $this->assertTrue($controller->hasMethod($method), "MainController::{$method} helper is missing.");
            $this->assertTrue($controller->getMethod($method)->isPrivate(), "MainController::{$method} should stay private.");
        }
    }

    public function test_requested_main_controller_routes_keep_expected_actions_methods_and_uris(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(MainController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");
        }
    }

    public function test_private_helpers_are_not_registered_as_routes(): void
    {
        $registeredActions = [];

        foreach (Route::getRoutes() as $route) {
            $registeredActions[] = $route->getActionName();
        }

        foreach (self::PRIVATE_HELPERS as $method) {
            $this->assertNotContains(
                MainController::class.'@'.$method,
                $registeredActions,
                "{$method} is an internal helper and must not be registered as a web route."
            );
        }
    }

    public function test_private_media_helpers_report_file_presence_and_nested_validation_errors(): void
    {
        $controller = app(MainController::class);
        $reflection = new ReflectionClass($controller);

        $hasPostMediaFiles = $reflection->getMethod('hasPostMediaFiles');
        $postMediaValidationErrors = $reflection->getMethod('postMediaValidationErrors');

        $emptyRequest = Request::create('/create_post', 'POST');
        $invalidFileRequest = Request::create('/create_post', 'POST', [], [], [
            'multiple_files' => [
                UploadedFile::fake()->create('unsafe-document.pdf', 32, 'application/pdf'),
            ],
        ]);

        $this->assertFalse($hasPostMediaFiles->invoke($controller, $emptyRequest));
        $this->assertTrue($hasPostMediaFiles->invoke($controller, $invalidFileRequest));

        $errors = $postMediaValidationErrors->invoke($controller, $invalidFileRequest, PostMediaFile::forCreate());

        $this->assertIsArray($errors);
        $this->assertArrayHasKey('multiple_files', $errors);
        $this->assertArrayNotHasKey('multiple_files.0', $errors);
    }

    public function test_legacy_ajax_endpoints_update_posts_comments_reports_shares_blocks_media_and_settings(): void
    {
        $user = $this->activeUser();
        $otherUser = $this->activeUser(['email' => 'main-other@example.test', 'name' => 'Main Other User']);
        $post = $this->postFor($user, ['description' => 'Main controller behavior post']);
        $otherPost = $this->postFor($otherUser, ['description' => 'Main controller block target']);

        $this->actingAs($user)
            ->post(route('my_react'), [
                'type' => 'post',
                'post_id' => $post->post_id,
                'request_type' => 'update',
                'react' => 'love',
                'response_type' => 'number',
            ])
            ->assertOk()
            ->assertContent('1');

        $this->assertSame('love', json_decode((string) $post->refresh()->user_reacts, true)[$user->id]);

        $this->actingAs($user)
            ->get(route('post_comment', [
                'description' => 'Main controller behavior comment',
                'comment_id' => 0,
                'parent_id' => 0,
                'type' => 'post',
                'post_id' => $post->post_id,
            ]))
            ->assertOk()
            ->assertSee('Main controller behavior comment');

        $comment = Comments::query()
            ->where('user_id', $user->id)
            ->where('id_of_type', $post->post_id)
            ->where('description', 'Main controller behavior comment')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('my_comment_react', [
                'comment_id' => $comment->comment_id,
                'request_type' => 'update',
                'react' => 'haha',
            ]))
            ->assertOk();

        $this->assertSame('haha', json_decode((string) $comment->refresh()->user_reacts, true)[$user->id]);

        $this->actingAs($user)
            ->get(route('post_comment_count', ['type' => 'post', 'post_id' => $post->post_id]))
            ->assertOk()
            ->assertContent('1');

        $this->actingAs($user)
            ->post(route('save.post.report'), [
                'post_id' => $post->post_id,
                'report' => 'Main controller report reason',
            ])
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this->assertSame(1, Report::query()->where('user_id', $user->id)->where('post_id', $post->post_id)->count());

        $this->actingAs($user)
            ->post(route('share.my.timeline'), [
                'shared_post_id' => $post->post_id,
                'postUrl' => 'https://sociopro.test/view/single/post/'.$post->post_id,
            ])
            ->assertOk()
            ->assertJson(['url' => route('profile')]);

        $this->actingAs($user)
            ->post(route('share.group.post'), [
                'shared_post_id' => $post->post_id,
                'group_id' => 77,
                'message' => 'Main controller shared to group',
            ])
            ->assertOk()
            ->assertSee('Posted On Group Successfully', false);

        $this->assertSame(2, PostShare::query()->where('user_id', $user->id)->where('post_id', $post->post_id)->count());

        $this->actingAs($user)
            ->from(route('timeline'))
            ->get(route('save_post', $post->post_id))
            ->assertRedirect(route('timeline'));

        $this->assertContains((string) $post->post_id, array_map('strval', json_decode((string) $user->refresh()->save_post, true)));

        $this->actingAs($user)
            ->from(route('timeline'))
            ->get(route('unsave_post', $post->post_id))
            ->assertRedirect(route('timeline'));

        $this->assertNotContains((string) $post->post_id, array_map('strval', json_decode((string) $user->refresh()->save_post, true)));

        $this->actingAs($user)
            ->post(route('block_user_post', $otherPost->post_id))
            ->assertRedirect(route('timeline'));

        $block = BlockUser::query()
            ->where('user_id', $user->id)
            ->where('block_user', $otherUser->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->from(route('timeline'))
            ->get(route('unblock_user', $block->id))
            ->assertRedirect(route('timeline'));

        $this->assertDatabaseMissing('block_users', ['id' => $block->id]);

        $media = $this->mediaFor($user, $post, 'main-controller-feature/deletable.jpg');

        $this->actingAs($user)
            ->get(route('media.file.delete', $media->id))
            ->assertOk()
            ->assertSee('Image deleted successfully', false);

        $this->assertDatabaseMissing('media_files', ['id' => $media->id]);
        $this->assertFileDoesNotExist(public_path('storage/post/images/main-controller-feature/deletable.jpg'));

        $this->actingAs($user)
            ->from(route('user.settings'))
            ->post(route('save.payment.settings'), [
                'raz_key_id' => 'feature-razorpay-key',
                'theme_color' => '#123456',
                'stripe_live' => 'on',
            ])
            ->assertRedirect(route('user.settings'));

        $settings = json_decode((string) $user->refresh()->payment_settings, true);

        $this->assertSame('feature-razorpay-key', $settings['raz_key_id']);
        $this->assertSame('#123456', $settings['theme_color']);
        $this->assertTrue($settings['stripe_live']);

        $this->actingAs($user)
            ->postJson(route('update-theme-color'), ['themeColor' => 'dark'])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertSessionHas('theme_color', 'dark');

        $deletableComment = Comments::query()->create([
            'parent_id' => 0,
            'user_id' => $user->id,
            'is_type' => 'post',
            'id_of_type' => $post->post_id,
            'description' => 'Main controller delete comment',
            'user_reacts' => json_encode([]),
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->actingAs($user)
            ->get(route('comment.delete', ['comment_id' => $deletableComment->comment_id]))
            ->assertOk()
            ->assertSee('Comment Deleted Successfully', false);

        $this->assertDatabaseMissing('comments', ['comment_id' => $deletableComment->comment_id]);

        $deletablePost = $this->postFor($user, ['description' => 'Main controller delete post']);

        $this->actingAs($user)
            ->get(route('post.delete', ['post_id' => $deletablePost->post_id]))
            ->assertOk()
            ->assertSee('Post Deleted Successfully', false);

        $this->assertDatabaseMissing('posts', ['post_id' => $deletablePost->post_id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create($overrides + [
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function postFor(User $user, array $overrides = []): Posts
    {
        return Posts::query()->create($overrides + [
            'user_id' => $user->id,
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => 'general',
            'privacy' => Visibility::Public->value,
            'tagged_user_ids' => json_encode([]),
            'location' => '',
            'description' => 'Main controller post',
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
            'user_reacts' => json_encode([]),
            'shared_user' => json_encode([]),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function mediaFor(User $user, Posts $post, string $fileName): MediaFile
    {
        File::ensureDirectoryExists(public_path('storage/post/images/'.dirname($fileName)));
        File::put(public_path('storage/post/images/'.$fileName), 'feature image');

        return MediaFile::query()->create([
            'user_id' => $user->id,
            'post_id' => $post->post_id,
            'file_name' => $fileName,
            'file_type' => 'image',
            'privacy' => Visibility::Public->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }
}
