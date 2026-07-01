<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDisableRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_account_disable_page(): void
    {
        $this->get(route('frontend.disable_view'))
            ->assertRedirect(route('login'));
    }

    public function test_disabled_user_can_view_account_disable_page(): void
    {
        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Disabled->value,
            'photo' => null,
        ]);

        $this->actingAs($user)
            ->get(route('frontend.disable_view'))
            ->assertOk()
            ->assertSee('Your account has been Deactivate');
    }

    public function test_unverified_disabled_user_can_view_account_disable_page(): void
    {
        $user = User::factory()->unverified()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Disabled->value,
            'photo' => null,
        ]);

        $this->actingAs($user)
            ->get(route('frontend.disable_view'))
            ->assertOk()
            ->assertSee('Your account has been Deactivate');
    }

    public function test_disable_view_does_not_query_account_requests_in_blade(): void
    {
        $contents = file_get_contents(resource_path('views/frontend/disable_view.blade.php'));

        $this->assertStringNotContainsString('DB::table', $contents);
        $this->assertStringNotContainsString('account_active_requests', $contents);
    }
}
