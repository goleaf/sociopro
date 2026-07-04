<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Http\Controllers\ApiController;
use App\Models\MediaFile;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class ApiPostAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_post_write_methods_use_dedicated_form_requests(): void
    {
        $this->assertControllerRequestType('create_post', 'App\\Http\\Requests\\Api\\Posts\\StorePostRequest');
        $this->assertControllerRequestType('edit_post', 'App\\Http\\Requests\\Api\\Posts\\UpdatePostRequest');
        $this->assertControllerRequestType('delete_post', 'App\\Http\\Requests\\Api\\Posts\\DestroyPostRequest');
    }

    public function test_api_post_media_delete_method_uses_dedicated_form_request(): void
    {
        $this->assertControllerRequestType('delete_media_file', 'App\\Http\\Requests\\Api\\Posts\\DestroyPostMediaFileRequest');
    }

    public function test_api_user_cannot_update_another_users_post(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $post = Posts::factory()
            ->forOwner($owner)
            ->create([
                'description' => 'Original API post body',
            ]);

        $response = $this
            ->withToken($this->apiTokenFor($otherUser))
            ->postJson(route('api.posts.update', ['id' => $post->post_id]), [
                'privacy' => Visibility::Public->value,
                'description' => 'Cross-owner API edit',
            ]);

        $response->assertForbidden();

        $this->assertSame('Original API post body', $post->refresh()->description);
        $this->assertSame($owner->id, (int) $post->user_id);
    }

    public function test_api_user_cannot_delete_another_users_post(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $post = Posts::factory()
            ->forOwner($owner)
            ->create();

        $response = $this
            ->withToken($this->apiTokenFor($otherUser))
            ->postJson(route('api.posts.destroy', ['id' => $post->post_id]));

        $response->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'post_id' => $post->post_id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_api_user_cannot_delete_another_users_post_media_file(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $post = Posts::factory()
            ->forOwner($owner)
            ->create();
        $mediaFile = MediaFile::factory()
            ->image()
            ->create([
                'user_id' => $owner->id,
                'post_id' => $post->post_id,
            ]);

        $response = $this
            ->withToken($this->apiTokenFor($otherUser))
            ->postJson(route('api.posts.media.destroy', ['id' => $mediaFile->id]));

        $response->assertForbidden();

        $this->assertDatabaseHas('media_files', [
            'id' => $mediaFile->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_delete_post_action_deletes_post_and_returns_legacy_payload(): void
    {
        $owner = $this->activeUser();
        $post = Posts::factory()
            ->forOwner($owner)
            ->create();
        $actionClass = 'App\\Actions\\Posts\\DeletePostAction';

        $this->assertTrue(class_exists($actionClass));

        $payload = app($actionClass)->handle($post);

        $this->assertSame([
            'alertMessage' => get_phrase('Post Deleted Successfully'),
            'fadeOutElem' => '#postIdentification'.$post->post_id,
        ], $payload);
        $this->assertDatabaseMissing('posts', [
            'post_id' => $post->post_id,
        ]);
    }

    public function test_delete_post_media_file_action_deletes_media_and_returns_legacy_payload(): void
    {
        $owner = $this->activeUser();
        $post = Posts::factory()
            ->forOwner($owner)
            ->create();
        $mediaFile = MediaFile::factory()
            ->image()
            ->create([
                'user_id' => $owner->id,
                'post_id' => $post->post_id,
            ]);
        $actionClass = 'App\\Actions\\Posts\\DeletePostMediaFileAction';

        $this->assertTrue(class_exists($actionClass));

        $payload = app($actionClass)->handle($mediaFile);

        $this->assertSame([
            'alertMessage' => get_phrase('Image deleted successfully'),
            'fadeOutElem' => '#previous-uploaded-img-'.$mediaFile->id,
        ], $payload);
        $this->assertDatabaseMissing('media_files', [
            'id' => $mediaFile->id,
        ]);
    }

    public function test_update_post_action_updates_post_and_returns_legacy_payload(): void
    {
        $owner = $this->activeUser();
        $post = Posts::factory()
            ->forOwner($owner)
            ->create([
                'description' => 'Original action body',
            ]);
        $request = Request::create('/api/edit_post/'.$post->post_id, 'POST', [
            'privacy' => Visibility::Public->value,
            'description' => 'Updated action body',
        ]);
        $actionClass = 'App\\Actions\\Posts\\UpdatePostAction';

        $this->assertTrue(class_exists($actionClass));

        $payload = app($actionClass)->handle($post, $owner, $request);

        $this->assertSame([
            'status' => 200,
            'message' => 'Your post successfully updated',
        ], $payload);
        $this->assertSame('Updated action body', $post->refresh()->description);
    }

    public function test_store_post_media_files_action_persists_uploaded_video_metadata(): void
    {
        Storage::fake('public');

        $owner = $this->activeUser();
        $post = Posts::factory()
            ->forOwner($owner)
            ->create();
        $request = Request::create('/api/edit_post/'.$post->post_id, 'POST', [
            'privacy' => Visibility::Public->value,
        ], [], [
            'multiple_files' => [
                UploadedFile::fake()->create('clip.mp4', 128, 'video/mp4'),
            ],
        ]);
        $actionClass = 'App\\Actions\\Posts\\StorePostMediaFilesAction';

        $this->assertTrue(class_exists($actionClass));

        app($actionClass)->handle($post->post_id, $owner, $request);

        $mediaFile = $post->media_files()->first();

        $this->assertNotNull($mediaFile);
        $this->assertSame('video', $mediaFile->file_type);
        $this->assertSame($owner->id, (int) $mediaFile->user_id);
        Storage::disk('public')->assertExists('post/videos/'.$mediaFile->file_name);
    }

    public function test_store_post_action_creates_post_and_returns_legacy_payload(): void
    {
        $owner = $this->activeUser();
        $request = Request::create('/api/create_post', 'POST', [
            'privacy' => Visibility::Public->value,
            'description' => 'Created action body',
        ]);
        $actionClass = 'App\\Actions\\Posts\\StorePostAction';

        $this->assertTrue(class_exists($actionClass));

        $payload = app($actionClass)->handle($owner, $request);

        $this->assertSame([
            'status' => 200,
            'message' => 'Your post successfully publidhed',
        ], $payload);
        $this->assertDatabaseHas('posts', [
            'user_id' => $owner->id,
            'publisher' => 'post',
            'publisher_id' => $owner->id,
            'description' => 'Created action body',
        ]);
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
        ]);
    }

    private function apiTokenFor(User $user): string
    {
        return $user->createToken('api-post-authorization-test')->plainTextToken;
    }

    private function assertControllerRequestType(string $method, string $requestClass): void
    {
        $parameterType = (new ReflectionMethod(ApiController::class, $method))
            ->getParameters()[0]
            ->getType();

        $this->assertSame($requestClass, $parameterType?->getName());
        $this->assertTrue(method_exists($requestClass, 'authorize'));
    }
}
