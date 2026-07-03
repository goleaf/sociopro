<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\PageCategory;
use App\Models\Setting;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ModalControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-modal-owner@example.test',
        'dusk-modal-going@example.test',
        'dusk-modal-interested@example.test',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFixtures();
        $this->seedRuntimeSettings();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        parent::tearDown();
    }

    public function test_modal_controller_routes_load_modal_partials_through_browser_and_fetch(): void
    {
        $owner = $this->activeUser('dusk-modal-owner@example.test', 'Dusk Modal Owner');
        $goingUser = $this->activeUser('dusk-modal-going@example.test', 'Dusk Modal Going');
        $interestedUser = $this->activeUser('dusk-modal-interested@example.test', 'Dusk Modal Interested');

        $this->pageCategory('Dusk Modal Alpha');
        $this->pageCategory('Dusk Modal Zeta');

        $event = new Event;
        $event->forceFill([
            'user_id' => $owner->id,
            'group_id' => null,
            'title' => 'Dusk Modal Event',
            'description' => 'Dusk modal event description.',
            'event_date' => now()->addWeek()->toDateString(),
            'event_time' => '10:00',
            'location' => 'Vilnius',
            'going_users_id' => json_encode([$goingUser->id]),
            'interested_users_id' => json_encode([$interestedUser->id]),
            'banner' => null,
            'privacy' => 'public',
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
        ]);
        $event->save();

        $this->browse(function (Browser $browser) use ($event, $goingUser, $interestedUser, $owner) {
            $browser->loginAs($owner)
                ->visit('/load_modal_content/frontend.pages.create_page')
                ->assertSee('Create Page')
                ->assertSee('Dusk Modal Alpha')
                ->assertSee('Dusk Modal Zeta');

            $this->assertFetchResponseContains(
                $browser,
                '/load_modal_content/frontend.main_content.create_report?post_id=987',
                'modalReportResponse',
                'value="987" name="post_id"'
            );

            $this->assertFetchResponseContains(
                $browser,
                '/load_modal_content/frontend.events.view-all?event_id='.$event->id,
                'modalEventGoingResponse',
                $goingUser->name
            );

            $this->assertFetchResponseContains(
                $browser,
                '/load_modal_content/frontend.events.view-all?event_id='.$event->id,
                'modalEventInterestedResponse',
                $interestedUser->name
            );
        });
    }

    private function activeUser(string $email, string $name): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => str_replace(['@', '.'], '-', $email),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'payment_settings' => '',
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'profile_status' => 'unlock',
        ]);
        $user->save();

        return $user;
    }

    private function assertFetchResponseContains(Browser $browser, string $url, string $windowKey, string $expectedText): void
    {
        $this->assertFetchOk($browser, $url, $windowKey);

        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
    }

    private function pageCategory(string $name): PageCategory
    {
        $category = PageCategory::query()->where('name', $name)->first() ?? new PageCategory;
        $category->forceFill(['name' => $name]);
        $category->save();

        return $category;
    }

    private function assertFetchOk(Browser $browser, string $url, string $windowKey): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);

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
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('SQLSTATE[')", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('no such table')", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('Internal Server Error')", 5);
    }

    private function seedRuntimeSettings(): void
    {
        $this->upsertSetting('system_language', 'english');
        $this->upsertSetting('system_name', 'Dusk Sociopro');
        $this->upsertSetting('system_fav_icon', '');
        $this->upsertSetting('theme_color', 'default');
    }

    private function upsertSetting(string $type, string $description): void
    {
        $setting = Setting::query()->where('type', $type)->first() ?? new Setting;
        $setting->forceFill([
            'type' => $type,
            'description' => $description,
            'updated_at' => now(),
        ]);

        if (! $setting->exists) {
            $setting->created_at = now();
        }

        $setting->save();
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        Event::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('title', 'like', 'Dusk Modal %')
            ->delete();

        User::query()
            ->whereIn('id', $userIds)
            ->delete();

        PageCategory::query()
            ->where('name', 'like', 'Dusk Modal %')
            ->delete();
    }
}
