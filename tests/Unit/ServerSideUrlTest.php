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
            'http://127.0.0.1',
            'http://10.0.0.1',
            'http://172.16.0.1',
            'http://192.168.1.1',
            'http://169.254.169.254',
            'http://0.0.0.0',
            'http://[::1]',
        ];

        foreach ($blocked as $url) {
            $this->assertNull(ServerSideUrl::forHttpFetch($url), "{$url} should be blocked.");
        }
    }
}
