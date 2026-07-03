<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\PaymentHistory;
use App\Models\PaymentHistoryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class PaymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_method_stays_public(): void
    {
        $controller = new ReflectionClass(PaymentHistory::class);

        $this->assertTrue($controller->hasMethod('index'));
        $this->assertTrue($controller->getMethod('index')->isPublic());
    }

    public function test_payment_history_routes_keep_expected_contracts(): void
    {
        $adminRoute = Route::getRoutes()->getByName('admin.payment_histories');
        $userRoute = Route::getRoutes()->getByName('user.payment_histories');

        $this->assertNotNull($adminRoute);
        $this->assertSame(PaymentHistory::class.'@index', $adminRoute->getActionName());
        $this->assertSame(['GET', 'HEAD'], $adminRoute->methods());
        $this->assertSame('admin/payment-histories', $adminRoute->uri());
        $this->assertContains('admin', $adminRoute->gatherMiddleware());

        $this->assertNotNull($userRoute);
        $this->assertSame(PaymentHistory::class.'@index', $userRoute->getActionName());
        $this->assertSame(['GET', 'HEAD'], $userRoute->methods());
        $this->assertSame('user/payment-histories', $userRoute->uri());
        $this->assertContains('user', $userRoute->gatherMiddleware());
    }

    public function test_index_paginates_all_payment_histories_for_admin(): void
    {
        $admin = $this->activeUser(UserRole::Admin, ['email' => 'payment-history-admin@example.test']);
        $owner = $this->activeUser(UserRole::General, ['email' => 'payment-history-owner@example.test']);
        $otherUser = $this->activeUser(UserRole::General, ['email' => 'payment-history-other@example.test']);

        $ownerHistory = PaymentHistoryEntry::factory()->create([
            'user_id' => $owner->id,
            'item_type' => 'admin-visible-history',
            'amount' => '19.50',
            'currency' => 'USD',
        ]);
        $otherHistory = PaymentHistoryEntry::factory()->create([
            'user_id' => $otherUser->id,
            'item_type' => 'other-visible-history',
            'amount' => '29.50',
            'currency' => 'EUR',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.payment_histories'));

        $histories = $response->viewData('payment_histories');

        $response
            ->assertOk()
            ->assertViewIs('backend.index')
            ->assertViewHas('view_path', 'payment_history.index')
            ->assertSee('19.50 USD')
            ->assertSee('29.50 EUR');

        $this->assertSame([$otherHistory->id, $ownerHistory->id], $histories->pluck('id')->all());
    }

    public function test_index_scopes_payment_histories_to_current_general_user(): void
    {
        $owner = $this->activeUser(UserRole::General, ['email' => 'payment-history-current@example.test']);
        $otherUser = $this->activeUser(UserRole::General, ['email' => 'payment-history-hidden@example.test']);

        $ownerHistory = PaymentHistoryEntry::factory()->create([
            'user_id' => $owner->id,
            'item_type' => 'user-visible-history',
            'amount' => '13.25',
            'currency' => 'USD',
        ]);
        PaymentHistoryEntry::factory()->create([
            'user_id' => $otherUser->id,
            'item_type' => 'user-hidden-history',
            'amount' => '88.88',
            'currency' => 'EUR',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get(route('user.payment_histories'));

        $histories = $response->viewData('payment_histories');

        $response
            ->assertOk()
            ->assertViewIs('backend.index')
            ->assertViewHas('view_path', 'payment_history.index')
            ->assertSee('13.25 USD')
            ->assertDontSee('88.88 EUR');

        $this->assertSame([$ownerHistory->id], $histories->pluck('id')->all());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeUser(UserRole $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ], $overrides));
    }
}
