<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_api_methods_are_split_out_of_god_api_controller(): void
    {
        $apiController = file_get_contents(app_path('Http/Controllers/ApiController.php'));
        $apiRoutes = file_get_contents(base_path('routes/api.php'));

        $this->assertStringNotContainsString('public function notifications(', $apiController);
        $this->assertStringNotContainsString('public function mark_as_read(', $apiController);
        $this->assertStringContainsString('ApiNotificationController::class', $apiRoutes);
    }

    public function test_notifications_endpoint_rejects_missing_bearer_token(): void
    {
        $this->getJson(route('api.notifications.index'))
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access',
            ]);
    }

    public function test_notifications_endpoint_returns_current_and_older_notifications(): void
    {
        $receiver = User::factory()->create();
        $sender = User::factory()->create(['name' => 'Sender User']);

        $currentNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'friend_request',
            'status' => 0,
            'view' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $olderNotification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'group_invitation',
            'status' => 1,
            'view' => 1,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($receiver);

        $this->withToken('test-token')
            ->getJson(route('api.notifications.index'))
            ->assertOk()
            ->assertJsonPath('new_notifications.0.id', $currentNotification->id)
            ->assertJsonPath('new_notifications.0.sender_user_id', $sender->id)
            ->assertJsonPath('new_notifications.0.reciver_user_id', $receiver->id)
            ->assertJsonPath('new_notifications.0.name', 'Sender User')
            ->assertJsonPath('new_notifications.0.type', 'friend_request')
            ->assertJsonPath('new_notifications.0.status', 0)
            ->assertJsonPath('new_notifications.0.view', 0)
            ->assertJsonPath('older_notifications.0.id', $olderNotification->id)
            ->assertJsonPath('older_notifications.0.type', 'group_invitation')
            ->assertJsonPath('older_notifications.0.status', 1)
            ->assertJsonPath('older_notifications.0.view', 1);
    }

    public function test_mark_as_read_updates_notification_status_and_view(): void
    {
        $receiver = User::factory()->create();
        $sender = User::factory()->create();
        $notification = $this->notification([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'friend_request',
            'status' => 0,
            'view' => 0,
        ]);

        Sanctum::actingAs($receiver);

        $this->withToken('test-token')
            ->postJson(route('api.notifications.read', $notification))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'mark as read',
            ]);

        $notification->refresh();

        $this->assertSame(1, (int) $notification->status);
        $this->assertSame(1, (int) $notification->view);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function notification(array $attributes): Notification
    {
        $notification = new Notification;
        $notification->forceFill($attributes);
        $notification->save();

        return $notification;
    }
}
