<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MediaFileType;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Http\Controllers\CustomUserController;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class CustomUserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('storage/post/images/custom-user-test'));
        File::deleteDirectory(public_path('storage/post/videos/custom-user-test'));
        File::delete(public_path('storage/post/custom-user-traversal.jpg'));

        parent::tearDown();
    }

    public function test_custom_user_controller_routes_are_bound_to_expected_methods(): void
    {
        $routes = [
            'user.profile.view' => ['GET', 'HEAD', 'user/view/profile/{id}', 'view_profile_data'],
            'user.load_post_by_scrolling' => ['GET', 'HEAD', 'user/load_post_by_scrolling', 'load_post_by_scrolling'],
            'user.password.change' => ['GET', 'HEAD', 'user/password/change', 'changepass'],
            'user.password.update' => ['POST', 'user/password/update', 'updatepass'],
            'user.friend' => ['GET', 'HEAD', 'user/friend/{id}', 'friend'],
            'user.unfriend' => ['GET', 'HEAD', 'user/unfriend/{id}', 'unfriend'],
            'user.friends' => ['GET', 'HEAD', 'user/friends/{id}', 'friends'],
            'user.photos' => ['GET', 'HEAD', 'user/photos/{id}/{identifire}', 'photos'],
            'user.videos' => ['GET', 'HEAD', 'user/videos/{id}', 'videos'],
            'delete.mediafile' => ['GET', 'HEAD', 'video/delete/{id}', 'delete_mediafile'],
            'download.mediafile' => ['GET', 'HEAD', 'download/media/file/{id}', 'download_mediafile'],
            'download.mediafile.image' => ['GET', 'HEAD', 'download/media/file/image/{id}', 'download_mediafile_image'],
            'user.status' => ['GET', 'HEAD', 'user/status/{id}', 'account_status'],
        ];

        foreach ($routes as $name => $contract) {
            $method = array_pop($contract);
            $uri = array_pop($contract);
            $expectedMethods = $contract;
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route [{$name}] is missing.");

            $actualMethods = $route->methods();
            sort($expectedMethods);
            sort($actualMethods);

            $this->assertSame($uri, $route->uri(), "Route [{$name}] URI changed.");
            $this->assertSame($expectedMethods, $actualMethods, "Route [{$name}] HTTP methods changed.");
            $this->assertSame(CustomUserController::class.'@'.$method, $route->getActionName(), "Route [{$name}] action changed.");
        }
    }

    public function test_custom_user_controller_method_surface_tracks_public_and_private_helpers(): void
    {
        $reflection = new ReflectionClass(CustomUserController::class);

        foreach ([
            'changepass',
            'updatepass',
            'view_profile_data',
            'load_post_by_scrolling',
            'friend',
            'unfriend',
            'friends',
            'photos',
            'videos',
            'delete_mediafile',
            'download_mediafile',
            'download_mediafile_image',
            'account_status',
        ] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing public method [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPublic(), "Method [{$method}] must stay public.");
        }

        foreach ([
            'downloadMediaFile',
            'canDownloadMediaFile',
            'canManageMediaFile',
            'mediaDownloadPath',
            'isSafeMediaFileName',
        ] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing private helper [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPrivate(), "Helper [{$method}] must stay private.");
        }
    }

    public function test_account_status_only_deactivates_the_current_authenticated_user(): void
    {
        $currentUser = $this->activeUser();
        $otherUser = $this->activeUser();

        $this->actingAs($currentUser)
            ->get(route('user.status', $otherUser->id))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'status' => UserAccountStatus::Active->value,
        ]);

        $this->actingAs($currentUser)
            ->get(route('user.status', $currentUser->id))
            ->assertOk()
            ->assertJson([
                'url' => route('login'),
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $currentUser->id,
            'status' => UserAccountStatus::Disabled->value,
        ]);
    }

    public function test_media_private_helpers_enforce_visibility_type_and_path_safety(): void
    {
        $controller = new CustomUserController;
        $reflection = new ReflectionClass($controller);
        $isSafeMediaFileName = $reflection->getMethod('isSafeMediaFileName');
        $mediaDownloadPath = $reflection->getMethod('mediaDownloadPath');
        $canManageMediaFile = $reflection->getMethod('canManageMediaFile');
        $canDownloadMediaFile = $reflection->getMethod('canDownloadMediaFile');

        $owner = $this->activeUser();
        $viewer = $this->activeUser();
        $fileName = 'custom-user-test/private-image.jpg';
        File::ensureDirectoryExists(public_path('storage/post/images/custom-user-test'));
        File::put(public_path('storage/post/images/'.$fileName), 'private image');

        $media = MediaFile::query()->create([
            'user_id' => $owner->id,
            'file_name' => $fileName,
            'file_type' => MediaFileType::Image->value,
            'privacy' => Visibility::Private->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->assertTrue($isSafeMediaFileName->invoke($controller, $fileName));
        $this->assertFalse($isSafeMediaFileName->invoke($controller, '../custom-user-traversal.jpg'));
        $this->assertFalse($isSafeMediaFileName->invoke($controller, 'custom-user-test\\private-image.jpg'));
        $this->assertFalse($isSafeMediaFileName->invoke($controller, ''));

        $this->assertNotNull($mediaDownloadPath->invoke($controller, $media, MediaFileType::Image));
        $this->assertNull($mediaDownloadPath->invoke($controller, $media, MediaFileType::Video));

        $this->actingAs($viewer);
        $this->assertFalse($canManageMediaFile->invoke($controller, $media));
        $this->assertFalse($canDownloadMediaFile->invoke($controller, $media));

        $media->privacy = Visibility::Public->value;
        $media->save();
        $this->assertTrue($canDownloadMediaFile->invoke($controller, $media->refresh()));

        $this->actingAs($owner);
        $this->assertTrue($canManageMediaFile->invoke($controller, $media));
        $this->assertTrue($canDownloadMediaFile->invoke($controller, $media));
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
    }
}
