<?php

namespace Tests\Browser;

use App\Enums\ContentStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\VideoCategory;
use App\Enums\Visibility;
use App\Models\Posts;
use App\Models\SaveForLater;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\File;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class VideoControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-video-viewer@example.test',
        'dusk-video-owner@example.test',
    ];

    private const VIDEO_TITLE = 'Dusk Video Public';

    private const SHORT_TITLE = 'Dusk Short Public';

    private const SAVED_TITLE = 'Dusk Saved Video';

    private const DELETE_TITLE = 'Dusk Delete Video';

    private const CREATED_TITLE = 'Dusk Created Video';

    private const DELETE_FILE = 'dusk-delete-video.mp4';

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

    public function test_video_controller_routes_work_through_browser_navigation_and_fetch(): void
    {
        $viewer = $this->activeUser('dusk-video-viewer@example.test', 'Dusk Video Viewer');
        $owner = $this->activeUser('dusk-video-owner@example.test', 'Dusk Video Owner');
        $video = $this->createVideo($owner, self::VIDEO_TITLE);
        $short = $this->createVideo($owner, self::SHORT_TITLE, VideoCategory::Shorts);
        $savedVideo = $this->createVideo($owner, self::SAVED_TITLE);
        $deleteVideo = $this->createVideo($viewer, self::DELETE_TITLE, VideoCategory::Video, self::DELETE_FILE);
        $this->putLegacyVideoDeleteFiles(self::DELETE_FILE);

        $this->browse(function (Browser $browser) use ($deleteVideo, $savedVideo, $short, $viewer, $video) {
            $browser->loginAs($viewer)
                ->visit('/videos')
                ->assertPathIs('/videos')
                ->assertSourceHas('id="video-'.$video->id.'"');

            $this->assertFetchResponseContains(
                $browser,
                '/load_videos_by_scrolling?offset=0',
                'duskVideoLoad',
                'id="video-'.$video->id.'"'
            );

            $browser->visit('/shorts')
                ->assertPathIs('/shorts')
                ->assertSourceHas(self::SHORT_TITLE)
                ->assertSourceHas('id="shorts-'.$short->id.'"');

            $this->assertFetchResponseContains(
                $browser,
                '/load_shorts_by_scrolling?offset=0',
                'duskShortLoad',
                self::SHORT_TITLE
            );

            $browser->visit('/video/details/info/'.$video->id)
                ->assertPathIs('/video/details/info/'.$video->id)
                ->assertSourceHas(self::VIDEO_TITLE);

            $this->assertFetchResponseContains($browser, '/save/video/short/'.$savedVideo->id, 'duskVideoSave', '"reload":1');
            $this->assertDatabaseHas('saveforlaters', [
                'user_id' => $viewer->id,
                'video_id' => $savedVideo->id,
            ]);

            $browser->visit('/saved/video/view')
                ->assertPathIs('/saved/video/view')
                ->assertSourceHas(self::SAVED_TITLE);

            $this->assertFetchResponseContains($browser, '/unsave/video/short/'.$savedVideo->id, 'duskVideoUnsave', '"reload":1');
            $this->assertDatabaseMissing('saveforlaters', [
                'user_id' => $viewer->id,
                'video_id' => $savedVideo->id,
            ]);

            $this->postVideoUpload($browser, 'duskVideoStore');

            $createdVideo = Video::query()
                ->where('user_id', $viewer->id)
                ->where('title', self::CREATED_TITLE)
                ->firstOrFail();

            $this->assertDatabaseHas('posts', [
                'user_id' => $viewer->id,
                'publisher' => 'video_and_shorts',
                'publisher_id' => $createdVideo->id,
                'post_type' => VideoCategory::Video->value,
                'privacy' => Visibility::Public->value,
                'description' => self::CREATED_TITLE,
                'status' => ContentStatus::Active->value,
            ]);

            $this->assertFetchResponseContains(
                $browser,
                '/video/delete?video_id='.$deleteVideo->id,
                'duskVideoDelete',
                'Video Deleted Successfully'
            );
        });

        $video->refresh();
        $this->assertSame([$viewer->id], json_decode($video->view, true));

        $this->assertDatabaseMissing('videos', ['id' => $deleteVideo->id]);
        $this->assertFileDoesNotExist(public_path('storage/video/coverphoto/'.self::DELETE_FILE));
        $this->assertFileDoesNotExist(public_path('storage/video/thumbnail/'.self::DELETE_FILE));
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

    private function createVideo(
        User $owner,
        string $title,
        VideoCategory $category = VideoCategory::Video,
        ?string $file = null,
    ): Video {
        $video = new Video;
        $video->forceFill([
            'user_id' => $owner->id,
            'title' => $title,
            'category' => $category->value,
            'privacy' => Visibility::Public->value,
            'file' => $file ?? str($title)->slug()->append('.mp4')->toString(),
            'view' => json_encode([]),
            'mobile_app_image' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $video->save();

        $post = new Posts;
        $post->forceFill([
            'user_id' => $owner->id,
            'publisher' => 'video_and_shorts',
            'publisher_id' => $video->id,
            'post_type' => $category->value,
            'privacy' => Visibility::Public->value,
            'description' => $title,
            'tagged_user_ids' => json_encode([]),
            'activity_id' => 0,
            'user_reacts' => json_encode([]),
            'status' => ContentStatus::Active->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $post->save();

        return $video;
    }

    private function postVideoUpload(Browser $browser, string $windowKey): void
    {
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedTitle = json_encode(self::CREATED_TITLE, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode('"reload":1', JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;

            const formData = new FormData();
            formData.append('title', {$encodedTitle});
            formData.append('privacy', 'public');
            formData.append('category', 'video');

            const mp4Binary = atob('AAAAIGZ0eXBpc29tAAACAGlzb21pc28ybXA0MQAAAAhmcmVlAA==');
            const mp4Bytes = new Uint8Array(mp4Binary.length);
            for (let index = 0; index < mp4Binary.length; index += 1) {
                mp4Bytes[index] = mp4Binary.charCodeAt(index);
            }
            formData.append('video', new File([mp4Bytes], 'dusk-created-video.mp4', { type: 'video/mp4' }));

            const pngBinary = atob('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFklEQVQImWP8//8/AwMDEwMDAwMDAwAkBgMBmjCi+wAAAABJRU5ErkJggg==');
            const pngBytes = new Uint8Array(pngBinary.length);
            for (let index = 0; index < pngBinary.length; index += 1) {
                pngBytes[index] = pngBinary.charCodeAt(index);
            }
            formData.append('mobile_app_image', new File([pngBytes], 'dusk-created-cover.png', { type: 'image/png' }));

            const token = document.querySelector('meta[name="csrf_token"], meta[name="csrf-token"]')?.content;

            fetch('/videos/sorts/store', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: formData,
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

    private function putLegacyVideoDeleteFiles(string $fileName): void
    {
        foreach (['coverphoto', 'thumbnail'] as $directory) {
            $path = public_path('storage/video/'.$directory.'/'.$fileName);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, 'video-delete-fixture');
        }
    }

    private function deleteFixtures(): void
    {
        Video::query()
            ->whereIn('title', [
                self::VIDEO_TITLE,
                self::SHORT_TITLE,
                self::SAVED_TITLE,
                self::DELETE_TITLE,
                self::CREATED_TITLE,
            ])
            ->get()
            ->each(function (Video $video): void {
                SaveForLater::query()->where('video_id', $video->id)->delete();
                Posts::query()
                    ->where('publisher', 'video_and_shorts')
                    ->where('publisher_id', $video->id)
                    ->delete();

                foreach ([
                    public_path('storage/videos/'.$video->file),
                    public_path('storage/videos/'.$video->mobile_app_image),
                    public_path('storage/video/coverphoto/'.$video->file),
                    public_path('storage/video/thumbnail/'.$video->file),
                ] as $path) {
                    if ($path && File::exists($path)) {
                        File::delete($path);
                    }
                }

                $video->delete();
            });

        Posts::query()
            ->where('publisher', 'video_and_shorts')
            ->whereIn('description', [
                self::VIDEO_TITLE,
                self::SHORT_TITLE,
                self::SAVED_TITLE,
                self::DELETE_TITLE,
                self::CREATED_TITLE,
            ])
            ->delete();

        User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->get()
            ->each(function (User $user): void {
                SaveForLater::query()->where('user_id', $user->id)->delete();
                $user->delete();
            });

        foreach ([
            public_path('storage/video/coverphoto/'.self::DELETE_FILE),
            public_path('storage/video/thumbnail/'.self::DELETE_FILE),
        ] as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }
}
