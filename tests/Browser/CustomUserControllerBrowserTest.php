<?php

namespace Tests\Browser;

use App\Enums\ContentStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Follower;
use App\Models\Friendships;
use App\Models\MediaFile;
use App\Models\Notification;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CustomUserControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-custom-password@example.test',
        'dusk-custom-viewer@example.test',
        'dusk-custom-profile@example.test',
        'dusk-custom-friend@example.test',
        'dusk-custom-deactivate@example.test',
    ];

    private const POST_DESCRIPTION = 'Dusk custom user profile post';

    private const IMAGE_FILE = 'custom-user-browser/profile-image.jpg';

    private const VIDEO_FILE = 'custom-user-browser/profile-video.mp4';

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

    public function test_password_change_form_updates_current_user_password_in_browser(): void
    {
        $user = $this->activeUser('dusk-custom-password@example.test', 'Dusk Custom Password');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/user/password/change')
                ->assertSee('Reset password')
                ->type('prevpass', 'password')
                ->type('password', 'DuskCustomPassword123')
                ->type('password_confirmation', 'DuskCustomPassword123')
                ->press('Update Password')
                ->waitForLocation('/');
        });

        $this->assertTrue(Hash::check('DuskCustomPassword123', $user->refresh()->password));
    }

    public function test_profile_friend_media_and_account_status_routes_in_browser(): void
    {
        $viewer = $this->activeUser('dusk-custom-viewer@example.test', 'Dusk Custom Viewer');
        $profile = $this->activeUser('dusk-custom-profile@example.test', 'Dusk Custom Profile');
        $friend = $this->activeUser('dusk-custom-friend@example.test', 'Dusk Custom Friend');
        $deactivateUser = $this->activeUser('dusk-custom-deactivate@example.test', 'Dusk Custom Deactivate');

        $this->createAcceptedFriendship($profile, $friend);
        $post = $this->createProfilePost($profile);
        $image = $this->createMediaFile($profile, $post, self::IMAGE_FILE, 'image', 'post/images', 'browser image');
        $video = $this->createMediaFile($profile, $post, self::VIDEO_FILE, 'video', 'post/videos', 'browser video');

        $this->browse(function (Browser $browser) use ($image, $profile, $viewer, $video) {
            $browser->loginAs($viewer)
                ->visit('/user/view/profile/'.$profile->id)
                ->assertSee('Dusk Custom Profile')
                ->clickLink('Add Friend')
                ->waitForText('Requested', 5);

            $this->assertSame(1, Friendships::query()
                ->where('requester', $viewer->id)
                ->where('accepter', $profile->id)
                ->where('is_accepted', 0)
                ->count());

            $browser->visit('/user/unfriend/'.$profile->id)
                ->assertSourceHas('reload');

            $this->assertSame(0, Friendships::query()
                ->where('requester', $viewer->id)
                ->where('accepter', $profile->id)
                ->count());

            $browser->visit('/user/friends/'.$profile->id)
                ->assertSee('Dusk Custom Friend')
                ->visit('/user/photos/'.$profile->id.'/customer')
                ->assertSee('Photos')
                ->assertSee('Album')
                ->visit('/user/videos/'.$profile->id)
                ->assertSee('Your videos')
                ->visit('/user/load_post_by_scrolling?id='.$profile->id.'&offset=0')
                ->assertSourceHas('single-entry');

            $this->assertFetchStatus($browser, '/download/media/file/image/'.$image->id, 'customUserImageDownloadStatus');
            $this->assertFetchStatus($browser, '/download/media/file/'.$video->id, 'customUserVideoDownloadStatus');

            $browser->loginAs($profile)
                ->visit('/video/delete/'.$image->id)
                ->assertSourceHas('reload');
        });

        $this->assertDatabaseMissing('media_files', ['id' => $image->id]);
        $this->assertFileDoesNotExist(public_path('storage/post/images/'.self::IMAGE_FILE));

        $this->browse(function (Browser $browser) use ($deactivateUser) {
            $browser->loginAs($deactivateUser)
                ->visit('/user/status/'.$deactivateUser->id)
                ->assertSourceHas('/login')
                ->visit('/login')
                ->assertSee('Login');
        });

        $this->assertDatabaseHas('users', [
            'id' => $deactivateUser->id,
            'status' => UserAccountStatus::Disabled->value,
        ]);
    }

    private function assertFetchStatus(Browser $browser, string $url, string $windowKey, int $status = 200): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;
            fetch({$encodedUrl}, { credentials: 'same-origin' })
                .then(response => { window[{$encodedWindowKey}] = response.status; })
                .catch(() => { window[{$encodedWindowKey}] = -1; });
        JS);

        $browser->waitUntil("window[{$encodedWindowKey}] === {$status}", 5);
    }

    private function activeUser(string $email, string $name): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
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

    private function createAcceptedFriendship(User $firstUser, User $secondUser): void
    {
        Friendships::query()->create([
            'requester' => $firstUser->id,
            'accepter' => $secondUser->id,
            'importance' => 1,
            'is_accepted' => 1,
            'accepted_at' => time(),
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $firstUser->forceFill(['friends' => json_encode([$secondUser->id])])->save();
        $secondUser->forceFill(['friends' => json_encode([$firstUser->id])])->save();
    }

    private function createProfilePost(User $profile): Posts
    {
        return Posts::query()->create([
            'user_id' => $profile->id,
            'publisher' => 'post',
            'publisher_id' => $profile->id,
            'post_type' => 'general',
            'privacy' => Visibility::Public->value,
            'description' => self::POST_DESCRIPTION,
            'user_reacts' => json_encode([]),
            'status' => ContentStatus::Active->value,
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
        ]);
    }

    private function createMediaFile(User $profile, Posts $post, string $fileName, string $type, string $directory, string $contents): MediaFile
    {
        File::ensureDirectoryExists(public_path('storage/'.$directory.'/'.dirname($fileName)));
        File::put(public_path('storage/'.$directory.'/'.$fileName), $contents);

        return MediaFile::query()->create([
            'user_id' => $profile->id,
            'post_id' => $post->post_id,
            'file_name' => $fileName,
            'file_type' => $type,
            'privacy' => Visibility::Public->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function deleteFixtures(): void
    {
        File::deleteDirectory(public_path('storage/post/images/custom-user-browser'));
        File::deleteDirectory(public_path('storage/post/videos/custom-user-browser'));

        MediaFile::query()
            ->whereIn('file_name', [self::IMAGE_FILE, self::VIDEO_FILE])
            ->delete();
        Posts::query()
            ->where('description', self::POST_DESCRIPTION)
            ->delete();

        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        Notification::query()
            ->whereIn('sender_user_id', $userIds)
            ->orWhereIn('reciver_user_id', $userIds)
            ->delete();
        Follower::query()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('follow_id', $userIds)
            ->delete();
        Friendships::query()
            ->whereIn('requester', $userIds)
            ->orWhereIn('accepter', $userIds)
            ->delete();
        MediaFile::query()
            ->whereIn('user_id', $userIds)
            ->delete();
        Posts::query()
            ->whereIn('user_id', $userIds)
            ->delete();
        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
