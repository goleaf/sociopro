<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecretLeakAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_image_views_do_not_render_hugging_face_tokens(): void
    {
        $token = 'hf_'.str_repeat('a', 32);
        $user = $this->activeUser();

        Setting::query()->insert([
            'type' => 'hugging_face_auth_key',
            'description' => $token,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('ai_image.image_generator'));

        $response
            ->assertOk()
            ->assertDontSee($token, false)
            ->assertDontSee('api-inference.huggingface.co', false)
            ->assertDontSee('Authorization', false);

        $modal = view('frontend.main_content.create_post_modal', [
            'user_info' => $user,
        ])->render();

        $this->assertStringNotContainsString($token, $modal);
        $this->assertStringNotContainsString('api-inference.huggingface.co', $modal);
        $this->assertStringNotContainsString('Authorization', $modal);
    }

    public function test_ai_image_generation_requires_server_side_token_configuration(): void
    {
        config(['services.huggingface.token' => null]);

        Setting::query()->insert([
            'type' => 'hugging_face_auth_key',
            'description' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($this->activeUser())
            ->postJson(route('ai_image.generate'), [
                'prompt' => 'a small safe test prompt',
            ]);

        $response
            ->assertStatus(503)
            ->assertJson([
                'error' => 'Image generation is not configured',
            ]);
    }

    public function test_zoom_live_streaming_view_uses_server_signature_without_api_secret(): void
    {
        $user = $this->activeUser();
        $apiSecret = 'zoom-secret-value-that-must-stay-server-side';
        $signature = 'server-generated-zoom-signature';

        Setting::query()->insert([
            'type' => 'zoom_configuration',
            'description' => json_encode([
                'api_key' => 'zoom-api-key',
                'api_secret' => $apiSecret,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        $html = view('frontend.live_streaming.index', [
            'zoom_api_key' => 'zoom-api-key',
            'zoom_signature' => $signature,
            'meeting_details' => [
                'id' => '123456789',
                'password' => 'meeting-password',
            ],
            'host' => 1,
            'isSupportAV' => 1,
            'disableJoinAudio' => 0,
            'post_details' => (object) ['post_id' => 123],
        ])->render();

        $this->assertStringContainsString($signature, $html);
        $this->assertStringNotContainsString($apiSecret, $html);
        $this->assertStringNotContainsString('apiSecret', $html);
        $this->assertStringNotContainsString('API_SECRET', $html);
    }

    public function test_production_code_does_not_contain_known_secret_literals(): void
    {
        $offenders = [];
        $patterns = [
            'hugging face token' => '/\bhf_[A-Za-z0-9]{20,}\b/',
            'private key block' => '/-----BEGIN (?:RSA |DSA |EC |OPENSSH |PGP )?PRIVATE KEY-----/',
            'certificate block' => '/-----BEGIN CERTIFICATE-----/',
            'old demo login email' => '/(?:admin|karenjrios)@example\.com/',
        ];

        foreach ($this->productionSecretScanFiles() as $path) {
            $contents = File::get($path);

            foreach ($patterns as $label => $pattern) {
                if (preg_match($pattern, $contents) === 1) {
                    $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).": {$label}";
                }
            }

            if (
                str_contains($contents, '12345678')
                && preg_match('/(?:admin|karenjrios)@example\.com/', $contents) === 1
            ) {
                $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).': old demo login credentials';
            }
        }

        $this->assertSame([], $offenders, 'Remove hardcoded secrets, demo credentials, and private key material from production-facing files.');
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
            'profile_status' => 'unlock',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function productionSecretScanFiles(): array
    {
        return collect([
            ...File::allFiles(app_path()),
            ...File::allFiles(base_path('routes')),
            ...File::allFiles(config_path()),
            ...File::allFiles(resource_path('views')),
            base_path('.env.example'),
        ])
            ->filter(fn ($file): bool => is_string($file) || in_array($file->getExtension(), ['php', 'blade.php', 'js', 'env', 'example'], true))
            ->map(fn ($file): string => is_string($file) ? $file : $file->getPathname())
            ->values()
            ->all();
    }
}
