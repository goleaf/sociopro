<?php

namespace Tests\Browser;

use App\Enums\ContentStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Posts;
use App\Models\Setting;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MemoriesControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-memories-viewer@example.test',
        'dusk-memories-other@example.test',
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

    public function test_memories_pages_work_through_browser_navigation_and_fetch(): void
    {
        $viewer = $this->activeUser('dusk-memories-viewer@example.test', 'Dusk Memories Viewer');
        $other = $this->activeUser('dusk-memories-other@example.test', 'Dusk Memories Other');

        $firstMemory = $this->memoryPost($viewer, 'Dusk memory first visible');
        $secondMemory = $this->memoryPost($viewer, 'Dusk memory second visible');
        $thirdMemory = $this->memoryPost($viewer, 'Dusk memory third visible');
        $fourthMemory = $this->memoryPost($viewer, 'Dusk memory fourth visible');
        $otherMemory = $this->memoryPost($other, 'Dusk memory other hidden');
        $currentYearMemory = $this->memoryPost($viewer, 'Dusk memory current year hidden', [
            'posted_on' => now()->toDateTimeString(),
        ]);

        $this->browse(function (Browser $browser) use (
            $currentYearMemory,
            $firstMemory,
            $fourthMemory,
            $otherMemory,
            $secondMemory,
            $thirdMemory,
            $viewer
        ) {
            $browser->loginAs($viewer)
                ->visit('/memories')
                ->assertSee('Memories')
                ->assertSourceHas($this->postMarker($fourthMemory))
                ->assertSourceHas($this->postMarker($thirdMemory))
                ->assertSourceMissing($this->postMarker($otherMemory))
                ->assertSourceMissing($this->postMarker($currentYearMemory));

            $this->assertFetchResponseContains(
                $browser,
                '/load/memories?offset=1',
                'memoriesLoadResponse',
                $this->postMarker($thirdMemory)
            );
            $this->assertFetchResponseContains(
                $browser,
                '/load/memories?offset=1',
                'memoriesLoadSecondResponse',
                $this->postMarker($secondMemory)
            );
            $this->assertFetchResponseContains(
                $browser,
                '/load/memories?offset=1',
                'memoriesLoadFirstResponse',
                $this->postMarker($firstMemory)
            );
            $this->assertFetchResponseMissing(
                $browser,
                '/load/memories?offset=1',
                'memoriesLoadMissingResponse',
                $this->postMarker($fourthMemory)
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function memoryPost(User $owner, string $description, array $overrides = []): Posts
    {
        $post = new Posts;
        $post->forceFill([
            'user_id' => $owner->id,
            'publisher' => 'post',
            'publisher_id' => $owner->id,
            'post_type' => 'general',
            'privacy' => Visibility::Public->value,
            'tagged_user_ids' => json_encode([]),
            'location' => '',
            'description' => $description,
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
            'user_reacts' => json_encode([]),
            'shared_user' => json_encode([]),
            'posted_on' => now()->subYear()->toDateTimeString(),
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
            ...$overrides,
        ]);
        $post->save();

        return $post;
    }

    private function assertFetchResponseContains(Browser $browser, string $url, string $windowKey, string $expectedText): void
    {
        $this->assertFetchOk($browser, $url, $windowKey);

        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
    }

    private function assertFetchResponseMissing(Browser $browser, string $url, string $windowKey, string $unexpectedText): void
    {
        $this->assertFetchOk($browser, $url, $windowKey);

        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedUnexpectedText = json_encode($unexpectedText, JSON_THROW_ON_ERROR);

        $browser->waitUntil("!window[{$encodedWindowKey}].text.includes({$encodedUnexpectedText})", 5);
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

    private function postMarker(Posts $post): string
    {
        return 'copy_post_'.$post->post_id;
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        Posts::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('description', 'like', 'Dusk memory %')
            ->delete();

        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
