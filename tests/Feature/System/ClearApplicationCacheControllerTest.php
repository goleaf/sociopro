<?php

namespace Tests\Feature\System;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\System\ClearApplicationCacheController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class ClearApplicationCacheControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_method_stays_public(): void
    {
        $controller = new ReflectionClass(ClearApplicationCacheController::class);

        $this->assertTrue($controller->hasMethod('__invoke'));
        $this->assertTrue($controller->getMethod('__invoke')->isPublic());
    }

    public function test_clear_cache_route_keeps_expected_contract(): void
    {
        $route = Route::getRoutes()->getByName('system.clear-cache');

        $this->assertNotNull($route);
        $this->assertSame(ClearApplicationCacheController::class, $route->getActionName());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame('clear-cache', $route->uri());

        foreach (['auth', 'verified', 'admin'] as $middleware) {
            $this->assertContains($middleware, $route->gatherMiddleware());
        }
    }

    public function test_admin_can_clear_application_cache(): void
    {
        Artisan::shouldReceive('call')->once()->with('cache:clear')->ordered()->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('config:clear')->ordered()->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('route:clear')->ordered()->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('view:clear')->ordered()->andReturn(0);

        $this->actingAs($this->adminUser())
            ->get(route('system.clear-cache'))
            ->assertOk()
            ->assertContent('Application cache cleared');
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ]);
    }
}
