<?php

namespace Tests\Unit;

use App\Rules\PostMediaFile;
use App\Support\Validation\NestedFileValidationErrors;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    public function test_post_media_file_rule_accepts_legacy_image_and_video_types(): void
    {
        $imageValidator = Validator::make(
            ['file' => UploadedFile::fake()->image('photo.jpg')->size(128)],
            ['file' => [PostMediaFile::forCreate()]]
        );
        $videoValidator = Validator::make(
            ['file' => UploadedFile::fake()->create('clip.mp4', 128, 'video/mp4')],
            ['file' => [PostMediaFile::forCreate()]]
        );

        $this->assertTrue($imageValidator->passes());
        $this->assertTrue($videoValidator->passes());
    }

    public function test_post_media_file_rule_rejects_unknown_file_types_with_translated_message(): void
    {
        $validator = Validator::make(
            ['file' => UploadedFile::fake()->create('document.pdf', 128, 'application/pdf')],
            ['file' => [PostMediaFile::forCreate()]]
        );

        $this->assertFalse($validator->passes());
        $this->assertStringContainsString('post media upload', $validator->errors()->first('file'));
        $this->assertStringContainsString('jpeg, png, jpg, gif, svg, mp4, mov, wmv, avi, webm', $validator->errors()->first('file'));
    }

    public function test_post_media_file_rule_preserves_create_and_update_size_limits(): void
    {
        $largeImage = UploadedFile::fake()->image('photo.jpg')->size(21_000);

        $createValidator = Validator::make(
            ['file' => $largeImage],
            ['file' => [PostMediaFile::forCreate()]]
        );
        $updateValidator = Validator::make(
            ['file' => $largeImage],
            ['file' => [PostMediaFile::forUpdate()]]
        );

        $this->assertTrue($createValidator->passes());
        $this->assertFalse($updateValidator->passes());
        $this->assertStringContainsString('20480 kilobytes', $updateValidator->errors()->first('file'));
    }

    public function test_nested_file_validation_errors_preserve_legacy_parent_error_key(): void
    {
        $errors = NestedFileValidationErrors::collapse([
            'multiple_files.0' => ['Invalid first file.'],
            'multiple_files.1' => ['Invalid second file.'],
        ], 'multiple_files');

        $this->assertSame(['Invalid second file.'], $errors['multiple_files']);
        $this->assertArrayNotHasKey('multiple_files.0', $errors);
        $this->assertArrayNotHasKey('multiple_files.1', $errors);
    }
}
