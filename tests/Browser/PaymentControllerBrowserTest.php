<?php

namespace Tests\Browser;

use App\Enums\PaymentGatewayIdentifier;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\PaymentGateway;
use App\Models\PaymentHistoryEntry;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PaymentControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAIL = 'dusk-payment-user@example.test';

    private const SETTING_TYPES = [
        'system_name',
        'system_fav_icon',
        'system_currency',
        'amazon_s3',
    ];

    /**
     * @var array<string, array<string, mixed>|null>
     */
    private array $originalGateways = [];

    /**
     * @var array<string, string|null>
     */
    private array $originalSettings = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->snapshotPaymentState();
        $this->deleteFixtures();
        $this->seedPaymentState();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();
        $this->restorePaymentState();

        parent::tearDown();
    }

    public function test_payment_controller_page_and_gateway_buttons_render_in_browser(): void
    {
        $user = $this->activeUser();
        $paymentDetails = $this->paymentDetails($user);

        $this->browse(function (Browser $browser) use ($paymentDetails, $user) {
            $browser->loginAs($user)
                ->visit('/');

            $this->putPaymentDetailsInBrowserSession($browser, $paymentDetails);

            $browser->visit('/payment')
                ->assertPathIs('/payment')
                ->assertSee('Order summary')
                ->assertSee('Payment feature item')
                ->assertSee('Dusk Stripe')
                ->assertSee('Dusk Paystack')
                ->assertSourceHas('paymentGatewayUrlTemplate');

            $this->assertFetchResponseContains($browser, '/payment/show_payment_gateway_by_ajax/stripe', 'duskStripeGateway', 'Pay by Stripe');
            $this->assertFetchResponseContains($browser, '/payment/show_payment_gateway_by_ajax/razorpay', 'duskRazorpayGateway', 'Pay by Razorpay');
            $this->assertFetchResponseContains($browser, '/payment/show_payment_gateway_by_ajax/flutterwave', 'duskFlutterwaveGateway', 'Pay by Flutterwave');
            $this->assertFetchResponseContains($browser, '/payment/show_payment_gateway_by_ajax/paypal', 'duskPaypalGateway', 'paypal-button-container');
            $this->assertFetchResponseContains($browser, '/payment/show_payment_gateway_by_ajax/paystack', 'duskPaystackGateway', 'Pay by Paystack');
            $this->assertFetchResponseContains($browser, '/payment/show_payment_gateway_by_ajax/paytm', 'duskPaytmGateway', "This payment gateway isn't configured.");
        });
    }

    /**
     * @param  array<string, mixed>  $paymentDetails
     */
    private function putPaymentDetailsInBrowserSession(Browser $browser, array $paymentDetails): void
    {
        $sessionId = $browser->cookie((string) config('session.cookie'));
        $this->assertIsString($sessionId);
        $this->assertNotSame('', $sessionId);

        $session = app('session')->driver();
        $session->setId($sessionId);
        $session->start();
        $session->put('payment_details', $paymentDetails);
        $session->save();
    }

    private function assertFetchResponseContains(Browser $browser, string $url, string $windowKey, string $expectedText): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;

            fetch({$encodedUrl}, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html',
                },
            }).then(async (response) => {
                window[{$encodedWindowKey}] = {
                    status: response.status,
                    text: await response.text(),
                };
            }).catch((error) => {
                window[{$encodedWindowKey}] = {
                    status: -1,
                    text: String(error),
                };
            });
        JS);

        $browser->waitUntil("window[{$encodedWindowKey}] !== null && window[{$encodedWindowKey}].status === 200", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('SQLSTATE[')", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('Internal Server Error')", 5)
            ->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'name' => 'Dusk Payment User',
            'email' => self::USER_EMAIL,
            'email_verified_at' => now(),
            'username' => 'dusk-payment-user',
            'phone' => '15550004444',
            'payment_settings' => json_encode([]),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentDetails(User $user): array
    {
        return [
            'payable_amount' => '19.50',
            'success_url' => '/payment-success',
            'cancel_url' => '/payment-cancel',
            'items' => [
                [
                    'id' => 101,
                    'title' => 'Payment feature item',
                    'subtitle' => 'Payment feature subtitle',
                    'price' => '19.50',
                    'discount_percentage' => 0,
                    'discount_price' => '19.50',
                ],
            ],
            'tax' => 0,
            'custom_field' => [
                'user_id' => $user->id,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
            ],
            'success_method' => [
                'model_name' => 'Sponsor',
                'function_name' => 'add_payment_success',
            ],
        ];
    }

    private function snapshotPaymentState(): void
    {
        foreach (PaymentGatewayIdentifier::cases() as $identifier) {
            $gateway = PaymentGateway::query()
                ->where('identifier', $identifier->value)
                ->first();

            $this->originalGateways[$identifier->value] = $gateway?->getRawOriginal();
        }

        foreach (self::SETTING_TYPES as $type) {
            $this->originalSettings[$type] = Setting::query()
                ->where('type', $type)
                ->value('description');
        }
    }

    private function seedPaymentState(): void
    {
        $this->upsertSetting('system_name', 'Dusk Payment App');
        $this->upsertSetting('system_fav_icon', 'dusk-payment-favicon.png');
        $this->upsertSetting('system_currency', 'USD');
        $this->upsertSetting('amazon_s3', json_encode(['active' => 0]));

        $this->upsertGateway(PaymentGatewayIdentifier::Stripe, [
            'title' => 'Dusk Stripe',
            'keys' => [
                'secret_key' => 'dusk-stripe-secret',
                'secret_live_key' => 'dusk-stripe-live-secret',
            ],
        ]);
        $this->upsertGateway(PaymentGatewayIdentifier::Razorpay, [
            'title' => 'Dusk Razorpay',
            'keys' => [
                'public_key' => 'dusk-razorpay-public',
                'secret_key' => 'dusk-razorpay-secret',
            ],
        ]);
        $this->upsertGateway(PaymentGatewayIdentifier::Flutterwave, [
            'title' => 'Dusk Flutterwave',
            'keys' => [
                'public_key' => 'dusk-flutterwave-public',
                'secret_key' => 'dusk-flutterwave-secret',
            ],
        ]);
        $this->upsertGateway(PaymentGatewayIdentifier::Paypal, [
            'title' => 'Dusk Paypal',
            'keys' => [
                'sandbox_client_id' => 'dusk-paypal-sandbox-client',
                'production_client_id' => 'dusk-paypal-production-client',
            ],
        ]);
        $this->upsertGateway(PaymentGatewayIdentifier::Paystack, [
            'title' => 'Dusk Paystack',
            'keys' => [
                'public_test_key' => 'dusk-paystack-public-test',
                'public_live_key' => 'dusk-paystack-public-live',
            ],
        ]);
        $this->upsertGateway(PaymentGatewayIdentifier::Paytm, [
            'title' => 'Dusk Paytm',
            'keys' => [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertGateway(PaymentGatewayIdentifier $identifier, array $attributes): void
    {
        $gateway = PaymentGateway::query()
            ->where('identifier', $identifier->value)
            ->first() ?? new PaymentGateway;

        $gateway->forceFill(array_merge([
            'identifier' => $identifier->value,
            'currency' => 'USD',
            'description' => 'Dusk payment gateway.',
            'model_name' => Str::studly($identifier->value),
            'test_mode' => 1,
            'status' => 1,
            'is_addon' => 0,
        ], $attributes));
        $gateway->save();
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $setting->save();
    }

    private function restorePaymentState(): void
    {
        foreach ($this->originalGateways as $identifier => $attributes) {
            PaymentGateway::query()
                ->where('identifier', $identifier)
                ->delete();

            if ($attributes === null) {
                continue;
            }

            $gateway = new PaymentGateway;
            $gateway->forceFill($attributes);
            $gateway->save();
        }

        foreach ($this->originalSettings as $type => $description) {
            if ($description === null) {
                Setting::query()
                    ->where('type', $type)
                    ->delete();

                continue;
            }

            $this->upsertSetting($type, $description);
        }
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->where('email', self::USER_EMAIL)
            ->pluck('id');

        PaymentHistoryEntry::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('item_type', 'dusk-payment')
            ->delete();

        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
