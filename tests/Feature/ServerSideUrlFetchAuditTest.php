<?php

namespace Tests\Feature;

use App\Support\Security\ServerSideUrl;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ServerSideUrlFetchAuditTest extends TestCase
{
    public function test_configured_url_fetch_uses_allowlist_and_bounds_network_behavior(): void
    {
        Config::set('security.server_side_url.allowed_hosts', ['93.184.216.34']);
        Config::set('security.server_side_url.timeout_seconds', 2);
        Config::set('security.server_side_url.max_redirects', 0);
        Config::set('security.server_side_url.max_response_bytes', 2048);
        Config::set('security.server_side_url.user_agent', 'SocioproUrlAudit/1.0');

        $this->assertSame(
            'https://93.184.216.34/path',
            ServerSideUrl::forConfiguredHttpFetch('https://93.184.216.34/path')
        );

        Config::set('security.server_side_url.allowed_hosts', ['203.0.113.10']);

        $this->assertNull(ServerSideUrl::forConfiguredHttpFetch('https://93.184.216.34/path'));
        $this->assertNull(ServerSideUrl::forConfiguredHttpFetch('https://169.254.169.254/latest/meta-data'));

        $options = ServerSideUrl::configuredStreamContextOptions();

        $this->assertSame(2, $options['https']['timeout']);
        $this->assertSame(0, $options['https']['follow_location']);
        $this->assertSame(0, $options['https']['max_redirects']);
        $this->assertSame('SocioproUrlAudit/1.0', $options['https']['user_agent']);
        $this->assertSame(2048, ServerSideUrl::configuredResponseByteLimit());
    }

    public function test_user_controlled_link_preview_fetches_stay_behind_server_side_url_guard(): void
    {
        $helper = file_get_contents(app_path('Helpers/CommonHelper.php')) ?: '';

        $this->assertStringContainsString('ServerSideUrl::forConfiguredHttpFetch', $helper);
        $this->assertStringNotContainsString('file_get_contents($pageUrl', $helper);
        $this->assertStringNotContainsString('file_get_contents((string) $pageUrl', $helper);
    }

    public function test_fixed_provider_http_clients_use_fixed_hosts_and_timeouts(): void
    {
        $zoom = file_get_contents(app_path('Services/Zoom/ZoomMeetingClient.php')) ?: '';
        $paypal = file_get_contents(app_path('Services/Payments/Gateways/Paypal.php')) ?: '';
        $paystack = file_get_contents(app_path('Services/Payments/Gateways/Paystack.php')) ?: '';

        $this->assertStringContainsString("private const API_BASE_URL = 'https://api.zoom.us/v2/';", $zoom);
        $this->assertStringContainsString('->timeout(10)', $zoom);
        $this->assertStringContainsString('https://api.sandbox.paypal.com/v1/', $paypal);
        $this->assertStringContainsString('https://api.paypal.com/v1/', $paypal);
        $this->assertStringContainsString('->timeout(10)', $paypal);
        $this->assertStringContainsString('https://api.paystack.co/transaction/verify/', $paystack);
        $this->assertStringContainsString('->timeout(10)', $paystack);
    }
}
