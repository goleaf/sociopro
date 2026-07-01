<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function adminUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserAccountStatus::Active->value,
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
            'user_role' => UserRole::General->value,
        ], $overrides));
    }
}
