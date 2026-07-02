<?php

namespace Tests\Unit;

use App\Support\Security\ServerSideUrl;
use PHPUnit\Framework\TestCase;

class ServerSideUrlTest extends TestCase
{
    public function test_it_allows_public_http_and_https_ip_urls(): void
    {
        $this->assertSame('https://93.184.216.34/path', ServerSideUrl::forHttpFetch('https://93.184.216.34/path'));
        $this->assertSame('http://93.184.216.34/path', ServerSideUrl::forHttpFetch('http://93.184.216.34/path'));
    }

    public function test_it_enforces_explicit_host_allowlists(): void
    {
        $this->assertSame(
            'https://93.184.216.34/path',
            ServerSideUrl::forHttpFetch('https://93.184.216.34/path', ['93.184.216.34'])
        );

        $this->assertNull(ServerSideUrl::forHttpFetch('https://93.184.216.34/path', ['203.0.113.10']));
        $this->assertNull(ServerSideUrl::forHttpFetch('https://93.184.216.34.evil.example/path', ['93.184.216.34']));
    }

    public function test_it_blocks_non_http_schemes(): void
    {
        $this->assertNull(ServerSideUrl::forHttpFetch('file:///etc/passwd'));
        $this->assertNull(ServerSideUrl::forHttpFetch('ftp://93.184.216.34/file'));
        $this->assertNull(ServerSideUrl::forHttpFetch('https://user:pass@93.184.216.34/path'));
    }

    public function test_it_blocks_private_reserved_and_local_hosts(): void
    {
        $blocked = [
            'http://localhost',
            'http://localhost.',
            'http://app.localhost',
            'http://127.0.0.1',
            'http://10.0.0.1',
            'http://172.16.0.1',
            'http://192.168.1.1',
            'http://169.254.169.254',
            'http://0.0.0.0',
            'http://[::1]',
            'http://[::ffff:127.0.0.1]',
            'http://127.0.0.1@93.184.216.34',
        ];

        foreach ($blocked as $url) {
            $this->assertNull(ServerSideUrl::forHttpFetch($url), "{$url} should be blocked.");
        }
    }

    public function test_it_builds_timeout_bounded_non_redirecting_stream_options(): void
    {
        $options = ServerSideUrl::streamContextOptions(
            timeoutSeconds: 3,
            maxRedirects: 0,
            userAgent: 'SocioproUrlAudit/1.0'
        );

        foreach (['http', 'https'] as $scheme) {
            $this->assertSame(0, $options[$scheme]['follow_location']);
            $this->assertSame(0, $options[$scheme]['max_redirects']);
            $this->assertSame(3, $options[$scheme]['timeout']);
            $this->assertSame('SocioproUrlAudit/1.0', $options[$scheme]['user_agent']);
            $this->assertStringContainsString('Accept: text/html', $options[$scheme]['header']);
        }
    }
}
