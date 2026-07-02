<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_chat_message_view_escapes_user_content(): void
    {
        $blade = File::get(resource_path('views/frontend/chat/single-message.blade.php'));

        $this->assertStringNotContainsString(
            '{!! $message->message !!}',
            $blade,
            'Chat messages must not be rendered as raw HTML (stored XSS risk).'
        );

        $this->assertStringContainsString(
            'nl2br(e($message->message))',
            $blade,
            'Plain chat messages should be escaped with e() while preserving line breaks.'
        );
    }

    public function test_upload_directories_are_not_world_writable(): void
    {
        $folder = 'security_hardening_test_'.uniqid();
        $path = uploadTo($folder);

        try {
            $this->assertDirectoryExists($path);

            $worldWritable = (fileperms(rtrim($path, '/')) & 0o002) !== 0;
            $this->assertFalse(
                $worldWritable,
                'Upload directories must not be world-writable (expected 0755, not 0777).'
            );
        } finally {
            File::deleteDirectory(public_path('storage/'.$folder));
        }
    }

    public function test_cors_allowed_origins_are_configurable_via_environment(): void
    {
        // Default behavior is preserved as a public allowlist.
        $this->assertSame(['*'], config('cors.allowed_origins'));

        // The wiring exists so production can lock origins down without a code change.
        $config = File::get(config_path('cors.php'));
        $this->assertStringContainsString('CORS_ALLOWED_ORIGINS', $config);
    }

    public function test_session_cookies_use_a_safe_same_site_policy(): void
    {
        $this->assertContains(config('session.same_site'), ['lax', 'strict']);
    }

    public function test_session_cookie_configuration_is_environment_driven_and_secure_by_default(): void
    {
        $config = File::get(config_path('session.php'));

        $this->assertStringContainsString('SESSION_DRIVER', $config);
        $this->assertStringContainsString('SESSION_LIFETIME', $config);
        $this->assertStringContainsString('SESSION_EXPIRE_ON_CLOSE', $config);
        $this->assertStringContainsString('SESSION_ENCRYPT', $config);
        $this->assertStringContainsString('SESSION_DOMAIN', $config);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE', $config);
        $this->assertStringContainsString('SESSION_HTTP_ONLY', $config);
        $this->assertStringContainsString('SESSION_SAME_SITE', $config);
        $this->assertStringContainsString("env('APP_ENV') === 'production'", $config);

        $this->assertTrue(config('session.http_only'));
        $this->assertContains(config('session.same_site'), ['lax', 'strict']);
    }

    public function test_env_example_documents_session_cookie_security_controls(): void
    {
        $envExample = File::get(base_path('.env.example'));

        foreach ([
            'SESSION_DRIVER=',
            'SESSION_LIFETIME=',
            'SESSION_EXPIRE_ON_CLOSE=',
            'SESSION_ENCRYPT=',
            'SESSION_DOMAIN=',
            'SESSION_HTTP_ONLY=',
            'SESSION_SAME_SITE=',
            'SESSION_SECURE_COOKIE=',
        ] as $expectedKey) {
            $this->assertStringContainsString($expectedKey, $envExample);
        }
    }

    public function test_authentication_session_lifecycle_uses_laravel_security_primitives(): void
    {
        $controller = File::get(app_path('Http/Controllers/Auth/AuthenticatedSessionController.php'));
        $loginRequest = File::get(app_path('Http/Requests/Auth/LoginRequest.php'));
        $loginView = File::get(resource_path('views/auth/login.blade.php'));

        $this->assertStringContainsString('$request->session()->regenerate();', $controller);
        $this->assertStringContainsString("Auth::guard('web')->logout();", $controller);
        $this->assertStringContainsString('$request->session()->invalidate();', $controller);
        $this->assertStringContainsString('$request->session()->regenerateToken();', $controller);
        $this->assertStringContainsString('Auth::attempt(', $loginRequest);
        $this->assertStringContainsString('$this->boolean(\'remember\')', $loginRequest);
        $this->assertStringContainsString('name="remember"', $loginView);
    }

    public function test_session_cookie_production_runbook_documents_required_controls(): void
    {
        $runbook = File::get(base_path('docs/session-cookie-security.md'));

        foreach ([
            'SESSION_SECURE_COOKIE=true',
            'SESSION_HTTP_ONLY=true',
            'SESSION_SAME_SITE=lax',
            'SESSION_ENCRYPT=true',
            'SESSION_DRIVER=database',
            'session()->regenerate()',
            'session()->invalidate()',
            'regenerateToken()',
            'remember me',
            'TrustProxies',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $runbook);
        }
    }
}
