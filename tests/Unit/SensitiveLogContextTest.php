<?php

namespace Tests\Unit;

use App\Logging\SanitizeLogContext;
use App\Support\Logging\SensitiveLogContext;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Tests\TestCase;

class SensitiveLogContextTest extends TestCase
{
    public function test_it_redacts_sensitive_context_while_preserving_diagnostics(): void
    {
        $sanitized = SensitiveLogContext::sanitize([
            'user_id' => 123,
            'email' => 'private@example.com',
            'password' => 'plain-password',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer live-token-value',
                'Cookie' => 'sociopro_session=secret-session-cookie',
            ],
            'payload' => [
                'name' => 'Private User',
                'card_number' => '4111111111111111',
                'notes' => 'Keep the shape, not the values.',
            ],
            'file_contents' => 'raw uploaded document bytes',
        ]);

        $this->assertSame(123, $sanitized['user_id']);
        $this->assertSame('[personal-data-redacted]', $sanitized['email']);
        $this->assertSame('[redacted]', $sanitized['password']);
        $this->assertSame('application/json', $sanitized['headers']['Accept']);
        $this->assertSame('[redacted]', $sanitized['headers']['Authorization']);
        $this->assertSame('[redacted]', $sanitized['headers']['Cookie']);
        $this->assertSame([
            'redacted' => true,
            'type' => 'array',
            'keys' => ['name', 'card_number', 'notes'],
        ], $sanitized['payload']);
        $this->assertSame('[file-content-redacted]', $sanitized['file_contents']);
    }

    public function test_it_masks_sensitive_values_embedded_in_log_messages(): void
    {
        $message = SensitiveLogContext::sanitizeMessage(
            'Authorization: Bearer live-token password=plain email private@example.com card 4111 1111 1111 1111 Cookie: sociopro_session=secret'
        );

        $this->assertStringNotContainsString('live-token', $message);
        $this->assertStringNotContainsString('plain', $message);
        $this->assertStringNotContainsString('private@example.com', $message);
        $this->assertStringNotContainsString('4111 1111 1111 1111', $message);
        $this->assertStringNotContainsString('sociopro_session=secret', $message);
        $this->assertStringContainsString('Bearer [redacted]', $message);
    }

    public function test_log_tap_sanitizes_monolog_records(): void
    {
        $logger = new Logger('test');
        $handler = new TestHandler(Level::Debug);
        $logger->pushHandler($handler);

        (new SanitizeLogContext)($logger);

        $logger->warning('Payment callback failed for private@example.com with token=secret-token', [
            'authorization' => 'Bearer secret-token',
            'payment_data' => [
                'card_number' => '4111111111111111',
            ],
        ]);

        $records = $handler->getRecords();

        $this->assertCount(1, $records);
        $this->assertStringNotContainsString('private@example.com', $records[0]->message);
        $this->assertStringNotContainsString('secret-token', $records[0]->message);
        $this->assertSame('[redacted]', $records[0]->context['authorization']);
        $this->assertSame([
            'redacted' => true,
            'type' => 'array',
            'keys' => ['card_number'],
        ], $records[0]->context['payment_data']);
    }
}
