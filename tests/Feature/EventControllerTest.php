<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Http\Controllers\Event\EventController;
use App\Models\Event;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_controller_routes_are_bound_to_expected_methods(): void
    {
        $routes = [
            'event' => ['GET', 'HEAD', 'events', 'allevents'],
            'userevent' => ['GET', 'HEAD', 'user/event', 'userevent'],
            'event.store' => ['POST', 'event/store', 'store'],
            'event.update' => ['POST', 'event/update/{id}', 'update'],
            'event.delete' => ['GET', 'HEAD', 'event/delete', 'event_delete'],
            'single.event' => ['GET', 'HEAD', 'event/view/{id}', 'single_event'],
            'event.going' => ['GET', 'HEAD', 'event/going/{id}', 'event_going'],
            'event.notgoing' => ['GET', 'HEAD', 'event/notgoing/{id}', 'event_notgoing'],
            'event.interested' => ['GET', 'HEAD', 'event/interested/{id}', 'event_interested'],
            'event.notinterested' => ['GET', 'HEAD', 'event/notinterested/{id}', 'event_notinterested'],
            'event.cancel' => ['GET', 'HEAD', 'event/cancel/{id}', 'event_cancel'],
            'event.invite' => ['GET', 'HEAD', 'event/invite/{invited_friend_id}/{requester_id}/{event_id}', 'event_invite'],
            'load_event_by_scrolling' => ['GET', 'HEAD', 'load_event_by_scrolling', 'load_event_by_scrolling'],
            'event.share' => ['GET', 'HEAD', 'share/event', 'shareevent'],
            'event.invition' => ['POST', 'event/invites/sent', 'sent_invition'],
            'search_user_for_event_inviting' => ['GET', 'HEAD', 'search_user_for_event_inviting', 'search_user_for_event_inviting'],
        ];

        foreach ($routes as $name => $contract) {
            $method = array_pop($contract);
            $uri = array_pop($contract);
            $expectedMethods = $contract;
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route [{$name}] is missing.");

            $actualMethods = $route->methods();
            sort($expectedMethods);
            sort($actualMethods);

            $this->assertSame($uri, $route->uri(), "Route [{$name}] URI changed.");
            $this->assertSame($expectedMethods, $actualMethods, "Route [{$name}] HTTP methods changed.");
            $this->assertSame(EventController::class.'@'.$method, $route->getActionName(), "Route [{$name}] action changed.");
        }
    }

    public function test_event_controller_method_surface_tracks_expected_public_actions(): void
    {
        $reflection = new ReflectionClass(EventController::class);

        foreach ([
            'allevents',
            'userevent',
            'store',
            'update',
            'event_delete',
            'single_event',
            'event_going',
            'event_notgoing',
            'event_interested',
            'event_notinterested',
            'event_cancel',
            'event_invite',
            'load_event_by_scrolling',
            'shareevent',
            'search_user_for_event_inviting',
            'sent_invition',
        ] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing public method [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPublic(), "Method [{$method}] must stay public.");
        }
    }

    public function test_event_owner_write_routes_reject_other_users_and_preserve_owner(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $event = Event::factory()->forOwner($owner)->create([
            'title' => 'Owner original event',
        ]);

        $this
            ->actingAs($otherUser)
            ->post(route('event.update', $event->id), $this->eventPayload([
                'eventname' => 'Unauthorized event title',
            ]))
            ->assertForbidden();

        $event->refresh();
        $this->assertSame($owner->id, (int) $event->user_id);
        $this->assertSame('Owner original event', $event->title);

        $this
            ->actingAs($otherUser)
            ->get(route('event.delete', ['event_id' => $event->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($owner)
            ->post(route('event.update', $event->id), $this->eventPayload([
                'eventname' => 'Owner updated event',
            ]))
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $event->refresh();
        $this->assertSame($owner->id, (int) $event->user_id);
        $this->assertSame('Owner updated event', $event->title);
    }

    public function test_event_invite_rejects_requester_spoofing_and_uses_authenticated_sender(): void
    {
        $sender = $this->activeUser();
        $spoofedSender = $this->activeUser();
        $invited = $this->activeUser();
        $event = Event::factory()->forOwner($sender)->create();

        $this
            ->actingAs($sender)
            ->get(route('event.invite', [$invited->id, $spoofedSender->id, $event->id]))
            ->assertForbidden();

        $this->assertDatabaseMissing('invites', [
            'invite_sender_id' => $spoofedSender->id,
            'invite_reciver_id' => $invited->id,
            'event_id' => $event->id,
        ]);

        $this
            ->actingAs($sender)
            ->get(route('event.invite', [$invited->id, $sender->id, $event->id]))
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this->assertDatabaseHas('invites', [
            'invite_sender_id' => $sender->id,
            'invite_reciver_id' => $invited->id,
            'event_id' => $event->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $invited->id,
            'type' => 'event',
            'event_id' => $event->id,
        ]);
    }

    public function test_event_interest_share_and_bulk_invite_actions_mutate_expected_records(): void
    {
        $user = $this->activeUser();
        $otherUser = $this->activeUser();
        $invitedOne = $this->activeUser();
        $invitedTwo = $this->activeUser();
        $event = Event::factory()->forOwner($otherUser)->create([
            'title' => 'Interactive event',
        ]);

        $this
            ->actingAs($user)
            ->get(route('event.going', $event->id))
            ->assertOk()
            ->assertJson(['alertMessage' => 'Going to Event']);

        $this->assertSame([$user->id], $this->eventUserIds($event->refresh(), 'going_users_id'));

        $event->forceFill(['going_users_id' => json_encode([$user->id, $otherUser->id])])->save();

        $this
            ->actingAs($user)
            ->get(route('event.notgoing', $event->id))
            ->assertOk()
            ->assertJson(['alertMessage' => 'Cancle to Event Going']);

        $this->assertSame([$otherUser->id], $this->eventUserIds($event->refresh(), 'going_users_id'));

        $this
            ->actingAs($user)
            ->get(route('event.interested', $event->id))
            ->assertOk()
            ->assertJson(['alertMessage' => 'Interested to Event']);

        $this->assertSame([$user->id], $this->eventUserIds($event->refresh(), 'interested_users_id'));

        $event->forceFill(['interested_users_id' => json_encode([$user->id, $otherUser->id])])->save();

        $this
            ->actingAs($user)
            ->get(route('event.notinterested', $event->id))
            ->assertOk()
            ->assertJson(['alertMessage' => 'Not Interested to Event']);

        $this->assertSame([$otherUser->id], $this->eventUserIds($event->refresh(), 'interested_users_id'));

        $event->forceFill([
            'going_users_id' => json_encode([$user->id, $otherUser->id]),
            'interested_users_id' => json_encode([$user->id, $otherUser->id]),
        ])->save();

        $this
            ->actingAs($user)
            ->get(route('event.cancel', $event->id))
            ->assertOk()
            ->assertJson(['alertMessage' => 'Event has been Canceled']);

        $event->refresh();
        $this->assertSame([$otherUser->id], $this->eventUserIds($event, 'going_users_id'));
        $this->assertSame([$otherUser->id], $this->eventUserIds($event, 'interested_users_id'));

        $this
            ->actingAs($user)
            ->get(route('event.share').'?event_id='.$event->id)
            ->assertOk()
            ->assertJson(['alertMessage' => 'Event Shared Successfully']);

        $this->assertSame(1, Share::query()
            ->where('share_user_id', $user->id)
            ->where('event_id', $event->id)
            ->count());

        $this
            ->actingAs($user)
            ->post(route('event.invition'), [
                'event_id' => $event->id,
                'invited_event_users_id' => [$invitedOne->id, $invitedTwo->id],
            ])
            ->assertOk()
            ->assertJson(['reload' => 1]);

        foreach ([$invitedOne, $invitedTwo] as $invitedUser) {
            $this->assertSame(1, Invite::query()
                ->where('invite_sender_id', $user->id)
                ->where('invite_reciver_id', $invitedUser->id)
                ->where('event_id', $event->id)
                ->count());
            $this->assertSame(1, Notification::query()
                ->where('sender_user_id', $user->id)
                ->where('reciver_user_id', $invitedUser->id)
                ->where('type', 'event')
                ->where('event_id', $event->id)
                ->count());
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function eventPayload(array $overrides = []): array
    {
        return array_merge([
            'eventname' => 'Feature event',
            'eventdate' => '2026-07-10',
            'eventtime' => '09:30',
            'eventlocation' => 'Vilnius',
            'description' => 'Feature event description',
            'privacy' => Visibility::Public->value,
        ], $overrides);
    }

    /**
     * @return list<int>
     */
    private function eventUserIds(Event $event, string $column): array
    {
        return array_map('intval', json_decode((string) $event->{$column}, true) ?: []);
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'user_role' => $role->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
        ]);
    }
}
