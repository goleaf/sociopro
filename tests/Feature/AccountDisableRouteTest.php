<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\AccountActiveRequest;
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

    public function test_disabled_page_shows_pending_activation_request_state(): void
    {
        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Disabled->value,
            'photo' => null,
        ]);
        AccountActiveRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('frontend.disable_view'))
            ->assertOk()
            ->assertSee('Account Active Request Pending')
            ->assertDontSee('Request Account Activation');
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

    public function test_request_enable_creates_pending_activation_request_for_current_user(): void
    {
        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Disabled->value,
        ]);

        $this->actingAs($user)
            ->post(route('frontend.account_enble_req', $user))
            ->assertRedirect();

        $this->assertDatabaseHas('account_active_requests', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_request_enable_reuses_existing_activation_request(): void
    {
        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Disabled->value,
        ]);
        $request = AccountActiveRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'declined',
        ]);

        $this->actingAs($user)
            ->post(route('frontend.account_enble_req', $user))
            ->assertRedirect();

        $this->assertSame(1, AccountActiveRequest::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('account_active_requests', [
            'id' => $request->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_request_enable_rejects_url_bypass_for_another_user(): void
    {
        $actor = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Disabled->value,
        ]);
        $target = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Disabled->value,
        ]);

        $this->actingAs($actor)
            ->post(route('frontend.account_enble_req', $target))
            ->assertForbidden();

        $this->assertDatabaseMissing('account_active_requests', [
            'user_id' => $target->id,
        ]);
    }

    public function test_request_enable_does_not_mutate_state_over_get(): void
    {
        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Disabled->value,
        ]);

        $this->actingAs($user)
            ->get(route('frontend.account_enble_req', $user))
            ->assertMethodNotAllowed();

        $this->assertDatabaseMissing('account_active_requests', [
            'user_id' => $user->id,
        ]);
    }

    public function test_disable_view_does_not_query_account_requests_in_blade(): void
    {
        $contents = file_get_contents(resource_path('views/frontend/disable_view.blade.php'));

        $this->assertStringNotContainsString('DB::table', $contents);
        $this->assertStringNotContainsString('account_active_requests', $contents);
    }

    public function test_activation_request_form_posts_with_csrf(): void
    {
        $contents = file_get_contents(resource_path('views/frontend/disable_view.blade.php'));

        $this->assertStringContainsString('method="POST"', $contents);
        $this->assertStringContainsString('@csrf', $contents);
    }
}
