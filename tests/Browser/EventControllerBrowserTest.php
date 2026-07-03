<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Event;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\Posts;
use App\Models\Share;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EventControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-event-viewer@example.test',
        'dusk-event-owner@example.test',
        'dusk-event-invited@example.test',
        'dusk-event-second-invited@example.test',
    ];

    private const PUBLIC_TITLE = 'Dusk Event Public Listing';

    private const USER_TITLE = 'Dusk Event User Listing';

    private const DELETE_TITLE = 'Dusk Event Delete Target';

    private const CREATED_TITLE = 'Dusk Event Browser Created';

    private const UPDATED_TITLE = 'Dusk Event Browser Updated';

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFixtures();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        parent::tearDown();
    }

    public function test_event_pages_and_ajax_actions_work_in_browser(): void
    {
        $viewer = $this->activeUser('dusk-event-viewer@example.test', 'Dusk Event Viewer');
        $owner = $this->activeUser('dusk-event-owner@example.test', 'Dusk Event Owner');
        $invited = $this->activeUser('dusk-event-invited@example.test', 'Dusk Event Invited User');
        $secondInvited = $this->activeUser('dusk-event-second-invited@example.test', 'Dusk Event Second Invited');

        $publicEvent = $this->createEvent($owner, self::PUBLIC_TITLE, 'Dusk public event description');
        $userEvent = $this->createEvent($viewer, self::USER_TITLE, 'Dusk user event description');
        $deleteEvent = $this->createEvent($viewer, self::DELETE_TITLE, 'Dusk delete event description');

        $this->browse(function (Browser $browser) use ($deleteEvent, $invited, $publicEvent, $secondInvited, $viewer) {
            $browser->loginAs($viewer)
                ->visit('/events')
                ->assertSee('Events')
                ->assertSee(self::PUBLIC_TITLE)
                ->visit('/user/event')
                ->assertSee('My Event')
                ->assertSee(self::USER_TITLE)
                ->visit('/event/view/'.$publicEvent->id)
                ->assertSee(self::PUBLIC_TITLE)
                ->assertSee('Dusk public event description')
                ->assertSee('Details')
                ->visit('/load_event_by_scrolling?offset=0')
                ->assertSourceHas(self::PUBLIC_TITLE)
                ->visit('/search_user_for_event_inviting?id='.$publicEvent->id.'&search_value=Dusk%20Event%20Invited')
                ->assertSourceHas('Dusk Event Invited User');

            $this->postForm($browser, '/event/store', [
                'eventname' => self::CREATED_TITLE,
                'eventdate' => '2026-07-11',
                'eventtime' => '10:15',
                'eventlocation' => 'Vilnius Browser Hall',
                'description' => 'Dusk created event description',
                'privacy' => Visibility::Public->value,
            ], 'eventStoreResponse', 'reload');

            $createdEvent = Event::query()->where('title', self::CREATED_TITLE)->firstOrFail();

            $this->postForm($browser, '/event/update/'.$createdEvent->id, [
                'eventname' => self::UPDATED_TITLE,
                'eventdate' => '2026-07-12',
                'eventtime' => '11:30',
                'eventlocation' => 'Vilnius Updated Hall',
                'description' => 'Dusk updated event description',
                'privacy' => Visibility::Public->value,
            ], 'eventUpdateResponse', 'reload');

            $this->assertFetchResponseContains($browser, '/event/going/'.$publicEvent->id, 'eventGoingResponse', 'Going to Event');
            $this->assertSame([$viewer->id], $this->eventUserIds($publicEvent->refresh(), 'going_users_id'));

            $publicEvent->forceFill(['going_users_id' => json_encode([$viewer->id, $invited->id])])->save();
            $this->assertFetchResponseContains($browser, '/event/notgoing/'.$publicEvent->id, 'eventNotGoingResponse', 'Cancle to Event Going');
            $this->assertSame([$invited->id], $this->eventUserIds($publicEvent->refresh(), 'going_users_id'));

            $this->assertFetchResponseContains($browser, '/event/interested/'.$publicEvent->id, 'eventInterestedResponse', 'Interested to Event');
            $this->assertSame([$viewer->id], $this->eventUserIds($publicEvent->refresh(), 'interested_users_id'));

            $publicEvent->forceFill(['interested_users_id' => json_encode([$viewer->id, $invited->id])])->save();
            $this->assertFetchResponseContains($browser, '/event/notinterested/'.$publicEvent->id, 'eventNotInterestedResponse', 'Not Interested to Event');
            $this->assertSame([$invited->id], $this->eventUserIds($publicEvent->refresh(), 'interested_users_id'));

            $publicEvent->forceFill([
                'going_users_id' => json_encode([$viewer->id, $invited->id]),
                'interested_users_id' => json_encode([$viewer->id, $invited->id]),
            ])->save();
            $this->assertFetchResponseContains($browser, '/event/cancel/'.$publicEvent->id, 'eventCancelResponse', 'Event has been Canceled');
            $this->assertSame([$invited->id], $this->eventUserIds($publicEvent->refresh(), 'going_users_id'));
            $this->assertSame([$invited->id], $this->eventUserIds($publicEvent, 'interested_users_id'));

            $this->assertFetchResponseContains(
                $browser,
                '/event/invite/'.$invited->id.'/'.$viewer->id.'/'.$publicEvent->id,
                'eventInviteResponse',
                'reload'
            );

            $this->postForm($browser, '/event/invites/sent', [
                'event_id' => $publicEvent->id,
                'invited_event_users_id' => [$invited->id, $secondInvited->id],
            ], 'eventBulkInviteResponse', 'reload');

            $this->assertFetchResponseContains(
                $browser,
                '/share/event?event_id='.$publicEvent->id,
                'eventShareResponse',
                'Event Shared Successfully'
            );

            $this->assertFetchResponseContains(
                $browser,
                '/event/delete?event_id='.$deleteEvent->id,
                'eventDeleteResponse',
                'Event Deleted Successfully'
            );
        });

        $createdEvent = Event::query()->where('title', self::UPDATED_TITLE)->firstOrFail();
        $this->assertSame($viewer->id, (int) $createdEvent->user_id);
        $this->assertSame('Vilnius Updated Hall', $createdEvent->location);
        $this->assertDatabaseMissing('events', ['id' => $deleteEvent->id]);

        foreach ([$invited, $secondInvited] as $invitedUser) {
            $this->assertGreaterThanOrEqual(1, Invite::query()
                ->where('invite_sender_id', $viewer->id)
                ->where('invite_reciver_id', $invitedUser->id)
                ->where('event_id', $publicEvent->id)
                ->count());
            $this->assertGreaterThanOrEqual(1, Notification::query()
                ->where('sender_user_id', $viewer->id)
                ->where('reciver_user_id', $invitedUser->id)
                ->where('type', 'event')
                ->where('event_id', $publicEvent->id)
                ->count());
        }

        $this->assertSame(1, Share::query()
            ->where('share_user_id', $viewer->id)
            ->where('event_id', $publicEvent->id)
            ->count());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postForm(Browser $browser, string $url, array $payload, string $windowKey, string $expectedText): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;
            const payload = {$encodedPayload};
            const params = new URLSearchParams();

            Object.entries(payload).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((item) => params.append(key + '[]', item));
                    return;
                }

                params.append(key, value ?? '');
            });

            const token = document.querySelector('meta[name="csrf_token"], meta[name="csrf-token"]')?.content;

            fetch({$encodedUrl}, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': token,
                },
                body: params,
            }).then(async (response) => {
                window[{$encodedWindowKey}] = {
                    status: response.status,
                    text: await response.text(),
                };
            }).catch((error) => {
                window[{$encodedWindowKey}] = {
                    status: -1,
                    text: String(error),
                };
            });
        JS);

        $browser->waitUntil("window[{$encodedWindowKey}] !== null && window[{$encodedWindowKey}].status === 200", 5)
            ->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
    }

    private function assertFetchResponseContains(Browser $browser, string $url, string $windowKey, string $expectedText): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;

            fetch({$encodedUrl}, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                },
            }).then(async (response) => {
                window[{$encodedWindowKey}] = {
                    status: response.status,
                    text: await response.text(),
                };
            }).catch((error) => {
                window[{$encodedWindowKey}] = {
                    status: -1,
                    text: String(error),
                };
            });
        JS);

        $browser->waitUntil("window[{$encodedWindowKey}] !== null && window[{$encodedWindowKey}].status === 200", 5)
            ->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
    }

    private function activeUser(string $email, string $name): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2u.heWG/igi',
            'email_verified_at' => now(),
            'username' => str_replace(['@', '.'], '-', $email),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'profile_status' => 'unlock',
        ]);
        $user->save();

        return $user;
    }

    private function createEvent(User $owner, string $title, string $description): Event
    {
        return Event::factory()->forOwner($owner)->create([
            'title' => $title,
            'description' => $description,
            'event_date' => '2026-07-20',
            'event_time' => '14:00',
            'location' => 'Vilnius Dusk Venue',
            'privacy' => Visibility::Public->value,
        ]);
    }

    /**
     * @return list<int>
     */
    private function eventUserIds(Event $event, string $column): array
    {
        return array_map('intval', json_decode((string) $event->{$column}, true) ?: []);
    }

    private function deleteFixtures(): void
    {
        $eventIds = Event::query()
            ->where('title', 'like', 'Dusk Event%')
            ->pluck('id');

        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        if ($eventIds->isNotEmpty()) {
            Invite::query()->whereIn('event_id', $eventIds)->delete();
            Notification::query()->whereIn('event_id', $eventIds)->delete();
            Share::query()->whereIn('event_id', $eventIds)->delete();
            Posts::query()
                ->where('publisher', 'event')
                ->whereIn('publisher_id', $eventIds)
                ->delete();
            Event::query()->whereIn('id', $eventIds)->delete();
        }

        Posts::query()
            ->where('description', 'like', 'Dusk % event description')
            ->orWhere('description', 'like', 'Dusk Event%')
            ->delete();

        if ($userIds->isEmpty()) {
            return;
        }

        Invite::query()
            ->whereIn('invite_sender_id', $userIds)
            ->orWhereIn('invite_reciver_id', $userIds)
            ->delete();
        Notification::query()
            ->whereIn('sender_user_id', $userIds)
            ->orWhereIn('reciver_user_id', $userIds)
            ->delete();
        Share::query()
            ->whereIn('share_user_id', $userIds)
            ->delete();
        Event::query()
            ->whereIn('user_id', $userIds)
            ->delete();
        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
