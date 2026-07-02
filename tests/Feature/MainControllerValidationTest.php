<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Media_files;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_create_post_with_image_upload_creates_public_post_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'friends' => json_encode([]),
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
        ]);

        $mediaFile = null;

        try {
            $response = $this->actingAs($user)->post(route('create_post'), [
                'privacy' => Visibility::Public->value,
                'description' => 'Image upload regression',
                'multiple_files' => [
                    UploadedFile::fake()->image('post-photo.jpg', 640, 480),
                ],
            ]);

            $response->assertOk();

            $payload = json_decode($response->getContent(), true);

            $this->assertSame(['reload' => 1], $payload);

            $post = Posts::query()
                ->where('user_id', $user->id)
                ->latest('post_id')
                ->first();

            $this->assertNotNull($post);

            $mediaFile = Media_files::query()
                ->where('post_id', $post->post_id)
                ->first();

            $this->assertNotNull($mediaFile);
            $this->assertSame('image', $mediaFile->file_type);
            Storage::disk('public')->assertExists('post/images/'.$mediaFile->file_name);
        } finally {
            if ($mediaFile instanceof Media_files) {
                Storage::disk('public')->delete([
                    'post/images/'.$mediaFile->file_name,
                    'post/images/optimized/'.$mediaFile->file_name,
                ]);
            }
        }
    }

    public function test_payment_settings_ignore_sensitive_raw_request_fields(): void
    {
        $user = User::factory()->create([
            'friends' => json_encode([]),
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
        ]);

        $this->actingAs($user)
            ->from(route('user.settings'))
            ->post(route('save.payment.settings'), [
                '_token' => 'test-token',
                'raz_key_id' => 'razorpay-key',
                'raz_secret_key' => 'razorpay-secret',
                'theme_color' => '#123456',
                'stripe_public_key' => 'stripe-public',
                'stripe_secret_key' => 'stripe-secret',
                'stripe_public_live_key' => 'stripe-live-public',
                'stripe_secret_live_key' => 'stripe-live-secret',
                'paypal_client_id' => 'paypal-client',
                'paypal_secret_key' => 'paypal-secret',
                'paypal_production_client_id' => 'paypal-live-client',
                'paypal_production_secret_key' => 'paypal-live-secret',
                'flutterwave_public_key' => 'flutterwave-public',
                'flutterwave_secret_key' => 'flutterwave-secret',
                'flutterwave_encryption_key' => 'flutterwave-encryption',
                'stripe_live' => 'on',
                'status' => UserAccountStatus::Disabled->value,
                'user_role' => UserRole::Admin->value,
                'payment_settings' => '{"owned":"no"}',
                'unexpected_secret' => 'do-not-store',
            ])
            ->assertRedirect(route('user.settings'));

        $settings = json_decode((string) $user->refresh()->payment_settings, true);

        $this->assertIsArray($settings);
        $this->assertSame('razorpay-key', $settings['raz_key_id']);
        $this->assertTrue($settings['stripe_live']);
        $this->assertFalse($settings['paypal_live']);
        $this->assertFalse($settings['flutterwave_live']);
        $this->assertArrayNotHasKey('_token', $settings);
        $this->assertArrayNotHasKey('status', $settings);
        $this->assertArrayNotHasKey('user_role', $settings);
        $this->assertArrayNotHasKey('payment_settings', $settings);
        $this->assertArrayNotHasKey('unexpected_secret', $settings);
        $this->assertSame(UserAccountStatus::Active->value, (int) $user->status);
        $this->assertSame(UserRole::General->value, $user->user_role);
    }
}
