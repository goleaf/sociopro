<?php

namespace Tests\Browser;

use App\Enums\ContentStatus;
use App\Enums\MediaFileType;
use App\Enums\PostType;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Albums;
use App\Models\Friendships;
use App\Models\MediaFile;
use App\Models\Posts;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfileControllerBrowserTest extends DuskTestCase
{
    private const EMAILS = [
        'dusk-profile-owner@example.test',
        'dusk-profile-friend@example.test',
        'dusk-profile-requester@example.test',
    ];

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

    public function test_profile_pages_and_ajax_endpoints_render_in_browser(): void
    {
        $owner = $this->activeUser('dusk-profile-owner@example.test', 'Dusk Profile Owner');
        $friend = $this->activeUser('dusk-profile-friend@example.test', 'Dusk Profile Friend');
        $requester = $this->activeUser('dusk-profile-requester@example.test', 'Dusk Profile Requester');
        $post = $this->postFor($owner, 'Dusk profile timeline post', '');
        $this->postFor($owner, 'Dusk profile checkin post', 'Vilnius');
        $photo = $this->mediaFor($owner, $post, MediaFileType::Image, 'https://cdn.example.test/dusk-profile-photo.jpg');
        $video = $this->mediaFor($owner, $post, MediaFileType::Video, 'https://cdn.example.test/dusk-profile-video.mp4');
        Albums::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Dusk Profile Album',
            'privacy' => Visibility::Public->value,
        ]);
        Friendships::factory()->accepted()->requester($friend)->accepter($owner)->create(['importance' => 3]);
        Friendships::factory()->pending()->requester($requester)->accepter($owner)->create();

        $this->browse(function (Browser $browser) use ($owner, $photo, $video) {
            $browser->loginAs($owner)
                ->visit('/profile')
                ->assertPathIs('/profile')
                ->assertSee('Dusk Profile Owner')
                ->assertSee('Timeline')
                ->assertSee('Vilnius')
                ->visit('/profile/friends')
                ->assertPathIs('/profile/friends')
                ->assertSee('Friends')
                ->assertSee('Dusk Profile Friend')
                ->visit('/profile/photos')
                ->assertPathIs('/profile/photos')
                ->assertSee('Photo')
                ->assertSourceHas($photo->file_name)
                ->visit('/profile/videos')
                ->assertPathIs('/profile/videos')
                ->assertSee('Video')
                ->assertSourceHas($video->file_name)
                ->visit('/profile/save-post-list')
                ->assertPathIs('/profile/save-post-list')
                ->assertSee('Saved Posts')
                ->visit('/profile/check-ins')
                ->assertPathIs('/profile/check-ins')
                ->assertSee('Vilnius');

            $this->assertFetchResponseContains($browser, '/profile/load_post_by_scrolling?offset=0', 'profilePostsResponse', 'Vilnius');
            $this->assertFetchResponseContains($browser, '/profile/load_photos?offset=0', 'profilePhotosResponse', 'dusk-profile-photo.jpg');
            $this->assertFetchResponseContains($browser, '/profile/load_albums?offset=0', 'profileAlbumsResponse', 'Dusk Profile Album');
            $this->assertFetchResponseContains($browser, '/profile/load_videos?offset=0', 'profileVideosResponse', 'dusk-profile-video.mp4');
            $this->assertFetchResponseContains($browser, '/profile/load_my_friends?offset=0', 'profileFriendsResponse', 'Dusk Profile Friend');
            $this->assertFetchResponseContains($browser, '/profile/load_my_friend_requests?offset=0', 'profileFriendRequestsResponse', 'Dusk Profile Requester');
        });
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
                    'Accept': 'text/html',
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
            'username' => str($email)->before('@')->replace('.', '-')->toString(),
            'phone' => '1555'.random_int(100000, 999999),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
            'about' => 'Dusk profile bio',
        ]);
    }

    private function postFor(User $user, string $description, string $location): Posts
    {
        return Posts::factory()->forOwner($user)->create([
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => PostType::General->value,
            'privacy' => Visibility::Public->value,
            'tagged_user_ids' => json_encode([]),
            'activity_id' => 0,
            'location' => $location,
            'description' => $description,
            'status' => ContentStatus::Active->value,
        ]);
    }

    private function mediaFor(User $user, Posts $post, MediaFileType $type, string $fileName): MediaFile
    {
        return MediaFile::factory()
            ->{$type === MediaFileType::Image ? 'image' : 'video'}()
            ->create([
                'user_id' => $user->id,
                'post_id' => $post->post_id,
                'story_id' => null,
                'product_id' => null,
                'page_id' => null,
                'group_id' => null,
                'chat_id' => null,
                'file_name' => $fileName,
                'file_type' => $type->value,
                'privacy' => Visibility::Public->value,
            ]);
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', self::EMAILS)
            ->pluck('id');

        Friendships::query()
            ->whereIn('requester', $userIds)
            ->orWhereIn('accepter', $userIds)
            ->delete();

        MediaFile::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('file_name', 'like', 'dusk-profile-%')
            ->delete();

        Albums::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('title', 'like', 'Dusk Profile%')
            ->delete();

        Posts::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('description', 'like', 'Dusk profile%')
            ->delete();

        User::query()
            ->whereIn('email', self::EMAILS)
            ->delete();
    }
}
