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
}
