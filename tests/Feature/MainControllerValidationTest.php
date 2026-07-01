<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MainControllerValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_post_invalid_media_uses_legacy_ajax_error_key(): void
    {
        $user = User::factory()->create([
            'friends' => json_encode([]),
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
        ]);

        $response = $this->actingAs($user)->post(route('create_post'), [
            'privacy' => Visibility::Public->value,
            'multiple_files' => [
                UploadedFile::fake()->create('document.pdf', 128, 'application/pdf'),
            ],
        ]);

        $response->assertOk();

        $payload = json_decode($response->getContent(), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('validationError', $payload);
        $this->assertArrayHasKey('multiple_files', $payload['validationError']);
        $this->assertArrayNotHasKey('multiple_files.0', $payload['validationError']);
        $this->assertStringContainsString('post media upload', $payload['validationError']['multiple_files'][0]);
    }
}
