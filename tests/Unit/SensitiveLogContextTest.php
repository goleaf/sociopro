<?php

namespace Tests\Unit;

use App\Logging\SanitizeLogContext;
use App\Support\Logging\SensitiveLogContext;
use Illuminate\Support\Collection;
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

    public function test_it_handles_required_sensitive_keys_collections_objects_and_safe_values(): void
    {
        $object = new SensitiveLogContextObjectFixture(42, 'do-not-log-this');

        $sanitized = SensitiveLogContext::sanitize([
            'model_id' => 321,
            'route_name' => 'profile.show',
            'status_code' => 403,
            'is_public' => false,
            'status' => 'active',
            'plainTextToken' => 'plain-text-token',
            'X-Api-Key' => 'api-key-value',
            'set-cookie' => 'session=secret',
            'new_password' => 'new-password',
            'card_number' => '4111111111111111',
            'cvv' => '123',
            'nested' => [
                'client_secret' => 'client-secret',
                'refresh_token' => 'refresh-token',
            ],
            'collection' => new Collection([
                'authorization' => 'Bearer token',
                'safe_status' => 'queued',
            ]),
            'object' => $object,
            'nothing' => null,
        ]);

        $this->assertSame(321, $sanitized['model_id']);
        $this->assertSame('profile.show', $sanitized['route_name']);
        $this->assertSame(403, $sanitized['status_code']);
        $this->assertFalse($sanitized['is_public']);
        $this->assertSame('active', $sanitized['status']);
        $this->assertNull($sanitized['nothing']);
        $this->assertSame('[redacted]', $sanitized['plainTextToken']);
        $this->assertSame('[redacted]', $sanitized['X-Api-Key']);
        $this->assertSame('[redacted]', $sanitized['set-cookie']);
        $this->assertSame('[redacted]', $sanitized['new_password']);
        $this->assertSame('[redacted]', $sanitized['card_number']);
        $this->assertSame('[redacted]', $sanitized['cvv']);
        $this->assertSame('[redacted]', $sanitized['nested']['client_secret']);
        $this->assertSame('[redacted]', $sanitized['nested']['refresh_token']);
        $this->assertSame('[redacted]', $sanitized['collection']['authorization']);
        $this->assertSame('queued', $sanitized['collection']['safe_status']);
        $this->assertSame([
            'class' => SensitiveLogContextObjectFixture::class,
            'id' => 42,
        ], $sanitized['object']);
    }
}

class SensitiveLogContextObjectFixture
{
    public function __construct(
        public int $id,
        public string $secret,
    ) {}
}
