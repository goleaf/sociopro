<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecretLeakAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_external_image_creator_routes_and_provider_hooks_stay_absent(): void
    {
        $removedRoutePrefix = 'ai'.'_image.';

        $this->assertFalse(Route::has($removedRoutePrefix.'image_generator'));
        $this->assertFalse(Route::has($removedRoutePrefix.'generate'));
        $this->assertFileDoesNotExist(resource_path('views/frontend/'.'ai'.'_image/image_generator.blade.php'));

        $forbiddenFragments = [
            'hug'.'ging_face_auth_key',
            'HUG'.'GING'.'FACE_',
            'services.'.'hug'.'gingface',
            'api'.'-inference.'.'hug'.'gingface.co',
            $removedRoutePrefix,
        ];

        foreach ($this->externalImageCreatorRemovalFiles() as $path) {
            $contents = File::get($path);

            foreach ($forbiddenFragments as $fragment) {
                $this->assertStringNotContainsString($fragment, $contents, $path);
            }
        }
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

    /**
     * @return list<string>
     */
    private function externalImageCreatorRemovalFiles(): array
    {
        return [
            app_path('Http/Controllers/MainController.php'),
            app_path('Http/Controllers/SettingController.php'),
            base_path('routes/web.php'),
            config_path('services.php'),
            resource_path('views/backend/admin/setting/system.blade.php'),
            resource_path('views/frontend/header.blade.php'),
            resource_path('views/frontend/main_content/create_post_modal.blade.php'),
            resource_path('views/frontend/right_sidebar.blade.php'),
            base_path('.env.example'),
            base_path('public/assets/install.sql'),
        ];
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
