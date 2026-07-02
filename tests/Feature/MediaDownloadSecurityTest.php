<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Media_files;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaDownloadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('storage/post/images/security-test'));
        File::delete(public_path('storage/post/security-traversal.jpg'));

        parent::tearDown();
    }

    public function test_private_media_download_requires_owner(): void
    {
        $owner = $this->activeUser();
        $viewer = $this->activeUser();
        $fileName = 'security-test/private-image.jpg';

        File::ensureDirectoryExists(public_path('storage/post/images/security-test'));
        File::put(public_path('storage/post/images/'.$fileName), 'private image');

        $media = Media_files::query()->create([
            'user_id' => $owner->id,
            'file_name' => $fileName,
            'file_type' => 'image',
            'privacy' => Visibility::Private->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this
            ->actingAs($viewer)
            ->get(route('download.mediafile.image', $media->id))
            ->assertForbidden();

        $this
            ->actingAs($owner)
            ->get(route('download.mediafile.image', $media->id))
            ->assertOk();
    }

    public function test_media_download_rejects_traversal_file_names(): void
    {
        $owner = $this->activeUser();

        File::ensureDirectoryExists(public_path('storage/post'));
        File::put(public_path('storage/post/security-traversal.jpg'), 'outside image');

        $media = Media_files::query()->create([
            'user_id' => $owner->id,
            'file_name' => '../security-traversal.jpg',
            'file_type' => 'image',
            'privacy' => Visibility::Private->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this
            ->actingAs($owner)
            ->get(route('download.mediafile.image', $media->id))
            ->assertNotFound();
    }

    public function test_public_media_delete_requires_owner(): void
    {
        $owner = $this->activeUser();
        $viewer = $this->activeUser();
        $fileName = 'security-test/public-delete.jpg';
        $filePath = public_path('storage/post/images/'.$fileName);

        File::ensureDirectoryExists(public_path('storage/post/images/security-test'));
        File::put($filePath, 'public image');

        $media = Media_files::query()->create([
            'user_id' => $owner->id,
            'file_name' => $fileName,
            'file_type' => 'image',
            'privacy' => Visibility::Public->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this
            ->actingAs($viewer)
            ->get(route('delete.mediafile', $media->id))
            ->assertForbidden();

        $this->assertDatabaseHas('media_files', ['id' => $media->id]);
        $this->assertFileExists($filePath);

        $this
            ->actingAs($owner)
            ->get(route('delete.mediafile', $media->id))
            ->assertOk();

        $this->assertDatabaseMissing('media_files', ['id' => $media->id]);
        $this->assertFileDoesNotExist($filePath);
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'user_role' => UserRole::General->value,
        ]);
    }
}
