<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\User;
use Illuminate\View\View;
use Tests\TestCase;

class PaymentPageViewDataTest extends TestCase
{
    public function test_payment_index_view_does_not_query_settings(): void
    {
        $contents = file_get_contents(resource_path('views/payment/index.blade.php'));

        $this->assertStringNotContainsString('\App\Models\Setting', $contents);
        $this->assertStringNotContainsString('Setting::where', $contents);
    }

    public function test_payment_controller_passes_page_settings_to_index_view(): void
    {
        Setting::where('type', 'system_name')->update(['description' => 'SocioPro Test']);
        Setting::where('type', 'system_fav_icon')->update(['description' => 'favicon-test.png']);

        session(['payment_details' => [
            'payable_amount' => 10,
            'cancel_url' => '/cancel',
            'items' => [
                [
                    'title' => 'Test item',
                    'price' => 10,
                    'discount_percentage' => 0,
                    'discount_price' => 10,
                ],
            ],
            'tax' => 0,
        ]]);

        $response = app(PaymentController::class)->index();

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('SocioPro Test', $response->getData()['system_name']);
        $this->assertSame('favicon-test.png', $response->getData()['system_favicon']);
    }

    public function test_paystack_view_data_uses_precomputed_minor_unit_amount(): void
    {
        $user = User::factory()->create();

        PaymentGateway::query()
            ->where('identifier', 'paystack')
            ->update([
                'keys' => json_encode([
                    'public_test_key' => 'pk_test_paystack',
                ]),
                'test_mode' => 1,
            ]);

        session(['payment_details' => [
            'payable_amount' => '19.50',
            'success_url' => '/payment/success',
            'cancel_url' => '/payment/cancel',
            'items' => [
                [
                    'title' => 'Test item',
                    'price' => '19.50',
                    'discount_percentage' => 0,
                    'discount_price' => '19.50',
                ],
            ],
            'tax' => 0,
        ]]);

        $this->actingAs($user);

        $response = app(PaymentController::class)->show_payment_gateway_by_ajax('paystack');

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('19.50', $response->getData()['amount']);
        $this->assertSame(1950, $response->getData()['amount_minor']);
    }
}
