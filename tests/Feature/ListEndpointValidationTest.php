<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\AdminCrudController;
use App\Http\Controllers\PaymentHistory;
use App\Http\Controllers\Updater;
use App\Models\AccountActiveRequest;
use App\Models\Addon;
use App\Models\Friendships;
use App\Models\Notification;
use App\Models\PaymentHistoryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ListEndpointValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_datatable_caps_page_size_and_falls_back_from_unsafe_sorting(): void
    {
        $admin = $this->adminUser();
        $latestUser = null;

        for ($index = 1; $index <= 105; $index++) {
            $latestUser = $this->generalUser([
                'name' => sprintf('Listed User %03d', $index),
                'email' => sprintf('listed-user-%03d@example.test', $index),
            ]);
        }

        $response = $this->actingAs($admin)->postJson(route('admin.server_side_users_data'), [
            'draw' => '7',
            'start' => '0',
            'length' => '500',
            'order' => [
                [
                    'column' => '999',
                    'dir' => 'drop table users',
                ],
            ],
            'search' => [
                'value' => null,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('draw', 7)
            ->assertJsonCount(100, 'data');

        $this->assertSame($latestUser->email, $response->json('data.0.email'));
    }

    public function test_admin_users_datatable_sorts_only_allowed_columns_and_directions(): void
    {
        $admin = $this->adminUser();
        $this->generalUser([
            'name' => 'Zulu User',
            'email' => 'zulu@example.test',
        ]);
        $this->generalUser([
            'name' => 'Alpha User',
            'email' => 'alpha@example.test',
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.server_side_users_data'), [
            'draw' => '1',
            'start' => '0',
            'length' => '10',
            'order' => [
                [
                    'column' => '3',
                    'dir' => 'asc',
                ],
            ],
            'search' => [
                'value' => null,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.0.email', 'alpha@example.test')
            ->assertJsonPath('data.1.email', 'zulu@example.test');
    }

    public function test_admin_users_datatable_uses_id_tie_breaker_for_duplicate_sort_values(): void
    {
        $admin = $this->adminUser();
        $olderUser = $this->generalUser([
            'name' => 'Duplicate Name',
            'email' => 'older-duplicate-name@example.test',
        ]);
        $newerUser = $this->generalUser([
            'name' => 'Duplicate Name',
            'email' => 'newer-duplicate-name@example.test',
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.server_side_users_data'), [
            'draw' => '1',
            'start' => '0',
            'length' => '10',
            'order' => [
                [
                    'column' => '2',
                    'dir' => 'asc',
                ],
            ],
            'search' => [
                'value' => 'Duplicate Name',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.0.email', $newerUser->email)
            ->assertJsonPath('data.1.email', $olderUser->email);
    }

    public function test_admin_users_datatable_handles_malformed_filter_shapes(): void
    {
        $admin = $this->adminUser();
        $latestUser = $this->generalUser([
            'name' => 'Malformed Payload User',
            'email' => 'malformed-payload@example.test',
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.server_side_users_data'), [
            'draw' => ['7'],
            'start' => ['0'],
            'length' => ['500'],
            'order' => [
                [
                    'column' => ['3'],
                    'dir' => ['asc'],
                ],
            ],
            'search' => 'not-a-search-array',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('draw', 0)
            ->assertJsonPath('data.0.email', $latestUser->email);
    }

    public function test_payment_history_indexes_are_paginated_and_scoped(): void
    {
        $admin = $this->adminUser();
        $owner = $this->generalUser();
        $otherUser = $this->generalUser();

        for ($index = 1; $index <= 31; $index++) {
            $this->paymentHistory($owner, $index);
        }

        for ($index = 1; $index <= 3; $index++) {
            $this->paymentHistory($otherUser, 100 + $index);
        }

        Auth::login($admin);

        $adminView = app(PaymentHistory::class)->index();
        $adminHistories = $adminView->getData()['payment_histories'];

        $this->assertSame(25, $adminHistories->count());
        $this->assertSame(34, $adminHistories->total());

        Auth::login($owner);

        $userView = app(PaymentHistory::class)->index();
        $userHistories = $userView->getData()['payment_histories'];

        $this->assertSame(25, $userHistories->count());
        $this->assertSame(31, $userHistories->total());
        $this->assertTrue($userHistories->getCollection()->every(
            fn (object $history): bool => (int) $history->user_id === $owner->id
        ));
    }

    public function test_addon_manager_paginator_uses_newest_addon_order(): void
    {
        $oldestAddon = Addon::query()->create([
            'title' => 'Oldest addon',
            'unique_identifier' => 'oldest-addon',
            'features' => '[]',
            'version' => '1.0.0',
            'status' => 1,
        ]);
        $newestAddon = Addon::query()->create([
            'title' => 'Newest addon',
            'unique_identifier' => 'newest-addon',
            'features' => '[]',
            'version' => '1.0.0',
            'status' => 1,
        ]);

        $view = app(Updater::class)->addon_manager();
        $addons = $view->getData()['addons'];

        $this->assertSame(
            [$newestAddon->id, $oldestAddon->id],
            $addons->getCollection()->pluck('id')->all()
        );
    }

    public function test_account_activation_requests_use_id_tie_breaker_for_duplicate_timestamps(): void
    {
        $timestamp = now();
        $olderUser = $this->generalUser([
            'email' => 'older-account-request@example.test',
        ]);
        $newerUser = $this->generalUser([
            'email' => 'newer-account-request@example.test',
        ]);

        $olderRequest = new AccountActiveRequest;
        $olderRequest->forceFill([
            'user_id' => $olderUser->id,
            'status' => 0,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->save();

        $newerRequest = new AccountActiveRequest;
        $newerRequest->forceFill([
            'user_id' => $newerUser->id,
            'status' => 0,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->save();

        $view = app(AdminCrudController::class)->accountActiveReq();
        $requestUsers = $view->getData()['request_users'];

        $this->assertSame(
            [$newerRequest->id, $olderRequest->id],
            $requestUsers->getCollection()->pluck('id')->all()
        );
    }

    public function test_profile_friend_request_window_uses_newest_request_order(): void
    {
        $receiver = $this->generalUser();
        $olderRequester = $this->generalUser([
            'email' => 'older-friend-requester@example.test',
        ]);
        $newerRequester = $this->generalUser([
            'email' => 'newer-friend-requester@example.test',
        ]);

        $olderRequest = Friendships::query()->create([
            'requester' => $olderRequester->id,
            'accepter' => $receiver->id,
            'importance' => 0,
            'is_accepted' => 0,
        ]);
        $newerRequest = Friendships::query()->create([
            'requester' => $newerRequester->id,
            'accepter' => $receiver->id,
            'importance' => 0,
            'is_accepted' => 0,
        ]);

        $response = $this->actingAs($receiver)
            ->get(route('profile.load_my_friend_requests', ['offset' => 0]))
            ->assertOk();

        $friendRequests = $response->viewData('friend_requests');

        $this->assertSame(
            [$newerRequest->id, $olderRequest->id],
            $friendRequests->pluck('id')->all()
        );
    }

    public function test_api_friend_request_list_uses_newest_request_order(): void
    {
        $receiver = $this->generalUser();
        $olderRequester = $this->generalUser([
            'name' => 'Older API Friend Requester',
            'email' => 'older-api-friend-requester@example.test',
        ]);
        $newerRequester = $this->generalUser([
            'name' => 'Newer API Friend Requester',
            'email' => 'newer-api-friend-requester@example.test',
        ]);
        $token = $receiver->createToken('friend-request-order-test')->plainTextToken;

        Friendships::query()->create([
            'requester' => $olderRequester->id,
            'accepter' => $receiver->id,
            'importance' => 0,
            'is_accepted' => 0,
        ]);
        Friendships::query()->create([
            'requester' => $newerRequester->id,
            'accepter' => $receiver->id,
            'importance' => 0,
            'is_accepted' => 0,
        ]);

        $response = $this->withToken($token)
            ->getJson(route('api.friend_requests.index'))
            ->assertOk();

        $this->assertSame(
            [$newerRequester->id, $olderRequester->id],
            array_column($response->json('friendsList'), 'friend_id')
        );
    }

    public function test_web_notifications_index_paginates_new_and_older_lists(): void
    {
        $receiver = $this->generalUser();
        $sender = $this->generalUser();

        for ($index = 1; $index <= 31; $index++) {
            $this->notification($receiver, $sender, [
                'status' => 0,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
            $this->notification($receiver, $sender, [
                'status' => 1,
                'view' => 1,
                'created_at' => now()->subDays(2)->subMinutes($index),
                'updated_at' => now()->subDays(2)->subMinutes($index),
            ]);
        }

        $response = $this->actingAs($receiver)
            ->get(route('notifications'))
            ->assertOk();

        $newNotifications = $response->viewData('new_notification');
        $olderNotifications = $response->viewData('older_notification');

        $this->assertSame(25, $newNotifications->count());
        $this->assertSame(31, $newNotifications->total());
        $this->assertSame(25, $olderNotifications->count());
        $this->assertSame(31, $olderNotifications->total());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function adminUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'user_role' => UserRole::Admin->value,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function generalUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'user_role' => UserRole::General->value,
        ], $overrides));
    }

    private function paymentHistory(User $user, int $index): PaymentHistoryEntry
    {
        $history = new PaymentHistoryEntry;
        $history->forceFill([
            'item_type' => 'badge',
            'item_id' => $index,
            'user_id' => $user->id,
            'amount' => '10.00',
            'currency' => 'USD',
            'identifier' => 'test-history-'.$index,
            'created_at' => now()->subMinutes($index),
            'updated_at' => now()->subMinutes($index),
        ])->save();

        return $history;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function notification(User $receiver, User $sender, array $overrides = []): Notification
    {
        $notification = new Notification;
        $notification->forceFill(array_merge([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'friend_request_accept',
            'status' => 0,
            'view' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides))->save();

        return $notification;
    }
}
