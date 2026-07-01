<?php

namespace Tests\Feature;

use App\Actions\Friends\SendFriendRequestAction;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Follower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class FriendRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_friend_route_preserves_ajax_response_and_creates_related_records(): void
    {
        $requester = $this->activeUser();
        $accepter = $this->activeUser();

        $response = $this->actingAs($requester)->get(route('user.friend', $accepter->id));

        $response->assertOk();
        $this->assertSame(json_encode(['reload' => 1]), $response->getContent());
        $response->assertSessionHas('success_message', get_phrase('Friend Request Sent Successfully'));

        $this->assertDatabaseHas('friendships', [
            'requester' => $requester->id,
            'accepter' => $accepter->id,
            'is_accepted' => 0,
        ]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $requester->id,
            'reciver_user_id' => $accepter->id,
            'type' => 'profile',
        ]);
        $this->assertDatabaseHas('followers', [
            'user_id' => $requester->id,
            'follow_id' => $accepter->id,
        ]);
    }

    public function test_send_friend_request_rolls_back_all_writes_when_follower_creation_fails(): void
    {
        $requester = $this->activeUser();
        $accepter = $this->activeUser();

        $action = new class extends SendFriendRequestAction
        {
            protected function createFollower(User $requester, int $accepterId): Follower
            {
                throw new RuntimeException('Simulated follower failure.');
            }
        };

        try {
            $action->handle($requester, $accepter->id);
            $this->fail('Expected friend request creation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated follower failure.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('friendships', [
            'requester' => $requester->id,
            'accepter' => $accepter->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'sender_user_id' => $requester->id,
            'reciver_user_id' => $accepter->id,
        ]);
        $this->assertDatabaseMissing('followers', [
            'user_id' => $requester->id,
            'follow_id' => $accepter->id,
        ]);
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'friends' => json_encode([]),
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
        ]);
    }
}
