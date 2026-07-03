<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\MediaFileType;
use App\Enums\PostType;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Http\Controllers\Profile;
use App\Models\AlbumImage;
use App\Models\Albums;
use App\Models\Friendships;
use App\Models\MediaFile;
use App\Models\Posts;
use App\Models\User;
use App\Support\Validation\DateTimeRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use ReflectionClass;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_METHODS = [
        '__construct',
        'profile',
        'load_post_by_scrolling',
        'friends',
        'photos',
        'load_photos',
        'album',
        'load_albums',
        'videos',
        'load_videos',
        'load_my_friends',
        'load_my_friend_requests',
        'accept_friend_request',
        'delete_friend_request',
        'about',
        'my_info',
        'upload_photo',
        'update_profile',
        'create_profile_photo_post',
        'create_cover_photo_post',
        'single_post2',
        'savePostList',
        'profileLock',
        'profileUnlock',
        'checkinsView',
    ];

    private const PRIVATE_METHODS = [
        'withProfileSidebarData',
        'profileSidebarMediaFiles',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string}>
     */
    private const ROUTES = [
        'profile' => ['profile', ['GET', 'HEAD'], 'profile'],
        'profile.load_post_by_scrolling' => ['load_post_by_scrolling', ['GET', 'HEAD'], 'profile/load_post_by_scrolling'],
        'profile.friends' => ['friends', ['GET', 'HEAD'], 'profile/friends'],
        'profile.photos' => ['photos', ['GET', 'HEAD'], 'profile/photos'],
        'profile.load_photos' => ['load_photos', ['GET', 'HEAD'], 'profile/load_photos'],
        'profile.load_albums' => ['load_albums', ['GET', 'HEAD'], 'profile/load_albums'],
        'profile.videos' => ['videos', ['GET', 'HEAD'], 'profile/videos'],
        'profile.load_videos' => ['load_videos', ['GET', 'HEAD'], 'profile/load_videos'],
        'profile.load_my_friends' => ['load_my_friends', ['GET', 'HEAD'], 'profile/load_my_friends'],
        'profile.load_my_friend_requests' => ['load_my_friend_requests', ['GET', 'HEAD'], 'profile/load_my_friend_requests'],
        'profile.accept_friend_request' => ['accept_friend_request', ['POST'], 'profile/accept_friend_request'],
        'profile.delete_friend_request' => ['delete_friend_request', ['GET', 'HEAD'], 'profile/delete_friend_request'],
        'profile.about' => ['about', ['POST'], 'profile/about/{action_type?}'],
        'profile.upload_photo' => ['upload_photo', ['POST'], 'profile/upload_photo/{photo_type}'],
        'profile.update_profile' => ['update_profile', ['POST'], 'profile/update_profile'],
        'profile.savePostList' => ['savePostList', ['GET', 'HEAD'], 'profile/save-post-list'],
        'profile.profileLock' => ['profileLock', ['GET', 'HEAD'], 'profile/profile-lock'],
        'profile.profileUnlock' => ['profileUnlock', ['GET', 'HEAD'], 'profile/profile-unlock'],
        'profile.checkins_list' => ['checkinsView', ['GET', 'HEAD'], 'profile/check-ins'],
    ];

    public function test_requested_profile_controller_methods_keep_expected_visibility(): void
    {
        $controller = new ReflectionClass(Profile::class);

        foreach (self::PUBLIC_METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "Profile::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "Profile::{$method} should stay public.");
        }

        foreach (self::PRIVATE_METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "Profile::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPrivate(), "Profile::{$method} should stay private.");
        }
    }

    public function test_requested_profile_routes_keep_expected_actions_methods_uris_and_middleware(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(Profile::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");
            $this->assertContains('auth', $route->gatherMiddleware(), "Route [{$routeName}] should require auth.");
            $this->assertContains('user', $route->gatherMiddleware(), "Route [{$routeName}] should require user middleware.");
        }

        $albumRoute = Route::getRoutes()->getByName('profile.album');
        $this->assertNotNull($albumRoute);
        $this->assertSame(Profile::class.'@album', $albumRoute->getActionName());
        $this->assertSame('profile/album/{action_type?}', $albumRoute->uri());
        $this->assertContains('GET', $albumRoute->methods());
        $this->assertContains('POST', $albumRoute->methods());

        $myInfoRoute = Route::getRoutes()->getByName('profile.my_info');
        $this->assertNotNull($myInfoRoute);
        $this->assertSame(Profile::class.'@my_info', $myInfoRoute->getActionName());
        $this->assertSame('profile/my_info/{action_type?}', $myInfoRoute->uri());
        $this->assertContains('GET', $myInfoRoute->methods());
        $this->assertContains('POST', $myInfoRoute->methods());
    }

    public function test_profile_pages_build_expected_view_data_for_current_user(): void
    {
        $user = $this->activeUser(['about' => 'Profile controller bio']);
        $post = $this->postFor($user, ['description' => 'Profile timeline post']);
        $savedPost = $this->postFor($user, ['description' => 'Saved profile post']);
        $checkinPost = $this->postFor($user, ['description' => 'Checkin profile post', 'location' => 'Vilnius']);
        $user->forceFill(['save_post' => json_encode([$savedPost->post_id])])->save();
        $image = $this->mediaFor($user, $post, MediaFileType::Image, 'profile-photo.jpg');
        $video = $this->mediaFor($user, $post, MediaFileType::Video, 'profile-video.mp4');
        $album = Albums::factory()->create(['user_id' => $user->id, 'title' => 'Profile Album']);
        $friend = $this->activeUser(['name' => 'Profile Friend']);
        Friendships::factory()->accepted()->requester($friend)->accepter($user)->create(['importance' => 7]);
        $requester = $this->activeUser(['name' => 'Profile Requester']);
        Friendships::factory()->pending()->requester($requester)->accepter($user)->create();

        $this->actingAs($user);

        $profile = $this->get(route('profile'))->assertOk();
        $this->assertSame('frontend.profile.index', $profile->viewData('view_path'));
        $this->assertTrue($profile->viewData('posts')->pluck('post_id')->contains($post->post_id));
        $this->assertTrue($profile->viewData('media_files')->pluck('id')->contains($image->id));

        $friends = $this->get(route('profile.friends'))->assertOk();
        $this->assertTrue($friends->viewData('friendships')->pluck('id')->isNotEmpty());
        $this->assertTrue($friends->viewData('friend_requests')->pluck('requester')->contains($requester->id));
        $this->assertTrue($friends->viewData('media_files')->pluck('id')->contains($image->id));

        $photos = $this->get(route('profile.photos'))->assertOk();
        $this->assertTrue($photos->viewData('all_photos')->pluck('id')->contains($image->id));
        $this->assertFalse($photos->viewData('all_photos')->pluck('id')->contains($video->id));
        $this->assertTrue($photos->viewData('all_albums')->pluck('id')->contains($album->id));

        $videos = $this->get(route('profile.videos'))->assertOk();
        $this->assertTrue($videos->viewData('all_videos')->pluck('id')->contains($video->id));
        $this->assertFalse($videos->viewData('all_videos')->pluck('id')->contains($image->id));

        $savedPosts = $this->get(route('profile.savePostList'))->assertOk();
        $this->assertSame([$savedPost->post_id], $savedPosts->viewData('posts')->pluck('post_id')->all());

        $checkins = $this->get(route('profile.checkins_list'))->assertOk();
        $this->assertTrue($checkins->viewData('posts')->pluck('post_id')->contains($checkinPost->post_id));
    }

    public function test_profile_scrolling_and_partial_endpoints_return_current_user_data(): void
    {
        $user = $this->activeUser();
        $post = $this->postFor($user, ['description' => 'Profile scrolling post']);
        $image = $this->mediaFor($user, $post, MediaFileType::Image, 'scroll-photo.jpg');
        $video = $this->mediaFor($user, $post, MediaFileType::Video, 'scroll-video.mp4');
        $album = Albums::factory()->create(['user_id' => $user->id, 'title' => 'Scrolling Album']);
        $friend = $this->activeUser(['name' => 'Scrolling Friend']);
        $requester = $this->activeUser(['name' => 'Scrolling Requester']);
        Friendships::factory()->accepted()->requester($friend)->accepter($user)->create(['importance' => 5]);
        Friendships::factory()->pending()->requester($requester)->accepter($user)->create();

        $this->actingAs($user);

        $posts = $this->get(route('profile.load_post_by_scrolling', ['offset' => 0]))->assertOk();
        $this->assertSame('frontend.main_content.posts', $posts->getOriginalContent()->name());
        $this->assertTrue($posts->viewData('posts')->pluck('post_id')->contains($post->post_id));

        $photos = $this->get(route('profile.load_photos', ['offset' => 0]))->assertOk();
        $this->assertSame('frontend.profile.photo_single', $photos->getOriginalContent()->name());
        $this->assertSame([$image->id], $photos->viewData('all_photos')->pluck('id')->all());

        $albums = $this->get(route('profile.load_albums', ['offset' => 0]))->assertOk();
        $this->assertSame('frontend.profile.album_single', $albums->getOriginalContent()->name());
        $this->assertSame([$album->id], $albums->viewData('all_albums')->pluck('id')->all());

        $videos = $this->get(route('profile.load_videos', ['offset' => 0]))->assertOk();
        $this->assertSame('frontend.profile.video_single', $videos->getOriginalContent()->name());
        $this->assertSame([$video->id], $videos->viewData('all_videos')->pluck('id')->all());

        $friends = $this->get(route('profile.load_my_friends', ['offset' => 0]))->assertOk();
        $this->assertSame('frontend.profile.friends_single_data', $friends->getOriginalContent()->name());
        $this->assertTrue($friends->viewData('friendships')->pluck('requester')->contains($friend->id));

        $requests = $this->get(route('profile.load_my_friend_requests', ['offset' => 0]))->assertOk();
        $this->assertSame('frontend.profile.friend_requests_single_data', $requests->getOriginalContent()->name());
        $this->assertSame([$requester->id], $requests->viewData('friend_requests')->pluck('requester')->all());
    }

    public function test_album_about_my_info_and_friend_request_actions_mutate_current_user_state(): void
    {
        $user = $this->activeUser();
        $requester = $this->activeUser();
        $pending = Friendships::factory()->pending()->requester($requester)->accepter($user)->create();
        $album = Albums::factory()->create(['user_id' => $user->id, 'title' => 'Delete Me']);
        $media = MediaFile::factory()->image()->create([
            'user_id' => $user->id,
            'album_id' => $album->id,
            'file_name' => 'album-delete.jpg',
        ]);

        $this->actingAs($user);

        $this->get(route('profile.album', ['action_type' => 'form']))
            ->assertOk()
            ->assertViewIs('frontend.profile.album_create_form');

        [$storeResponse, $storeOutput] = $this->captureEchoedResponse(fn () => $this->post(route('profile.album', ['action_type' => 'store']), [
            'title' => 'Stored Album',
            'sub_title' => 'Stored subtitle',
            'privacy' => Visibility::Public->value,
        ]));
        $storeResponse->assertOk();
        $this->assertStringContainsString('hideCustomModal', $storeOutput);
        $this->assertDatabaseHas('albums', ['user_id' => $user->id, 'title' => 'Stored Album']);

        $this->get(route('profile.album', ['action_type' => 'delete', 'album_id' => $album->id]))
            ->assertOk()
            ->assertSee('Album deleted successfully', false);
        $this->assertDatabaseMissing('albums', ['id' => $album->id]);
        $this->assertDatabaseMissing('media_files', ['id' => $media->id]);

        $this->post(route('profile.accept_friend_request'), ['user_id' => $requester->id])
            ->assertOk()
            ->assertSee('Friend request accepted', false);
        $this->assertDatabaseHas('friendships', ['id' => $pending->id, 'is_accepted' => 1]);

        $deleteRequester = $this->activeUser();
        Friendships::factory()->pending()->requester($deleteRequester)->accepter($user)->create();
        $this->get(route('profile.delete_friend_request', ['user_id' => $deleteRequester->id]))
            ->assertOk()
            ->assertSee('Friend request deleted', false);
        $this->assertDatabaseMissing('friendships', [
            'requester' => $deleteRequester->id,
            'accepter' => $user->id,
            'is_accepted' => 0,
        ]);

        $this->post(route('profile.about', ['action_type' => 'update']), ['about' => "Updated\nBio"])
            ->assertOk()
            ->assertSee('Your bio updated', false);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'about' => "Updated\nBio"]);

        $this->get(route('profile.my_info', ['action_type' => 'edit']))
            ->assertOk()
            ->assertViewIs('frontend.profile.my_info_edit');

        $this->post(route('profile.my_info', ['action_type' => 'update']), [
            'job' => 'Engineer',
            'studied_at' => 'Vilnius University',
            'address' => 'Vilnius',
            'gender' => 'male',
        ])->assertOk()
            ->assertSee('Profile info updated', false);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'job' => 'Engineer', 'address' => 'Vilnius']);
    }

    public function test_profile_update_upload_lock_unlock_checkins_and_photo_post_helpers(): void
    {
        Storage::fake('public');

        $user = $this->activeUser([
            'name' => 'Profile Before Update',
            'profile_status' => 'unlock',
        ]);
        $publicPost = $this->postFor($user, ['privacy' => Visibility::Public->value]);

        $this->actingAs($user);

        $this->post(route('profile.upload_photo', ['photo_type' => PostType::CoverPhoto->value]), [
            'cover_photo' => UploadedFile::fake()->image('cover.jpg', 1200, 600),
        ])->assertOk()
            ->assertExactJson(['reload' => 1]);
        $this->assertNotEmpty($user->refresh()->cover_photo);

        $this->post(route('profile.upload_photo', ['photo_type' => 'avatar']), [])
            ->assertOk()
            ->assertSee('Invalid photo type', false);

        $this->post(route('profile.update_profile'), [
            'name' => 'Profile After Update',
            'nickname' => 'After',
            'marital_status' => 'single',
            'phone' => '15550000000',
            'date_of_birth' => '1990-01-02',
        ])->assertOk()
            ->assertExactJson(['reload' => 1]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Profile After Update',
            'date_of_birth' => DateTimeRules::birthDateTimestamp('1990-01-02'),
        ]);

        $this->get(route('profile.profileLock'))->assertRedirect();
        $this->assertSame('lock', $user->refresh()->profile_status);
        $this->assertDatabaseHas('posts', [
            'post_id' => $publicPost->post_id,
            'privacy' => Visibility::Friends->value,
        ]);

        $this->get(route('profile.profileUnlock'))->assertRedirect();
        $this->assertSame('unlock', $user->refresh()->profile_status);

        $controller = app(Profile::class);
        $this->setProfileControllerUser($controller, $user);
        $controller->create_profile_photo_post(UploadedFile::fake()->image('profile-helper.jpg', 400, 400), 'profile-helper.jpg');
        $controller->create_cover_photo_post(UploadedFile::fake()->image('cover-helper.jpg', 400, 400), 'cover-helper.jpg');

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'post_type' => PostType::ProfilePicture->value,
        ]);
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'post_type' => PostType::CoverPhoto->value,
        ]);
        $this->assertDatabaseHas('media_files', [
            'user_id' => $user->id,
            'file_name' => 'profile-helper.jpg',
            'file_type' => MediaFileType::Image->value,
        ]);
        $this->assertDatabaseHas('media_files', [
            'user_id' => $user->id,
            'file_name' => 'cover-helper.jpg',
            'file_type' => MediaFileType::Image->value,
        ]);

        $albumImage = AlbumImage::factory()->create([
            'user_id' => $user->id,
            'image' => 'single-post-image.jpg',
        ]);
        $singlePost = app(Profile::class)->single_post2($albumImage->id);
        $this->assertSame('frontend.index', $singlePost->name());
        $this->assertSame('frontend.profile.test', $singlePost->getData()['view_path']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Profile Test User',
            'email_verified_at' => now(),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
            'about' => 'Profile test bio',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function postFor(User $user, array $overrides = []): Posts
    {
        return Posts::factory()->forOwner($user)->create(array_merge([
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => PostType::General->value,
            'privacy' => Visibility::Public->value,
            'tagged_user_ids' => json_encode([]),
            'activity_id' => 0,
            'location' => '',
            'description' => 'Profile test post',
            'status' => ContentStatus::Active->value,
        ], $overrides));
    }

    private function mediaFor(User $user, Posts $post, MediaFileType $type, string $fileName): MediaFile
    {
        return MediaFile::factory()
            ->{$type === MediaFileType::Image ? 'image' : 'video'}()
            ->create([
                'user_id' => $user->id,
                'post_id' => $post->post_id,
                'story_id' => null,
                'product_id' => null,
                'page_id' => null,
                'group_id' => null,
                'chat_id' => null,
                'file_name' => $fileName,
                'file_type' => $type->value,
                'privacy' => Visibility::Public->value,
            ]);
    }

    private function setProfileControllerUser(Profile $controller, User $user): void
    {
        $reflection = new ReflectionClass($controller);
        $property = $reflection->getProperty('user');
        $property->setAccessible(true);
        $property->setValue($controller, $user);
    }

    /**
     * @return array{0: TestResponse, 1: string}
     */
    private function captureEchoedResponse(callable $request): array
    {
        ob_start();
        $response = $request();
        $output = ob_get_clean();

        return [$response, $output === false ? '' : $output];
    }
}
