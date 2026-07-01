<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_admin_route_redirects_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_unverified_admin_is_redirected_to_email_verification_notice(): void
    {
        $admin = User::factory()->unverified()->create([
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_non_admin_web_admin_request_redirects_to_safe_timeline(): void
    {
        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
        ]);

        $this->actingAs($user)
            ->from(route('admin.users'))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('timeline'));
    }

    public function test_wrong_role_admin_request_redirects_to_safe_timeline(): void
    {
        $user = User::factory()->create([
            'user_role' => UserRole::Member->value,
            'status' => UserAccountStatus::Active->value,
        ]);

        $this->actingAs($user)
            ->from(route('admin.users'))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('timeline'));
    }

    public function test_unknown_super_admin_role_is_not_treated_as_admin(): void
    {
        $user = User::factory()->create([
            'user_role' => 'super-admin',
            'status' => UserAccountStatus::Active->value,
        ]);

        $this->actingAs($user)
            ->from(route('admin.users'))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('timeline'));
    }

    public function test_non_admin_json_admin_request_is_forbidden(): void
    {
        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
        ]);

        $this->actingAs($user)
            ->getJson(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_disabled_admin_web_admin_request_redirects_to_account_disabled_page(): void
    {
        $admin = User::factory()->create([
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Disabled->value,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users'))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('frontend.disable_view'));
    }

    public function test_disabled_admin_json_admin_request_is_forbidden(): void
    {
        $admin = User::factory()->create([
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Disabled->value,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_user_can_pass_admin_middleware(): void
    {
        $admin = User::factory()->create([
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
        ]);
        $request = Request::create('/admin/dashboard');
        $request->setUserResolver(fn (): User => $admin);

        $response = app(AdminMiddleware::class)->handle(
            $request,
            fn (): Response => new Response('ok')
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_admin_role_revocation_blocks_the_next_admin_middleware_check(): void
    {
        $admin = User::factory()->create([
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
        ]);

        $firstRequest = Request::create('/admin/dashboard');
        $firstRequest->setUserResolver(fn (): User => $admin);

        $firstResponse = app(AdminMiddleware::class)->handle(
            $firstRequest,
            fn (): Response => new Response('ok')
        );

        $admin->user_role = UserRole::General->value;

        $secondRequest = Request::create('/admin/dashboard');
        $secondRequest->setUserResolver(fn (): User => $admin);

        $secondResponse = app(AdminMiddleware::class)->handle(
            $secondRequest,
            fn (): Response => new Response('ok')
        );

        $this->assertSame('ok', $firstResponse->getContent());
        $this->assertSame(route('timeline'), $secondResponse->getTargetUrl());
    }

    public function test_soft_deleted_admin_users_are_not_applicable_to_current_user_model(): void
    {
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(User::class));
        $this->assertFalse(Schema::hasColumn('users', 'deleted_at'));
    }

    public function test_tenant_mismatch_is_not_applicable_to_current_admin_route_model(): void
    {
        $tenantColumns = ['tenant_id', 'team_id', 'account_id', 'organization_id'];

        foreach ($tenantColumns as $column) {
            $this->assertFalse(Schema::hasColumn('users', $column), "users.{$column} unexpectedly exists.");
        }

        foreach (Route::getRoutes() as $route) {
            $middleware = array_values($route->gatherMiddleware());

            if (! str_starts_with($route->uri(), 'admin/') && ! in_array('admin', $middleware, true)) {
                continue;
            }

            $this->assertStringNotContainsString('tenant', implode(',', $middleware), "{$route->uri()} has tenant middleware.");
        }
    }

    public function test_registered_admin_routes_have_secure_access_middleware_contract(): void
    {
        $adminRoutes = collect(Route::getRoutes())
            ->filter(function ($route): bool {
                $middleware = array_values($route->gatherMiddleware());

                return in_array('admin', $middleware, true) || str_starts_with($route->uri(), 'admin/');
            });

        $this->assertNotEmpty($adminRoutes);

        foreach ($adminRoutes as $route) {
            $middleware = array_values($route->gatherMiddleware());
            $identifier = "{$route->methods()[0]} {$route->uri()}";

            $this->assertContains('auth', $middleware, "{$identifier} is missing auth middleware.");
            $this->assertContains('verified', $middleware, "{$identifier} is missing verified middleware.");
            $this->assertContains('admin', $middleware, "{$identifier} is missing admin middleware.");
            $this->assertLessThan(
                array_search('verified', $middleware, true),
                array_search('auth', $middleware, true),
                "{$identifier} must authenticate before email verification."
            );
            $this->assertLessThan(
                array_search('admin', $middleware, true),
                array_search('verified', $middleware, true),
                "{$identifier} must verify email before admin authorization."
            );
        }
    }
}
