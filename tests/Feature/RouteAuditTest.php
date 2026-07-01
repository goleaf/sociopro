<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_http_route_files_do_not_register_closure_routes(): void
    {
        $routeFiles = [
            'api.php',
            'auth.php',
            'custom_routes.php',
            'payment.php',
            'user.php',
            'web.php',
        ];

        foreach ($routeFiles as $routeFile) {
            $contents = file_get_contents(base_path("routes/{$routeFile}"));

            $this->assertDoesNotMatchRegularExpression(
                '/Route::(?:get|post|put|patch|delete|match|any)\s*\([^;]*function\s*\(/s',
                $contents,
                "{$routeFile} contains an HTTP closure route."
            );
        }
    }

    public function test_clear_cache_route_requires_admin_authentication(): void
    {
        $this->get('/clear-cache')
            ->assertRedirect(route('login'));

        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
        ]);

        $this->actingAs($user)
            ->get('/clear-cache')
            ->assertRedirect();
    }

    public function test_admin_prefixed_routes_require_admin_middleware(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'admin/')) {
                continue;
            }

            $this->assertContains(
                'admin',
                $route->gatherMiddleware(),
                "{$route->methods()[0]} {$route->uri()} is missing admin middleware."
            );
        }
    }

    public function test_known_duplicate_route_declarations_are_removed(): void
    {
        $webRoutes = file_get_contents(base_path('routes/web.php'));
        $customRoutes = file_get_contents(base_path('routes/custom_routes.php'));

        $this->assertSame(
            1,
            substr_count($webRoutes, "Route::any('/stories/{offset?}/{limit?}', 'stories')->name('stories');")
        );

        $this->assertSame(
            1,
            substr_count($customRoutes, "Route::get('save/video/short/{id}', 'save_for_later')->name('save.video.later');")
        );
    }
}
