<?php

namespace Tests\Browser;

use App\Enums\ContentStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\AlbumImage;
use App\Models\Albums;
use App\Models\BlockUser;
use App\Models\Comments;
use App\Models\LiveStreaming;
use App\Models\MediaFile;
use App\Models\Posts;
use App\Models\PostShare;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MainControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-main-viewer@example.test',
        'dusk-main-friend@example.test',
        'dusk-main-author@example.test',
    ];

    private const EXISTING_POST = 'Dusk main controller existing post';

    private const CREATED_POST = 'Dusk main controller created post';

    private const EDITED_POST = 'Dusk main controller edited post';

    private const COMMENT = 'Dusk main controller browser comment';

    private const DELETED_COMMENT = 'Dusk main controller deleted comment';

    private const BLOCK_POST = 'Dusk main controller block target';

    private const LIVE_POST = 'Dusk main controller live post';

    private const MEDIA_FILE = 'main-controller-browser/deletable.jpg';

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

    public function test_main_controller_routes_work_through_browser_fetch_and_navigation(): void
    {
        $viewer = $this->activeUser('dusk-main-viewer@example.test', 'Dusk Main Viewer');
        $friend = $this->activeUser('dusk-main-friend@example.test', 'Dusk Main Friend', [
            'friends' => json_encode([$viewer->id]),
        ]);
        $author = $this->activeUser('dusk-main-author@example.test', 'Dusk Main Author');
        $existingPost = $this->createPost($viewer, self::EXISTING_POST);
        $blockPost = $this->createPost($author, self::BLOCK_POST);
        $livePost = $this->createPost($viewer, self::LIVE_POST, ['post_type' => 'live_streaming']);
        $albumPost = $this->createAlbumPost($viewer);
        $media = $this->createMediaFile($viewer, $existingPost);
        $this->createLiveStreamingFixtures($viewer, $livePost);

        $createdPostId = null;

        $this->browse(function (Browser $browser) use ($albumPost, $blockPost, &$createdPostId, $friend, $media, $viewer, $livePost) {
            $browser->loginAs($viewer)
                ->visit('/')
                ->assertSee('Dusk Main Viewer');

            $this->assertPageDidNotServerError($browser, 'timeline');

            $this->postForm($browser, '/create_post', [
                'privacy' => Visibility::Public->value,
                'description' => self::CREATED_POST,
            ], 'mainCreatePost', '"reload":1');

            $createdPost = Posts::query()
                ->where('user_id', $viewer->id)
                ->where('description', self::CREATED_POST)
                ->firstOrFail();
            $createdPostId = $createdPost->post_id;

            $browser->visit('/')
                ->assertSee('Dusk Main Viewer');

            $this->assertFetchResponseContains($browser, '/load_post_by_scrolling?offset=0', 'mainLoadScrolling', 'Dusk Main Viewer');
            $this->assertFetchResponseContains($browser, '/edit_post_form/'.$createdPost->post_id, 'mainEditForm', self::CREATED_POST);

            $this->postForm($browser, '/edit_post/'.$createdPost->post_id, [
                'privacy' => Visibility::Public->value,
                'description' => self::EDITED_POST,
            ], 'mainEditPost', '"reload":1');

            $this->assertSame(self::EDITED_POST, $createdPost->refresh()->description);

            $this->assertFetchResponseContains(
                $browser,
                '/search_friends_for_tagging?'.http_build_query(['search_value' => $friend->name]),
                'mainFriendSearch',
                $friend->name
            );
            $this->assertFetchOk($browser, '/preview_post?'.http_build_query(['post_id' => $createdPost->post_id]), 'mainPreview');

            $browser->visit('/view/single/post/'.$createdPost->post_id)
                ->assertSee('Dusk Main Viewer');

            $this->assertFetchOk($browser, '/custom/shared/post/view/'.$createdPost->post_id, 'mainSharedView');

            $this->assertFetchResponseContains(
                $browser,
                '/post_comment?'.http_build_query([
                    'description' => self::COMMENT,
                    'comment_id' => 0,
                    'parent_id' => 0,
                    'type' => 'post',
                    'post_id' => $createdPost->post_id,
                ]),
                'mainPostComment',
                self::COMMENT
            );

            $comment = Comments::query()
                ->where('user_id', $viewer->id)
                ->where('id_of_type', $createdPost->post_id)
                ->where('description', self::COMMENT)
                ->firstOrFail();

            $this->assertFetchResponseContains(
                $browser,
                '/load_post_comments?'.http_build_query([
                    'type' => 'post',
                    'post_id' => $createdPost->post_id,
                    'parent_id' => 0,
                    'total_loaded_comments' => 0,
                ]),
                'mainLoadComments',
                self::COMMENT
            );
            $this->assertFetchResponseContains(
                $browser,
                '/post_comment_count?'.http_build_query(['type' => 'post', 'post_id' => $createdPost->post_id]),
                'mainCommentCount',
                '1'
            );
            $this->assertFetchOk(
                $browser,
                '/my_comment_react?'.http_build_query([
                    'comment_id' => $comment->comment_id,
                    'request_type' => 'update',
                    'react' => 'haha',
                ]),
                'mainCommentReact'
            );

            $this->assertSame('haha', json_decode((string) $comment->refresh()->user_reacts, true)[$viewer->id]);

            $this->postForm($browser, '/my_react', [
                'type' => 'post',
                'post_id' => $createdPost->post_id,
                'request_type' => 'update',
                'react' => 'love',
                'response_type' => 'number',
            ], 'mainReact', '1');

            $this->assertSame('love', json_decode((string) $createdPost->refresh()->user_reacts, true)[$viewer->id]);

            $this->postForm($browser, '/post/report/save', [
                'post_id' => $createdPost->post_id,
                'report' => 'Dusk main controller report',
            ], 'mainReport', '"reload":1');

            $this->assertSame(1, Report::query()->where('user_id', $viewer->id)->where('post_id', $createdPost->post_id)->count());

            $this->postForm($browser, '/share/on/my/timeline', [
                'shared_post_id' => $createdPost->post_id,
                'postUrl' => 'https://sociopro.test/view/single/post/'.$createdPost->post_id,
            ], 'mainShareTimeline', '/profile');

            $this->postForm($browser, '/share/on/group', [
                'shared_post_id' => $createdPost->post_id,
                'group_id' => 77,
                'message' => 'Dusk main controller group share',
            ], 'mainShareGroup', 'Posted On Group Successfully');

            $this->assertSame(2, PostShare::query()->where('user_id', $viewer->id)->where('post_id', $createdPost->post_id)->count());

            $browser->visit('/save-post/'.$createdPost->post_id);
            $this->assertContains((string) $createdPost->post_id, array_map('strval', json_decode((string) $viewer->refresh()->save_post, true)));

            $browser->visit('/unsave-post/'.$createdPost->post_id);
            $this->assertNotContains((string) $createdPost->post_id, array_map('strval', json_decode((string) $viewer->refresh()->save_post, true)));

            $this->assertFetchResponseContains($browser, '/media/file/delete/'.$media->id, 'mainDeleteMedia', 'Image deleted successfully');
            $this->assertDatabaseMissing('media_files', ['id' => $media->id]);
            $this->assertFileDoesNotExist(public_path('storage/post/images/'.self::MEDIA_FILE));

            $browser->visit('/user/settings')
                ->assertSee('Razorpay');

            $this->postForm($browser, '/save/user/settings', [
                'raz_key_id' => 'dusk-razorpay-key',
                'theme_color' => '#246810',
                'stripe_live' => 'on',
            ], 'mainSaveSettings');

            $settings = json_decode((string) $viewer->refresh()->payment_settings, true);
            $this->assertSame('dusk-razorpay-key', $settings['raz_key_id']);
            $this->assertSame('#246810', $settings['theme_color']);
            $this->assertTrue($settings['stripe_live']);

            $this->postForm($browser, '/update-theme-color', [
                'themeColor' => 'dark',
            ], 'mainThemeColor', '"success":true');

            $this->assertFetchOk($browser, '/addons/manager', 'mainAddonsManager');
            $this->assertFetchOk($browser, '/album/details/page_show/'.$albumPost->post_id, 'mainAlbumDetails');
            $this->assertFetchResponseContains($browser, '/block_user/'.$blockPost->post_id, 'mainBlockModal', 'Dusk Main Author');

            $this->postForm($browser, '/block_user_post/'.$blockPost->post_id, [], 'mainBlockUser');

            $block = BlockUser::query()
                ->where('user_id', $viewer->id)
                ->where('block_user', $blockPost->user_id)
                ->firstOrFail();

            $browser->visit('/unblock_user/'.$block->id);
            $this->assertDatabaseMissing('block_users', ['id' => $block->id]);

            $this->assertFetchResponseContains($browser, '/streaming/live/'.$livePost->post_id, 'mainJitsiLive', 'jitsiMeet');
            $this->assertFetchResponseContains($browser, '/live/'.$livePost->post_id, 'mainZoomLive', 'MEETING_NUMBER');
            $this->assertFetchOk($browser, '/live-ended/'.$livePost->post_id, 'mainLiveEnded');
            $this->assertSame('yes', json_decode((string) $livePost->refresh()->description, true)['live_video_ended']);

            $deletableComment = Comments::query()->create([
                'parent_id' => 0,
                'user_id' => $viewer->id,
                'is_type' => 'post',
                'id_of_type' => $createdPost->post_id,
                'description' => self::DELETED_COMMENT,
                'user_reacts' => json_encode([]),
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            $this->assertFetchResponseContains($browser, '/comment/delete?comment_id='.$deletableComment->comment_id, 'mainDeleteComment', 'Comment Deleted Successfully');
            $this->assertDatabaseMissing('comments', ['comment_id' => $deletableComment->comment_id]);

            $this->assertFetchResponseContains($browser, '/delete/my/post?post_id='.$createdPost->post_id, 'mainDeletePost', 'Post Deleted Successfully');
        });

        $this->assertNotNull($createdPostId);
        $this->assertDatabaseMissing('posts', ['post_id' => $createdPostId]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeUser(string $email, string $name, array $overrides = []): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill($overrides + [
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
    private function createPost(User $user, string $description, array $overrides = []): Posts
    {
        return Posts::query()->create($overrides + [
            'user_id' => $user->id,
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => 'general',
            'privacy' => Visibility::Public->value,
            'tagged_user_ids' => json_encode([]),
            'location' => '',
            'description' => $description,
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
            'user_reacts' => json_encode([]),
            'shared_user' => json_encode([]),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function createMediaFile(User $user, Posts $post): MediaFile
    {
        File::ensureDirectoryExists(public_path('storage/post/images/'.dirname(self::MEDIA_FILE)));
        File::put(public_path('storage/post/images/'.self::MEDIA_FILE), 'dusk image');

        return MediaFile::query()->create([
            'user_id' => $user->id,
            'post_id' => $post->post_id,
            'file_name' => self::MEDIA_FILE,
            'file_type' => 'image',
            'privacy' => Visibility::Public->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function createAlbumPost(User $user): Posts
    {
        $album = Albums::query()->create([
            'user_id' => $user->id,
            'title' => 'Dusk Main Album',
            'sub_title' => 'Dusk Main Album Subtitle',
            'thumbnail' => null,
            'privacy' => Visibility::Public->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $albumImage = new AlbumImage;
        $albumImage->forceFill([
            'album_id' => $album->id,
            'user_id' => $user->id,
            'image' => 'main-controller-browser/album.jpg',
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $albumImage->save();

        $post = $this->createPost($user, 'Dusk main controller album post', [
            'album_image_id' => $albumImage->id,
        ]);

        File::ensureDirectoryExists(public_path('storage/post/images/main-controller-browser'));
        File::put(public_path('storage/post/images/main-controller-browser/album.jpg'), 'dusk album image');

        MediaFile::query()->create([
            'user_id' => $user->id,
            'post_id' => $post->post_id,
            'album_id' => $album->id,
            'album_image_id' => $albumImage->id,
            'file_name' => 'main-controller-browser/album.jpg',
            'file_type' => 'image',
            'privacy' => Visibility::Public->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        return $post;
    }

    private function createLiveStreamingFixtures(User $viewer, Posts $livePost): void
    {
        LiveStreaming::query()->create([
            'publisher' => 'post',
            'publisher_id' => $livePost->post_id,
            'user_id' => $viewer->id,
            'details' => json_encode([
                'id' => 987654321,
                'password' => 'dusk-live-pass',
            ]),
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        LiveStreaming::query()->create([
            'publisher' => 'post',
            'publisher_id' => $viewer->id,
            'user_id' => $viewer->id,
            'details' => json_encode([]),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function seedRuntimeSettings(): void
    {
        $this->upsertSetting('system_language', 'english');
        $this->upsertSetting('system_name', 'Dusk Sociopro');
        $this->upsertSetting('zitsi_configuration', json_encode([
            'account_email' => 'dusk-jitsi@example.test',
            'jitsi_app_id' => 'dusk-jitsi-app',
            'jitsi_jwt' => 'dusk-jitsi-jwt',
        ]));
        $this->upsertSetting('zoom_configuration', json_encode([
            'api_key' => 'dusk-zoom-key',
            'api_secret' => 'dusk-zoom-secret-with-enough-length',
        ]));
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

        $browser->waitUntil("window[{$encodedWindowKey}] !== null && window[{$encodedWindowKey}].status === 200", 5);

        if ($expectedText !== null) {
            $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);
            $browser->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
        }
    }

    private function assertFetchResponseContains(Browser $browser, string $url, string $windowKey, string $expectedText): void
    {
        $this->assertFetchOk($browser, $url, $windowKey);

        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
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

    private function assertPageDidNotServerError(Browser $browser, string $context): void
    {
        foreach ([
            'SQLSTATE[',
            'no such table',
            'Illuminate\\Database\\QueryException',
            'Server Error',
            'Internal Server Error',
        ] as $needle) {
            $browser->assertDontSee($needle);
        }

        $this->assertTrue(true, "No server error was rendered for [{$context}].");
    }

    private function deleteFixtures(): void
    {
        File::deleteDirectory(public_path('storage/post/images/main-controller-browser'));

        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $postIds = Posts::query()
            ->whereIn('user_id', $userIds)
            ->pluck('post_id');

        Comments::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('id_of_type', $postIds)
            ->delete();
        MediaFile::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('post_id', $postIds)
            ->delete();
        Report::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('post_id', $postIds)
            ->delete();
        PostShare::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('post_id', $postIds)
            ->delete();
        LiveStreaming::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('publisher_id', $postIds)
            ->orWhereIn('publisher_id', $userIds)
            ->delete();
        BlockUser::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('block_user', $userIds)
            ->delete();
        Posts::query()
            ->whereIn('post_id', $postIds)
            ->delete();
        AlbumImage::query()
            ->whereIn('user_id', $userIds)
            ->delete();
        Albums::query()
            ->whereIn('user_id', $userIds)
            ->delete();
        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
