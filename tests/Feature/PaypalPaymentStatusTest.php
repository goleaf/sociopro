<?php

namespace Tests\Feature;

use App\Enums\PaymentGatewayIdentifier;
use App\Exceptions\Payments\PaymentGatewayException;
use App\Models\Payment_gateway;
use App\Services\Payments\Gateways\Paypal;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaypalPaymentStatusTest extends TestCase
{
    public function test_paypal_payment_status_does_not_use_curl(): void
    {
        $contents = file_get_contents(app_path('Services/Payments/Gateways/Paypal.php'));

        $this->assertStringNotContainsString('curl_init', $contents);
        $this->assertStringNotContainsString('CURLOPT_SSL_VERIFYPEER', $contents);
    }

    public function test_it_confirms_approved_paypal_payment_with_http_client(): void
    {
        $this->configurePaypalGateway();

        Http::fake([
            'api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'paypal-access-token',
            ]),
            'api.sandbox.paypal.com/v1/payments/payment/PAY-123' => Http::response([
                'state' => 'approved',
            ]),
        ]);

        $this->assertTrue(Paypal::payment_status(PaymentGatewayIdentifier::Paypal->value, ['payment_id' => 'PAY-123']));

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'https://api.sandbox.paypal.com/v1/oauth2/token'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('sandbox-client:sandbox-secret'))
            && $request->data() === ['grant_type' => 'client_credentials']);
        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-123'
            && $request->hasHeader('Authorization', 'Bearer paypal-access-token')
            && $request->hasHeader('Accept', 'application/json'));
    }

    public function test_it_rejects_unapproved_paypal_payments(): void
    {
        $this->configurePaypalGateway();

        Http::fake([
            'api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'paypal-access-token',
            ]),
            'api.sandbox.paypal.com/v1/payments/payment/PAY-123' => Http::response([
                'state' => 'failed',
            ]),
        ]);

        $this->assertFalse(Paypal::payment_status(PaymentGatewayIdentifier::Paypal->value, ['payment_id' => 'PAY-123']));
    }

    public function test_it_reports_paypal_transport_failures_without_changing_public_result(): void
    {
        $this->configurePaypalGateway();
        Exceptions::fake();

        Http::fake(function () {
            throw new ConnectionException('Connection failed.');
        });

        $this->assertFalse(Paypal::payment_status(PaymentGatewayIdentifier::Paypal->value, ['payment_id' => 'PAY-123']));

        Exceptions::assertReported(fn (PaymentGatewayException $exception) => $exception->gateway() === PaymentGatewayIdentifier::Paypal->value
            && $exception->reason() === 'transport_failure'
            && $exception->getPrevious() instanceof ConnectionException);
    }

    public function test_it_reports_missing_paypal_credentials_without_calling_paypal(): void
    {
        Payment_gateway::where('identifier', PaymentGatewayIdentifier::Paypal->value)->update([
            'keys' => json_encode([]),
            'test_mode' => 1,
        ]);
        Exceptions::fake();
        Http::fake();

        $this->assertFalse(Paypal::payment_status(PaymentGatewayIdentifier::Paypal->value, ['payment_id' => 'PAY-123']));

        Exceptions::assertReported(fn (PaymentGatewayException $exception) => $exception->gateway() === PaymentGatewayIdentifier::Paypal->value
            && $exception->reason() === 'missing_credentials');
        Http::assertNothingSent();
    }

    private function configurePaypalGateway(): void
    {
        Payment_gateway::where('identifier', PaymentGatewayIdentifier::Paypal->value)->update([
            'keys' => json_encode([
                'sandbox_client_id' => 'sandbox-client',
                'sandbox_secret_key' => 'sandbox-secret',
                'production_client_id' => 'production-client',
                'production_secret_key' => 'production-secret',
            ]),
            'test_mode' => 1,
        ]);
    }
}
