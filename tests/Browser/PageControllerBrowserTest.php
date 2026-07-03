<?php

namespace Tests\Browser;

use App\Enums\MediaFileType;
use App\Enums\MembershipRole;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageLike;
use App\Models\Posts;
use App\Models\Setting;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PageControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-page-viewer@example.test',
        'dusk-page-owner@example.test',
    ];

    private const CATEGORY_NAME = 'Dusk Page Category';

    private const OWNED_TITLE = 'Dusk Page Owned';

    private const SUGGESTED_TITLE = 'Dusk Page Suggested';

    private const LIKED_TITLE = 'Dusk Page Liked';

    private const PROFILE_TITLE = 'Dusk Page Profile';

    private const CREATED_TITLE = 'Dusk Page Created';

    private const UPDATED_TITLE = 'Dusk Page Updated';

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

    public function test_page_controller_routes_work_through_browser_navigation_and_fetch(): void
    {
        $viewer = $this->activeUser('dusk-page-viewer@example.test', 'Dusk Page Viewer');
        $owner = $this->activeUser('dusk-page-owner@example.test', 'Dusk Page Owner');
        $category = $this->category();
        $ownedPage = $this->createPage($viewer, self::OWNED_TITLE, $category);
        $suggestedPage = $this->createPage($owner, self::SUGGESTED_TITLE, $category);
        $likedPage = $this->createPage($owner, self::LIKED_TITLE, $category);
        $profilePage = $this->createPage($owner, self::PROFILE_TITLE, $category);
        PageLike::factory()->forUser($viewer)->forPage($likedPage)->create();
        $profilePost = $this->createPagePost($owner, $profilePage);
        $this->createMedia($owner, $profilePage, $profilePost, 'dusk-page-photo.jpg', MediaFileType::Image);
        $this->createMedia($owner, $profilePage, $profilePost, 'dusk-page-video.mp4', MediaFileType::Video);
        $this->createMedia($viewer, null, $profilePost, 'dusk-page-user-video.mp4', MediaFileType::Video);

        $this->browse(function (Browser $browser) use ($category, $likedPage, $ownedPage, $profilePage, $suggestedPage, $viewer) {
            $browser->loginAs($viewer)
                ->visit('/pages')
                ->assertSee('Pages')
                ->assertSee(self::OWNED_TITLE)
                ->assertSourceHas('Dusk Page Suggest')
                ->assertSourceHas(self::LIKED_TITLE)
                ->visit('/page/view/'.$profilePage->id)
                ->assertSee(self::PROFILE_TITLE)
                ->visit('/page/photo/view/'.$profilePage->id)
                ->assertSee('Photos')
                ->visit('/page/videos/'.$profilePage->id)
                ->assertSee('Your videos');

            $this->assertFetchResponseContains($browser, '/load_page_by_scrolling?offset=0', 'pageScrollResponse', self::OWNED_TITLE);
            $this->assertFetchResponseContains($browser, '/page/load_videos?offset=0', 'pageLoadVideoResponse', 'play_v_icon');

            $this->postForm($browser, '/page/store', [
                'name' => self::CREATED_TITLE,
                'category' => $category->id,
                'description' => 'Dusk created page description.',
            ], 'pageStoreResponse', '"reload":1');

            $createdPage = Page::query()
                ->where('user_id', $viewer->id)
                ->where('title', self::CREATED_TITLE)
                ->firstOrFail();

            $this->postForm($browser, '/update/page/'.$createdPage->id, [
                'name' => self::UPDATED_TITLE,
                'category' => $category->id,
                'description' => 'Dusk updated page description.',
            ], 'pageUpdateResponse', '"reload":1');

            $this->postForm($browser, '/update/coverphoto/page/'.$createdPage->id, [], 'pageCoverResponse', '"reload":1');
            $this->postForm($browser, '/update/info/page/'.$createdPage->id, [
                'job' => 'Dusk page job',
                'lifestyle' => 'Dusk page lifestyle',
                'location' => 'Dusk page location',
            ], 'pageInfoResponse');

            $this->assertFetchResponseContains($browser, '/page/like/'.$suggestedPage->id, 'pageLikeResponse', '"reload":1');
            $this->assertSame(1, PageLike::query()
                ->where('user_id', $viewer->id)
                ->where('page_id', $suggestedPage->id)
                ->where('role', MembershipRole::General->value)
                ->count());

            $this->assertFetchResponseContains($browser, '/page/dislike/'.$likedPage->id, 'pageDislikeResponse', '"reload":1');
            $this->assertDatabaseMissing('page_likes', [
                'user_id' => $viewer->id,
                'page_id' => $likedPage->id,
            ]);

            $this->assertDatabaseHas('pages', [
                'id' => $ownedPage->id,
                'user_id' => $viewer->id,
            ]);
        });

        $createdPage = Page::query()
            ->where('user_id', $viewer->id)
            ->where('title', self::UPDATED_TITLE)
            ->firstOrFail();

        $this->assertSame('Dusk updated page description.', $createdPage->description);
        $this->assertSame('Dusk page job', $createdPage->job);
        $this->assertSame('Dusk page lifestyle', $createdPage->lifestyle);
        $this->assertSame('Dusk page location', $createdPage->location);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postForm(Browser $browser, string $url, array $payload, string $windowKey, ?string $expectedText = null): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);

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
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('SQLSTATE[')", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('Internal Server Error')", 5);

        if ($expectedText !== null) {
            $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);
            $browser->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
        }
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
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('SQLSTATE[')", 5)
            ->waitUntil("!window[{$encodedWindowKey}].text.includes('Internal Server Error')", 5)
            ->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
    }

    private function activeUser(string $email, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
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
    }

    private function category(): PageCategory
    {
        return PageCategory::factory()->create([
            'name' => self::CATEGORY_NAME,
        ]);
    }

    private function createPage(User $owner, string $title, PageCategory $category): Page
    {
        return Page::factory()
            ->forOwner($owner)
            ->forCategory($category)
            ->create([
                'title' => $title,
                'description' => $title.' description.',
                'status' => '1',
            ]);
    }

    private function createPagePost(User $owner, Page $page): Posts
    {
        return Posts::factory()->forOwner($owner)->create([
            'publisher' => 'page',
            'publisher_id' => $page->id,
            'description' => 'Dusk page post.',
        ]);
    }

    private function createMedia(User $owner, ?Page $page, Posts $post, string $fileName, MediaFileType $type): MediaFile
    {
        $factory = $type === MediaFileType::Video
            ? MediaFile::factory()->video()
            : MediaFile::factory()->image();

        return $factory->create([
            'user_id' => $owner->id,
            'post_id' => $post->post_id,
            'page_id' => $page?->id,
            'file_name' => $fileName,
            'file_type' => $type->value,
            'privacy' => 'public',
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function seedRuntimeSettings(): void
    {
        $this->upsertSetting('system_language', 'english');
        $this->upsertSetting('system_name', 'Dusk Sociopro');
        $this->upsertSetting('amazon_s3', json_encode(['active' => 0]));
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

        $pageIds = Page::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('title', [
                self::OWNED_TITLE,
                self::SUGGESTED_TITLE,
                self::LIKED_TITLE,
                self::PROFILE_TITLE,
                self::CREATED_TITLE,
                self::UPDATED_TITLE,
            ])
            ->pluck('id');

        $postIds = Posts::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('publisher_id', $pageIds)
            ->pluck('post_id');

        PageLike::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('page_id', $pageIds)
            ->delete();
        MediaFile::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('page_id', $pageIds)
            ->orWhereIn('post_id', $postIds)
            ->delete();
        Posts::query()
            ->whereIn('post_id', $postIds)
            ->delete();
        Page::query()
            ->whereIn('id', $pageIds)
            ->delete();
        User::query()
            ->whereIn('id', $userIds)
            ->delete();
        PageCategory::query()
            ->where('name', self::CATEGORY_NAME)
            ->delete();
    }
}
