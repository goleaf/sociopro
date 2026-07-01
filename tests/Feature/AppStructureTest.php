<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppStructureTest extends TestCase
{
    public function test_payment_gateway_handlers_live_in_services_not_models(): void
    {
        $this->assertDirectoryDoesNotExist(app_path('Models/payment_gateway'));

        foreach (['Flutterwave', 'Paypal', 'Paystack', 'Paytm', 'Razorpay', 'StripePay'] as $gateway) {
            $contents = file_get_contents(app_path("Services/Payments/Gateways/{$gateway}.php"));

            $this->assertStringContainsString('namespace App\Services\Payments\Gateways;', $contents);
            $this->assertStringNotContainsString('extends Model', $contents);
        }
    }

    public function test_support_helpers_do_not_live_in_models_or_root_traits(): void
    {
        $this->assertFileDoesNotExist(app_path('Models/FileUploader.php'));
        $this->assertFileExists(app_path('Support/Files/FileUploader.php'));

        $this->assertDirectoryDoesNotExist(app_path('Traits'));
        $this->assertFileExists(app_path('Support/Zoom/ZoomMeetingTrait.php'));
    }
}
