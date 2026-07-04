<?php

namespace Tests\Feature;

use App\Http\Controllers\UserWelcomeController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class UserWelcomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_welcome_controller_is_invokable(): void
    {
        $controller = new ReflectionClass(UserWelcomeController::class);

        $this->assertTrue($controller->hasMethod('__invoke'));
        $this->assertTrue($controller->getMethod('__invoke')->isPublic());
    }

    public function test_users_welcome_route_keeps_public_invokable_contract(): void
    {
        $route = Route::getRoutes()->getByName('users.welcome');

        $this->assertNotNull($route);
        $this->assertSame(UserWelcomeController::class, $route->getActionName());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame('users/{user_id}', $route->uri());

        $middleware = $route->gatherMiddleware();

        $this->assertContains('web', $middleware);
        $this->assertNotContains('auth', $middleware);
        $this->assertNotContains('user', $middleware);
        $this->assertNotContains('admin', $middleware);
        $this->assertNotContains('verified', $middleware);
    }

    public function test_user_welcome_route_renders_welcome_view_for_any_user_id(): void
    {
        $response = $this->get(route('users.welcome', ['user_id' => 'legacy-user-123']));

        $response->assertOk();
        $response->assertViewIs('welcome');
        $response->assertSee('SocioPro', false);
    }
}
