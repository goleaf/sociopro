<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentControllerGatewayQueryTest extends TestCase
{
    public function test_payment_controller_uses_eloquent_for_gateway_queries(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/PaymentController.php'));

        $this->assertStringNotContainsString("DB::table('payment_gateways')", $contents);
        $this->assertStringNotContainsString('use DB;', $contents);
    }

    public function test_payment_controller_delegates_gateway_service_resolution(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/PaymentController.php'));

        $this->assertStringNotContainsString('new $model_full_path', $contents);
        $this->assertStringNotContainsString('gatewayModelClass', $contents);
    }
}
