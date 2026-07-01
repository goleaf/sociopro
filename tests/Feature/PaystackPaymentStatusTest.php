<?php

namespace Tests\Feature;

use App\Models\Payment_gateway;
use App\Models\payment_gateway\Paystack;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackPaymentStatusTest extends TestCase
{
    public function test_paystack_payment_status_does_not_use_curl(): void
    {
        $contents = file_get_contents(app_path('Models/payment_gateway/Paystack.php'));

        $this->assertStringNotContainsString('curl_init', $contents);
        $this->assertStringNotContainsString('CURLOPT_SSL_VERIFYPEER', $contents);
    }

    public function test_it_confirms_successful_paystack_payment_with_http_client(): void
    {
        $this->configurePaystackGateway();

        Http::fake([
            'api.paystack.co/transaction/verify/PSK-123' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                ],
            ]),
        ]);

        $this->assertTrue($this->makeGateway()->payment_status('paystack', ['reference' => 'PSK-123']));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === 'https://api.paystack.co/transaction/verify/PSK-123'
            && $request->hasHeader('Authorization', 'Bearer test-secret-key')
            && $request->hasHeader('Accept', 'application/json'));
    }

    public function test_it_rejects_failed_paystack_payment(): void
    {
        $this->configurePaystackGateway();

        Http::fake([
            'api.paystack.co/transaction/verify/PSK-123' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'failed',
                ],
            ]),
        ]);

        $this->assertFalse($this->makeGateway()->payment_status('paystack', ['reference' => 'PSK-123']));
    }

    public function test_it_does_not_call_paystack_without_reference(): void
    {
        $this->configurePaystackGateway();

        Http::fake();

        $this->assertFalse($this->makeGateway()->payment_status('paystack'));

        Http::assertNothingSent();
    }

    public function test_it_returns_false_when_paystack_request_fails(): void
    {
        $this->configurePaystackGateway();

        Http::fake(function () {
            throw new ConnectionException('Connection failed.');
        });

        $this->assertFalse($this->makeGateway()->payment_status('paystack', ['reference' => 'PSK-123']));
    }

    private function configurePaystackGateway(): void
    {
        Payment_gateway::where('identifier', 'paystack')->update([
            'keys' => json_encode([
                'secret_test_key' => 'test-secret-key',
                'public_test_key' => 'test-public-key',
                'secret_live_key' => 'live-secret-key',
                'public_live_key' => 'live-public-key',
            ]),
            'test_mode' => 1,
        ]);
    }

    private function makeGateway(): Paystack
    {
        return new Paystack();
    }
}
