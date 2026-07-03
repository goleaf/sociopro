<?php

namespace Tests\Feature;

use Anand\LaravelPaytmWallet\Facades\PaytmWallet;
use App\Enums\PaymentGatewayIdentifier;
use App\Enums\PaytmTransactionStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\PaymentController;
use App\Models\PaymentGateway;
use App\Models\PaymentHistoryEntry;
use App\Models\Setting;
use App\Models\User;
use App\Services\Payments\PaymentGatewayResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Mockery;
use Mockery\MockInterface;
use ReflectionClass;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_METHODS = [
        '__construct',
        'index',
        'show_payment_gateway_by_ajax',
        'payment_success',
        'payment_create',
        'payment_razorpay',
        'payment_paytm',
        'paytm_paymentCallback',
    ];

    private const PRIVATE_METHODS = [
        'paymentGateway',
        'paymentPageSettings',
        'paymentGatewayViewData',
        'stripeViewData',
        'razorpayViewData',
        'flutterwaveViewData',
        'paypalViewData',
        'paystackViewData',
        'userPaymentSettings',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string}>
     */
    private const ROUTES = [
        'payment' => ['index', ['GET', 'HEAD'], 'payment'],
        'payment.show_payment_gateway_by_ajax' => ['show_payment_gateway_by_ajax', ['GET', 'HEAD'], 'payment/show_payment_gateway_by_ajax/{identifier}'],
        'payment.success' => ['payment_success', ['GET', 'HEAD'], 'payment/success/{identifier}'],
        'payment.create' => ['payment_create', ['GET', 'HEAD'], 'payment/create/{identifier}'],
        'razorpay.order' => ['payment_razorpay', ['POST'], 'payment/{identifier}/order'],
        'make.order' => ['payment_paytm', ['POST'], 'payment/make/order/{identifier}'],
        'payment.status' => ['paytm_paymentCallback', ['GET', 'HEAD'], 'payment/make/{identifier}/status'],
        'make.payment' => ['payment_success', ['POST'], 'paystack/payment/{identifier}'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        PaymentGateway::query()->delete();
        $this->upsertSetting('system_name', 'Payment Test App');
        $this->upsertSetting('system_fav_icon', 'payment-test-favicon.png');
        $this->upsertSetting('system_currency', 'USD');
    }

    public function test_requested_payment_controller_methods_keep_expected_visibility(): void
    {
        $controller = new ReflectionClass(PaymentController::class);

        foreach (self::PUBLIC_METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "PaymentController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "PaymentController::{$method} should stay public.");
        }

        foreach (self::PRIVATE_METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "PaymentController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPrivate(), "PaymentController::{$method} should stay private.");
        }
    }

    public function test_requested_payment_routes_keep_expected_actions_methods_uris_and_middleware(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(PaymentController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");
        }

        $this->assertContains('throttle:webhook', Route::getRoutes()->getByName('payment.status')->gatherMiddleware());
        $this->assertContains('payment.webhook:paystack', Route::getRoutes()->getByName('make.payment')->gatherMiddleware());
    }

    public function test_index_requires_payment_details_and_rejects_zero_payable_amounts(): void
    {
        $this
            ->from('/checkout')
            ->get(route('payment'))
            ->assertRedirect('/checkout');

        $this
            ->withSession(['payment_details' => $this->paymentDetails(payableAmount: '0.00')])
            ->get(route('payment'))
            ->assertRedirect('/payment-cancel');
    }

    public function test_index_renders_payment_gateways_and_page_settings(): void
    {
        PaymentGateway::factory()->stripe()->create(['title' => 'Stripe Test']);
        PaymentGateway::factory()->paystack()->create(['title' => 'Paystack Test']);

        $response = $this
            ->withSession(['payment_details' => $this->paymentDetails()])
            ->get(route('payment'));

        $response
            ->assertOk()
            ->assertViewIs('payment.index')
            ->assertViewHas('system_name', 'Payment Test App')
            ->assertViewHas('system_favicon', 'payment-test-favicon.png')
            ->assertSee('Stripe Test')
            ->assertSee('Paystack Test')
            ->assertSee('Payment feature item');
    }

    public function test_gateway_partials_build_view_data_for_admin_gateway_models(): void
    {
        $user = $this->activeUser(['phone' => '15550001111']);
        $details = $this->paymentDetails(model: 'Sponsor', userId: $user->id);

        PaymentGateway::factory()->stripe()->create([
            'keys' => [
                'secret_key' => 'stripe-secret-test',
                'secret_live_key' => 'stripe-secret-live',
            ],
        ]);
        PaymentGateway::factory()->razorpay()->create([
            'keys' => [
                'public_key' => 'razorpay-public',
                'secret_key' => 'razorpay-secret',
            ],
        ]);
        PaymentGateway::factory()->flutterwave()->create([
            'keys' => [
                'public_key' => 'flutterwave-public',
                'secret_key' => 'flutterwave-secret',
            ],
        ]);
        PaymentGateway::factory()->paypal()->create([
            'keys' => [
                'sandbox_client_id' => 'paypal-sandbox-client',
                'production_client_id' => 'paypal-production-client',
            ],
        ]);
        PaymentGateway::factory()->paystack()->create([
            'keys' => [
                'public_test_key' => 'paystack-public-test',
                'public_live_key' => 'paystack-public-live',
            ],
        ]);
        PaymentGateway::factory()->paytm()->create();

        $this->actingAs($user);

        $this->gatewayPartial(PaymentGatewayIdentifier::Stripe, $details)
            ->assertViewIs('payment.stripe.index')
            ->assertViewHas('key', 'stripe-secret-test')
            ->assertViewHas('msg', '');

        $this->gatewayPartial(PaymentGatewayIdentifier::Razorpay, $details)
            ->assertViewIs('payment.razorpay.index')
            ->assertViewHas('public_key', 'razorpay-public')
            ->assertViewHas('secret_key', 'razorpay-secret')
            ->assertViewHas('msg', '');

        $this->gatewayPartial(PaymentGatewayIdentifier::Flutterwave, $details)
            ->assertViewIs('payment.flutterwave.index')
            ->assertViewHas('key', 'flutterwave-public')
            ->assertViewHas('key_type', 'public_key')
            ->assertViewHas('title', 'Ads payment.')
            ->assertViewHas('description', 'Payment for ads publish.')
            ->assertViewHas('user', fn (object $viewUser): bool => (int) $viewUser->id === $user->id);

        $this->gatewayPartial(PaymentGatewayIdentifier::Paypal, $details)
            ->assertViewIs('payment.paypal.index')
            ->assertViewHas('client_id', 'paypal-sandbox-client')
            ->assertViewHas('paypalURL', 'https://api.sandbox.paypal.com/v1/')
            ->assertViewHas('systemCurrency', 'USD');

        $this->gatewayPartial(PaymentGatewayIdentifier::Paystack, $details)
            ->assertViewIs('payment.paystack.index')
            ->assertViewHas('key', 'paystack-public-test')
            ->assertViewHas('amount', '19.50')
            ->assertViewHas('amount_minor', 1950)
            ->assertViewHas('user_details', fn (User $viewUser): bool => $viewUser->id === $user->id);

        $this->gatewayPartial(PaymentGatewayIdentifier::Paytm, $details)
            ->assertViewIs('payment.paytm.index')
            ->assertSee("This payment gateway isn't configured.", false);
    }

    public function test_gateway_partials_use_user_payment_settings_for_payout_models(): void
    {
        $user = $this->activeUser([
            'payment_settings' => json_encode([
                'stripe_live' => true,
                'stripe_secret_key' => 'stripe-user-test',
                'stripe_secret_live_key' => 'stripe-user-live',
                'raz_key_id' => 'razorpay-user-public',
                'raz_secret_key' => 'razorpay-user-secret',
                'flutterwave_live' => true,
                'flutterwave_public_key' => 'flutterwave-user-public',
                'flutterwave_secret_key' => 'flutterwave-user-secret',
            ]),
        ]);
        $details = $this->paymentDetails(model: 'AuthorPayout', userId: $user->id);

        PaymentGateway::factory()->stripe()->create(['status' => 0, 'keys' => []]);
        PaymentGateway::factory()->razorpay()->create(['status' => 0, 'keys' => []]);
        PaymentGateway::factory()->flutterwave()->create(['status' => 0, 'keys' => []]);

        $this->actingAs($user);

        $this->gatewayPartial(PaymentGatewayIdentifier::Stripe, $details)
            ->assertViewHas('key', 'stripe-user-live')
            ->assertViewHas('msg', '');

        $this->gatewayPartial(PaymentGatewayIdentifier::Razorpay, $details)
            ->assertViewHas('public_key', 'razorpay-user-public')
            ->assertViewHas('secret_key', 'razorpay-user-secret')
            ->assertViewHas('msg', '');

        $this->gatewayPartial(PaymentGatewayIdentifier::Flutterwave, $details)
            ->assertViewHas('key', 'flutterwave-user-secret')
            ->assertViewHas('key_type', 'secret_key')
            ->assertViewHas('title', 'Authors payout.')
            ->assertViewHas('description', 'Authors payout.')
            ->assertViewHas('msg', '');
    }

    public function test_payment_create_and_razorpay_order_delegate_to_gateway_resolver(): void
    {
        $stripe = PaymentGateway::factory()->stripe()->create();
        $razorpay = PaymentGateway::factory()->razorpay()->create();

        $this->mock(PaymentGatewayResolver::class, function (MockInterface $mock) use ($razorpay, $stripe): void {
            $mock->shouldReceive('createPayment')
                ->once()
                ->with(Mockery::on(fn (PaymentGateway $gateway): bool => $gateway->id === $stripe->id), PaymentGatewayIdentifier::Stripe->value)
                ->andReturn('https://payments.test/stripe-session');

            $mock->shouldReceive('createPayment')
                ->once()
                ->with(Mockery::on(fn (PaymentGateway $gateway): bool => $gateway->id === $razorpay->id), PaymentGatewayIdentifier::Razorpay->value)
                ->andReturn([
                    'page_data' => [
                        'razorpay_id' => 'rzp_test_id',
                        'amount' => 1950,
                        'name' => 'Payment User',
                        'description' => 'Payment feature item',
                        'order_id' => 'order_test_123',
                        'email' => 'payment-user@example.test',
                        'phone' => '15550002222',
                    ],
                    'payment_details' => $this->paymentDetails(),
                    'color' => '#3399cc',
                ]);
        });

        $this
            ->get(route('payment.create', ['identifier' => PaymentGatewayIdentifier::Stripe->value]))
            ->assertRedirect('https://payments.test/stripe-session');

        $this
            ->post(route('razorpay.order', ['identifier' => PaymentGatewayIdentifier::Razorpay->value]))
            ->assertOk()
            ->assertViewIs('payment.razorpay.payment')
            ->assertViewHas('color', '#3399cc')
            ->assertViewHas('page_data', fn (array $pageData): bool => $pageData['order_id'] === 'order_test_123');
    }

    public function test_payment_success_redirects_to_cancel_url_when_gateway_status_fails(): void
    {
        $gateway = PaymentGateway::factory()->paypal()->create();

        $this->mock(PaymentGatewayResolver::class, function (MockInterface $mock) use ($gateway): void {
            $mock->shouldReceive('paymentStatus')
                ->once()
                ->with(
                    Mockery::on(fn (PaymentGateway $resolvedGateway): bool => $resolvedGateway->id === $gateway->id),
                    PaymentGatewayIdentifier::Paypal->value,
                    Mockery::on(fn (array $payload): bool => ($payload['payment_id'] ?? null) === 'PAY-123')
                )
                ->andReturnFalse();
        });

        $this
            ->withSession(['payment_details' => $this->paymentDetails()])
            ->get(route('payment.success', [
                'identifier' => PaymentGatewayIdentifier::Paypal->value,
                'payment_id' => 'PAY-123',
                '_token' => 'ignored-token',
            ]))
            ->assertRedirect('/payment-cancel');
    }

    public function test_payment_success_delegates_to_configured_success_model_when_gateway_status_passes(): void
    {
        $user = $this->activeUser();
        $gateway = PaymentGateway::factory()->paystack()->create();

        $this->mock(PaymentGatewayResolver::class, function (MockInterface $mock) use ($gateway): void {
            $mock->shouldReceive('paymentStatus')
                ->once()
                ->with(
                    Mockery::on(fn (PaymentGateway $resolvedGateway): bool => $resolvedGateway->id === $gateway->id),
                    PaymentGatewayIdentifier::Paystack->value,
                    Mockery::on(fn (array $payload): bool => ($payload['reference'] ?? null) === 'PSK-123')
                )
                ->andReturnTrue();
        });

        $this
            ->actingAs($user)
            ->withSession(['payment_details' => $this->paymentDetails(model: 'Badge', userId: $user->id)])
            ->get(route('payment.success', [
                'identifier' => PaymentGatewayIdentifier::Paystack->value,
                'reference' => 'PSK-123',
            ]))
            ->assertRedirect(route('badge'));

        $this->assertDatabaseHas('batchs', [
            'user_id' => $user->id,
            'title' => 'Payment feature item',
            'status' => 1,
        ]);
        $this->assertDatabaseHas('payment_histories', [
            'user_id' => $user->id,
            'identifier' => PaymentGatewayIdentifier::Paystack->value,
            'amount' => 19.50,
        ]);
    }

    public function test_payment_paytm_prepares_receive_order_without_real_provider_call(): void
    {
        $user = $this->activeUser(['phone' => '15550003333']);
        PaymentGateway::factory()->paytm()->create();

        $transaction = Mockery::mock();
        $transaction->shouldReceive('prepare')
            ->once()
            ->with(Mockery::on(fn (array $payload): bool => $payload['user'] === $user->id
                && $payload['mobile_number'] === '15550003333'
                && $payload['email'] === $user->email
                && $payload['amount'] === '7.25'
                && $payload['callback_url'] === route('payment.status', ['identifier' => PaymentGatewayIdentifier::Paytm->value])))
            ->andReturnSelf();
        $transaction->shouldReceive('receive')
            ->once()
            ->andReturn(response('paytm-receive-response'));

        PaytmWallet::shouldReceive('with')
            ->once()
            ->with('receive')
            ->andReturn($transaction);

        $this
            ->actingAs($user)
            ->post(route('make.order', ['identifier' => PaymentGatewayIdentifier::Paytm->value]), [
                'user' => $user->id,
                'amount' => '7.25',
            ])
            ->assertOk()
            ->assertSee('paytm-receive-response');
    }

    public function test_paytm_callback_updates_matching_payment_history_and_redirects_to_payment_page(): void
    {
        $history = PaymentHistoryEntry::factory()->create([
            'order_id' => 'ORDER-123',
            'status' => PaytmTransactionStatus::Open->value,
            'transaction_id' => null,
        ]);

        $transaction = Mockery::mock();
        $transaction->shouldReceive('response')->once()->andReturn(['STATUS' => 'TXN_SUCCESS']);
        $transaction->shouldReceive('getOrderId')->once()->andReturn('ORDER-123');
        $transaction->shouldReceive('getTransactionId')->twice()->andReturn('TXN-123');
        $transaction->shouldReceive('isSuccessful')->once()->andReturnTrue();

        PaytmWallet::shouldReceive('with')
            ->once()
            ->with('receive')
            ->andReturn($transaction);

        $this
            ->get(route('payment.status', ['identifier' => PaymentGatewayIdentifier::Paytm->value]))
            ->assertRedirect(route('payment'))
            ->assertSessionHas('message', 'Your payment is successfull.');

        $history->refresh();

        $this->assertSame(PaytmTransactionStatus::Successful, $history->status);
        $this->assertSame('TXN-123', $history->getAttribute('transaction_id'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'phone' => '15550000000',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ], $attributes));
    }

    private function gatewayPartial(PaymentGatewayIdentifier $identifier, array $paymentDetails)
    {
        return $this
            ->withSession(['payment_details' => $paymentDetails])
            ->get(route('payment.show_payment_gateway_by_ajax', ['identifier' => $identifier->value]))
            ->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentDetails(string $model = 'Sponsor', ?int $userId = null, string $payableAmount = '19.50'): array
    {
        return [
            'payable_amount' => $payableAmount,
            'success_url' => '/payment-success',
            'cancel_url' => '/payment-cancel',
            'items' => [
                [
                    'id' => 101,
                    'title' => 'Payment feature item',
                    'subtitle' => 'Payment feature subtitle',
                    'price' => $payableAmount,
                    'discount_percentage' => 0,
                    'discount_price' => $payableAmount,
                ],
            ],
            'tax' => 0,
            'custom_field' => [
                'user_id' => $userId,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
            ],
            'success_method' => [
                'model_name' => $model,
                'function_name' => 'add_payment_success',
            ],
        ];
    }

    private function upsertSetting(string $type, string $description): void
    {
        $updated = Setting::query()
            ->where('type', $type)
            ->update([
                'description' => $description,
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            return;
        }

        $setting = new Setting;
        $setting->forceFill([
            'type' => $type,
            'description' => $description,
            'updated_at' => now(),
        ]);
        $setting->created_at = now();

        $setting->save();
    }
}
