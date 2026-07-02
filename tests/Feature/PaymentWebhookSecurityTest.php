<?php

namespace Tests\Feature;

use App\Enums\PaymentGatewayIdentifier;
use App\Models\PaymentGateway;
use App\Services\Payments\PaymentGatewayResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PaymentWebhookSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'security.webhooks.paystack.require_timestamp' => false,
            'security.webhooks.paystack.timestamp_tolerance_seconds' => 300,
            'security.webhooks.paystack.replay_ttl_seconds' => 3600,
        ]);
    }

    public function test_paystack_webhook_route_has_signature_and_rate_limit_middleware(): void
    {
        $route = Route::getRoutes()->getByName('make.payment');

        $this->assertNotNull($route);
        $this->assertTrue(
            collect($route->gatherMiddleware())->contains(fn (string $middleware): bool => $middleware === 'throttle:webhook' || str_ends_with($middleware, ':webhook')),
            'The Paystack webhook route must keep the webhook rate limiter.'
        );
        $this->assertContains('payment.webhook:paystack', $route->gatherMiddleware());
    }

    public function test_paystack_webhook_rejects_invalid_signature_with_safe_log_context(): void
    {
        $this->configurePaystackGateway();
        $logger = Log::spy();

        $this->mock(PaymentGatewayResolver::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('paymentStatus');
        });

        $response = $this->callPaystackWebhook(['reference' => 'PSK-123'], [
            'HTTP_X_PAYSTACK_SIGNATURE' => 'invalid-signature',
        ]);

        $response->assertUnauthorized();
        $logger->shouldHaveReceived('warning')
            ->with('payment_webhook_rejected', Mockery::on(function (array $context): bool {
                return ($context['provider'] ?? null) === PaymentGatewayIdentifier::Paystack->value
                    && ($context['reason'] ?? null) === 'invalid_signature'
                    && ! array_key_exists('payload', $context)
                    && ! array_key_exists('signature', $context)
                    && ! array_key_exists('secret', $context);
            }))
            ->once();
    }

    public function test_paystack_webhook_accepts_valid_signature_and_passes_payload_to_controller(): void
    {
        $this->configurePaystackGateway();
        $payload = ['reference' => 'PSK-123'];
        $headers = $this->signedPaystackHeaders($payload);

        $this->withSession(['payment_details' => $this->paymentDetails()])
            ->mock(PaymentGatewayResolver::class, function (MockInterface $mock): void {
                $mock->shouldReceive('paymentStatus')
                    ->once()
                    ->with(
                        Mockery::type(PaymentGateway::class),
                        PaymentGatewayIdentifier::Paystack->value,
                        Mockery::on(fn (array $payload): bool => ($payload['reference'] ?? null) === 'PSK-123')
                    )
                    ->andReturnFalse();
            });

        $this->callPaystackWebhook($payload, $headers)
            ->assertRedirect('/payment-cancel');
    }

    public function test_paystack_webhook_rejects_stale_timestamp_when_required(): void
    {
        $this->configurePaystackGateway();
        config(['security.webhooks.paystack.require_timestamp' => true]);

        $payload = ['reference' => 'PSK-123'];
        $headers = $this->signedPaystackHeaders($payload, [
            'HTTP_X_SOCIOPRO_TIMESTAMP' => (string) now()->subMinutes(10)->timestamp,
        ]);

        $this->mock(PaymentGatewayResolver::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('paymentStatus');
        });

        $this->callPaystackWebhook($payload, $headers)
            ->assertBadRequest();
    }

    public function test_paystack_webhook_replay_returns_ok_without_reprocessing(): void
    {
        $this->configurePaystackGateway();
        $payload = ['reference' => 'PSK-123'];
        $headers = $this->signedPaystackHeaders($payload);

        $this->withSession(['payment_details' => $this->paymentDetails()])
            ->mock(PaymentGatewayResolver::class, function (MockInterface $mock): void {
                $mock->shouldReceive('paymentStatus')->once()->andReturnFalse();
            });

        $this->callPaystackWebhook($payload, $headers)
            ->assertRedirect('/payment-cancel');

        $this->callPaystackWebhook($payload, $headers)
            ->assertOk()
            ->assertSee('Duplicate webhook accepted.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    private function callPaystackWebhook(array $payload, array $headers)
    {
        return $this->call(
            'POST',
            route('make.payment', ['identifier' => PaymentGatewayIdentifier::Paystack->value]),
            [],
            [],
            [],
            array_merge([
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
            ], $headers),
            $this->payloadBody($payload)
        );
    }

    private function configurePaystackGateway(): void
    {
        PaymentGateway::where('identifier', PaymentGatewayIdentifier::Paystack->value)->update([
            'keys' => json_encode([
                'secret_test_key' => 'test-secret-key',
                'public_test_key' => 'test-public-key',
                'secret_live_key' => 'live-secret-key',
                'public_live_key' => 'live-public-key',
            ]),
            'test_mode' => 1,
            'status' => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentDetails(): array
    {
        return [
            'cancel_url' => '/payment-cancel',
            'success_method' => [
                'model_name' => 'Sponsor',
                'function_name' => 'add_payment_success',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $extraHeaders
     * @return array<string, string>
     */
    private function signedPaystackHeaders(array $payload, array $extraHeaders = []): array
    {
        return array_merge([
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $this->payloadBody($payload), 'test-secret-key'),
        ], $extraHeaders);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadBody(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
