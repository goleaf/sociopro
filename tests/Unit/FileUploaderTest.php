<?php

namespace Tests\Unit;

use App\Support\Files\FileUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class FileUploaderTest extends TestCase
{
    public function test_local_upload_writes_resized_public_files_through_storage_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('test-uploader');
        Storage::disk('public')->makeDirectory('test-uploader/optimized');
        File::ensureDirectoryExists(public_path('storage/test-uploader'));
        File::ensureDirectoryExists(public_path('storage/test-uploader/optimized'));

        $fileName = null;

        try {
            $fileName = FileUploader::upload(
                UploadedFile::fake()->image('banner.jpg', 1200, 800)->size(256),
                'public/storage/test-uploader/custom.jpg',
                800,
                null,
                300
            );

            $this->assertSame('custom.jpg', $fileName);
            Storage::disk('public')->assertExists('test-uploader/custom.jpg');
            Storage::disk('public')->assertExists('test-uploader/optimized/custom.jpg');
            $this->assertSame('public', Storage::disk('public')->getVisibility('test-uploader/custom.jpg'));
        } finally {
            File::deleteDirectory(public_path('storage/test-uploader'));
        }
    }

    public function test_local_upload_rejects_path_traversal_targets(): void
    {
        Storage::fake('public');
        File::ensureDirectoryExists(public_path('storage/test-uploader'));

        try {
            $this->expectException(InvalidArgumentException::class);

            FileUploader::upload(
                UploadedFile::fake()->image('avatar.jpg')->size(128),
                'public/storage/test-uploader/../shell.php'
            );
        } finally {
            File::delete(public_path('storage/shell.php'));
            File::deleteDirectory(public_path('storage/test-uploader'));
        }
    }

    public function test_local_upload_rejects_executable_file_targets(): void
    {
        Storage::fake('public');

        $this->expectException(InvalidArgumentException::class);

        FileUploader::upload(
            UploadedFile::fake()->create('payload.php', 1, 'application/x-php'),
            'public/storage/test-uploader/payload.php'
        );
    }

    public function test_s3_upload_uses_public_visibility_when_enabled(): void
    {
        DB::table('settings')->updateOrInsert(
            ['type' => 'amazon_s3'],
            ['description' => json_encode([
                'active' => '1',
                'AWS_ACCESS_KEY_ID' => 'test-key',
                'AWS_SECRET_ACCESS_KEY' => 'test-secret',
                'AWS_DEFAULT_REGION' => 'us-east-1',
                'AWS_BUCKET' => 'test-bucket',
            ])]
        );
        Storage::fake('s3');

        $url = FileUploader::upload(
            UploadedFile::fake()->image('avatar.jpg')->size(128),
            'public/storage/test-uploader'
        );
        $files = Storage::disk('s3')->allFiles('social-files');

        $this->assertNotEmpty($url);
        $this->assertCount(1, $files);
        $this->assertSame('public', Storage::disk('s3')->getVisibility($files[0]));
    }
}
