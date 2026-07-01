<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiControllerResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_controller_does_not_return_raw_json_strings(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/ApiController.php'));

        $this->assertDoesNotMatchRegularExpression('/^\s*return\s+json_encode\(/m', $contents);
    }

    public function test_web_controllers_use_named_route_redirect_helpers_for_internal_routes(): void
    {
        $controllers = collect([
            app_path('Http/Controllers/AdminCrudController.php'),
            app_path('Http/Controllers/InstallController.php'),
            app_path('Http/Controllers/MarketplaceController.php'),
            app_path('Http/Controllers/PaymentController.php'),
            app_path('Http/Controllers/StoryController.php'),
            app_path('Http/Controllers/UserController.php'),
        ]);

        foreach ($controllers as $controller) {
            $contents = file_get_contents($controller);

            $this->assertDoesNotMatchRegularExpression('/^\s*return\s+redirect\(route\(/m', $contents, $controller);
            $this->assertDoesNotMatchRegularExpression('/^\s*return\s+redirect\([\'"]\/(?:login)?[\'"]\)/m', $contents, $controller);
        }
    }

    public function test_signup_validation_errors_are_returned_as_json_response(): void
    {
        $response = $this->postJson(route('api.auth.signup'), []);

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure([
                'validationError' => [
                    'name',
                    'email',
                    'password',
                ],
            ]);
    }
}
