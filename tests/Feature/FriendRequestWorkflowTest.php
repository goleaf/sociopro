<?php

namespace Tests\Feature;

use App\Actions\Friends\AcceptFriendRequestAction;
use App\Actions\Friends\SendFriendRequestAction;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Follower;
use App\Models\Friendships;
use App\Models\Notification;
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

    public function test_send_friend_request_reuses_existing_follower_pair(): void
    {
        $requester = $this->activeUser();
        $accepter = $this->activeUser();

        $existingFollower = new Follower;
        $existingFollower->user_id = $requester->id;
        $existingFollower->follow_id = $accepter->id;
        $existingFollower->save();

        app(SendFriendRequestAction::class)->handle($requester, $accepter->id);

        $this->assertSame(1, Follower::where('user_id', $requester->id)->where('follow_id', $accepter->id)->count());
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
    }

    public function test_accept_friend_request_rolls_back_all_writes_when_acceptance_notification_fails(): void
    {
        $requester = $this->activeUser();
        $accepter = $this->activeUser();

        $this->pendingFriendRequest($requester, $accepter);

        $action = new class extends AcceptFriendRequestAction
        {
            protected function createAcceptanceNotification(User $sender, int $receiverId): Notification
            {
                throw new RuntimeException('Simulated acceptance notification failure.');
            }
        };

        try {
            $action->acceptFromProfile($accepter, $requester->id);
            $this->fail('Expected friend request acceptance to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated acceptance notification failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('friendships', [
            'requester' => $requester->id,
            'accepter' => $accepter->id,
            'is_accepted' => 0,
        ]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $requester->id,
            'reciver_user_id' => $accepter->id,
            'type' => 'profile',
            'status' => 0,
            'view' => 0,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'sender_user_id' => $accepter->id,
            'reciver_user_id' => $requester->id,
            'type' => 'friend_request_accept',
        ]);
        $this->assertDatabaseMissing('followers', [
            'user_id' => $accepter->id,
            'follow_id' => $requester->id,
        ]);
        $this->assertSame([], $this->friendIds($requester));
        $this->assertSame([], $this->friendIds($accepter));
    }

    public function test_profile_accept_friend_request_preserves_response_and_writes_related_records(): void
    {
        $requester = $this->activeUser();
        $accepter = $this->activeUser();

        $this->pendingFriendRequest($requester, $accepter);

        $response = $this->actingAs($accepter)->post(route('profile.accept_friend_request'), [
            'user_id' => $requester->id,
        ]);

        $response->assertOk();
        $this->assertSame(json_encode([
            'alertMessage' => get_phrase('Friend request accepted'),
            'showElem' => '#friendRequestAcceptedBtn'.$requester->id,
            'hideElem' => '#friendRequestConfirmBtn'.$requester->id,
        ]), $response->getContent());

        $this->assertFriendAccepted($requester, $accepter);
        $this->assertDatabaseHas('followers', [
            'user_id' => $accepter->id,
            'follow_id' => $requester->id,
        ]);
    }

    public function test_notification_accept_friend_request_preserves_response_and_writes_related_records(): void
    {
        $requester = $this->activeUser();
        $accepter = $this->activeUser();

        $this->pendingFriendRequest($requester, $accepter);

        $response = $this->actingAs($accepter)->get(route('accept.friend.request.from.notification', $requester->id));

        $response->assertOk();
        $response->assertSessionHas('success_message', get_phrase('Friend Request Accepted'));
        $this->assertSame(json_encode(['reload' => 1]), $response->getContent());

        $this->assertFriendAccepted($requester, $accepter);
        $this->assertDatabaseMissing('followers', [
            'user_id' => $accepter->id,
            'follow_id' => $requester->id,
        ]);
    }

    public function test_api_notification_accept_friend_request_preserves_response_and_writes_related_records(): void
    {
        $requester = $this->activeUser();
        $accepter = $this->activeUser();

        $this->pendingFriendRequest($requester, $accepter);

        $token = $accepter->createToken('api-test')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.notifications.friends.accept', $requester->id))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Friend request accept',
            ]);

        $this->assertFriendAccepted($requester, $accepter);
        $this->assertDatabaseMissing('followers', [
            'user_id' => $accepter->id,
            'follow_id' => $requester->id,
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

    private function pendingFriendRequest(User $requester, User $accepter): void
    {
        Friendships::create([
            'requester' => $requester->id,
            'accepter' => $accepter->id,
            'is_accepted' => 0,
        ]);

        $notification = new Notification;
        $notification->sender_user_id = $requester->id;
        $notification->reciver_user_id = $accepter->id;
        $notification->type = 'profile';
        $notification->status = 0;
        $notification->view = 0;
        $notification->save();
    }

    /**
     * @return list<int>
     */
    private function friendIds(User $user): array
    {
        $friends = json_decode((string) $user->refresh()->friends, true);

        return is_array($friends) ? array_values($friends) : [];
    }

    private function assertFriendAccepted(User $requester, User $accepter): void
    {
        $this->assertDatabaseHas('friendships', [
            'requester' => $requester->id,
            'accepter' => $accepter->id,
            'is_accepted' => 1,
        ]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $requester->id,
            'reciver_user_id' => $accepter->id,
            'type' => 'profile',
            'status' => 1,
            'view' => 1,
        ]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $accepter->id,
            'reciver_user_id' => $requester->id,
            'type' => 'friend_request_accept',
        ]);
        $this->assertSame([$accepter->id], $this->friendIds($requester));
        $this->assertSame([$requester->id], $this->friendIds($accepter));
    }
}
