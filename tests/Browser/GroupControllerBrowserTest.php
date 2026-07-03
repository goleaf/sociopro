<?php

namespace Tests\Browser;

use App\Enums\MediaFileType;
use App\Enums\MembershipRole;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\AlbumImage;
use App\Models\Albums;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Invite;
use App\Models\MediaFile;
use App\Models\Notification;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class GroupControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-group-viewer@example.test',
        'dusk-group-owner@example.test',
        'dusk-group-member@example.test',
        'dusk-group-invited@example.test',
        'dusk-group-second-invited@example.test',
    ];

    private const PUBLIC_TITLE = 'Dusk Group Public';

    private const JOINED_TITLE = 'Dusk Group Joined';

    private const CREATED_TITLE = 'Dusk Group Created';

    private const UPDATED_TITLE = 'Dusk Group Updated';

    private const ALBUM_TITLE = 'Dusk Group Album';

    private const EVENT_TITLE = 'Dusk Group Event';

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

    public function test_group_pages_and_ajax_actions_work_in_browser(): void
    {
        $viewer = $this->activeUser('dusk-group-viewer@example.test', 'Dusk Group Viewer');
        $owner = $this->activeUser('dusk-group-owner@example.test', 'Dusk Group Owner');
        $otherMember = $this->activeUser('dusk-group-member@example.test', 'Dusk Group Existing Member');
        $invited = $this->activeUser('dusk-group-invited@example.test', 'Dusk Group Invited User');
        $secondInvited = $this->activeUser('dusk-group-second-invited@example.test', 'Dusk Group Second Invited');

        $publicGroup = $this->createGroup($owner, self::PUBLIC_TITLE, 'Dusk public group subtitle');
        $joinedGroup = $this->createGroup($owner, self::JOINED_TITLE, 'Dusk joined group subtitle');
        $album = $this->createAlbum($owner, $publicGroup, self::ALBUM_TITLE);
        $this->createGroupEvent($owner, $publicGroup);
        $this->createGroupMember($otherMember, $publicGroup);
        $this->createGroupMember($viewer, $joinedGroup);

        $this->browse(function (Browser $browser) use ($album, $invited, $publicGroup, $secondInvited, $viewer) {
            $browser->loginAs($viewer)
                ->visit('/groups')
                ->assertSee(self::PUBLIC_TITLE)
                ->visit('/group/view/details/'.$publicGroup->id)
                ->assertSee(self::PUBLIC_TITLE)
                ->assertSee('Discussion')
                ->visit('/group/peopel/info/'.$publicGroup->id)
                ->assertSee(self::PUBLIC_TITLE)
                ->assertSee('Members')
                ->visit('/group/photo/view/'.$publicGroup->id)
                ->assertSee(self::PUBLIC_TITLE)
                ->assertSee('Photos')
                ->visit('/all/peopel/group/view/'.$publicGroup->id)
                ->assertSee(self::PUBLIC_TITLE)
                ->assertSee('Members')
                ->visit('/group/event/view/'.$publicGroup->id)
                ->assertSee(self::PUBLIC_TITLE)
                ->assertSee(self::EVENT_TITLE)
                ->visit('/group/search/view?search='.urlencode('Dusk Group Public'))
                ->assertSee(self::PUBLIC_TITLE)
                ->visit('/group/all/view')
                ->assertSee(self::PUBLIC_TITLE)
                ->visit('/group/user/joined')
                ->assertSee(self::JOINED_TITLE);

            $this->postForm($browser, '/group/store', [
                'name' => self::CREATED_TITLE,
                'subtitle' => 'Dusk created group subtitle',
                'about' => 'Dusk created group about',
                'privacy' => Visibility::Public->value,
                'status' => '1',
            ], 'groupStoreResponse', 'reload');

            $createdGroup = Group::query()->where('title', self::CREATED_TITLE)->firstOrFail();

            $this->postForm($browser, '/update/group/'.$createdGroup->id, [
                'name' => self::UPDATED_TITLE,
                'subtitle' => 'Dusk updated group subtitle',
                'about' => 'Dusk updated group about',
                'privacy' => Visibility::Public->value,
                'status' => '1',
                'location' => 'Vilnius Browser Group Hall',
                'group_type' => 'testing',
            ], 'groupUpdateResponse', 'reload');

            $this->postForm($browser, '/update/coverphoto/group/'.$createdGroup->id, [], 'groupCoverResponse', 'reload');

            $browser->visit('/group/user/create')
                ->assertSee(self::UPDATED_TITLE);

            $this->assertFetchResponseContains($browser, '/group/join/'.$publicGroup->id, 'groupJoinResponse', 'reload');
            $this->assertSame(1, GroupMember::query()
                ->where('user_id', $viewer->id)
                ->where('group_id', $publicGroup->id)
                ->count());

            $this->assertFetchResponseContains($browser, '/group/join/'.$publicGroup->id, 'groupJoinAgainResponse', 'reload');
            $this->assertSame(1, GroupMember::query()
                ->where('user_id', $viewer->id)
                ->where('group_id', $publicGroup->id)
                ->count());

            $browser->visit('/search_friends_for_inviting?group_id='.$publicGroup->id.'&search_value='.urlencode('Dusk Group Invited'))
                ->assertSourceHas('Dusk Group Invited User');

            $this->postForm($browser, '/group/invites/sent', [
                'group_id' => $publicGroup->id,
                'invited_group_users_id' => [$invited->id, $secondInvited->id],
            ], 'groupInviteResponse', 'reload');

            $this->postMultipartImageForm($browser, '/album/add/image', [
                'album' => $album->id,
                'group_id' => $publicGroup->id,
                'privacy' => Visibility::Public->value,
            ], 'images[]', 'groupAlbumImageResponse', 'reload');

            $this->assertFetchResponseContains($browser, '/group/rjoin/'.$publicGroup->id, 'groupLeaveResponse', 'reload');

            $browser->visit('/load_groups_by_scrolling?offset=0')
                ->assertSourceHas(self::UPDATED_TITLE)
                ->visit('/album/details/list/albums/'.$album->id)
                ->assertSee(self::ALBUM_TITLE);
        });

        $updatedGroup = Group::query()->where('title', self::UPDATED_TITLE)->firstOrFail();
        $this->assertSame($viewer->id, (int) $updatedGroup->user_id);
        $this->assertSame('Vilnius Browser Group Hall', $updatedGroup->location);
        $this->assertSame('testing', $updatedGroup->group_type);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $updatedGroup->id,
            'user_id' => $viewer->id,
            'role' => MembershipRole::Admin->value,
            'is_accepted' => '1',
        ]);
        $this->assertDatabaseMissing('group_members', [
            'group_id' => $publicGroup->id,
            'user_id' => $viewer->id,
        ]);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $publicGroup->id,
            'user_id' => $otherMember->id,
        ]);

        foreach ([$invited, $secondInvited] as $invitedUser) {
            $this->assertDatabaseHas('invites', [
                'invite_sender_id' => $viewer->id,
                'invite_reciver_id' => $invitedUser->id,
                'group_id' => $publicGroup->id,
                'is_accepted' => '0',
            ]);
            $this->assertDatabaseHas('notifications', [
                'sender_user_id' => $viewer->id,
                'reciver_user_id' => $invitedUser->id,
                'type' => 'group',
                'group_id' => $publicGroup->id,
            ]);
        }

        $albumImage = AlbumImage::query()
            ->where('album_id', $album->id)
            ->where('group_id', $publicGroup->id)
            ->firstOrFail();

        $post = Posts::query()
            ->where('album_image_id', $albumImage->id)
            ->firstOrFail();

        $this->assertSame($viewer->id, (int) $albumImage->user_id);
        $this->assertSame($viewer->id, (int) $post->user_id);
        $this->assertDatabaseHas('media_files', [
            'post_id' => $post->post_id,
            'album_id' => $album->id,
            'album_image_id' => $albumImage->id,
            'file_type' => MediaFileType::Image->value,
            'privacy' => Visibility::Public->value,
        ]);
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postMultipartImageForm(Browser $browser, string $url, array $payload, string $fileField, string $windowKey, string $expectedText): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $encodedFileField = json_encode($fileField, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);
        $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;
            const payload = {$encodedPayload};
            const formData = new FormData();

            Object.entries(payload).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((item) => formData.append(key + '[]', item));
                    return;
                }

                formData.append(key, value ?? '');
            });

            const binary = atob('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFklEQVQImWP8//8/AwMDEwMDAwMDAwAkBgMBmjCi+wAAAABJRU5ErkJggg==');
            const bytes = new Uint8Array(binary.length);
            for (let index = 0; index < binary.length; index += 1) {
                bytes[index] = binary.charCodeAt(index);
            }

            const file = new File([bytes], 'dusk-group-album.png', { type: 'image/png' });
            formData.append({$encodedFileField}, file);

            const token = document.querySelector('meta[name="csrf_token"], meta[name="csrf-token"]')?.content;

            fetch({$encodedUrl}, {
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

    private function createGroup(User $owner, string $title, string $subtitle): Group
    {
        $group = Group::query()->where('title', $title)->first() ?? new Group;
        $group->forceFill([
            'user_id' => $owner->id,
            'title' => $title,
            'subtitle' => $subtitle,
            'privacy' => Visibility::Public->value,
            'location' => 'Vilnius Dusk Group',
            'group_type' => 'testing',
            'logo' => null,
            'banner' => null,
            'about' => 'Dusk group about text',
            'status' => '1',
        ]);
        $group->save();

        return $group;
    }

    private function createGroupMember(User $user, Group $group, MembershipRole $role = MembershipRole::General): GroupMember
    {
        $member = GroupMember::query()
            ->where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->first() ?? new GroupMember;
        $member->forceFill([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'role' => $role->value,
            'is_accepted' => '1',
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
        ]);
        $member->save();

        return $member;
    }

    private function createAlbum(User $owner, Group $group, string $title): Albums
    {
        $album = Albums::query()->where('title', $title)->first() ?? new Albums;
        $album->forceFill([
            'user_id' => $owner->id,
            'group_id' => $group->id,
            'page_id' => null,
            'title' => $title,
            'sub_title' => 'Dusk group album subtitle',
            'thumbnail' => null,
            'privacy' => Visibility::Public->value,
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
        ]);
        $album->save();

        return $album;
    }

    private function createGroupEvent(User $owner, Group $group): Event
    {
        $event = Event::query()->where('title', self::EVENT_TITLE)->first() ?? new Event;
        $event->forceFill([
            'user_id' => $owner->id,
            'group_id' => $group->id,
            'title' => self::EVENT_TITLE,
            'description' => 'Dusk group event description',
            'event_date' => '2026-07-20',
            'event_time' => '14:00',
            'location' => 'Vilnius Dusk Group Event',
            'going_users_id' => json_encode([]),
            'interested_users_id' => json_encode([]),
            'banner' => null,
            'privacy' => Visibility::Public->value,
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
        ]);
        $event->save();

        return $event;
    }

    private function deleteFixtures(): void
    {
        $groupIds = Group::query()
            ->where('title', 'like', 'Dusk Group%')
            ->pluck('id');

        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        $albumIds = Albums::query()
            ->where('title', 'like', 'Dusk Group%')
            ->when($groupIds->isNotEmpty(), fn ($query) => $query->orWhereIn('group_id', $groupIds))
            ->pluck('id');

        $albumImageQuery = AlbumImage::query();
        if ($albumIds->isNotEmpty()) {
            $albumImageQuery->whereIn('album_id', $albumIds);
        }
        if ($groupIds->isNotEmpty()) {
            $albumImageQuery->orWhereIn('group_id', $groupIds);
        }
        $albumImages = $albumImageQuery->get(['id', 'image']);
        $albumImageIds = $albumImages->pluck('id');

        foreach ($albumImages as $albumImage) {
            if (! empty($albumImage->image)) {
                Storage::disk('public')->delete('album/images/'.$albumImage->image);
            }
        }

        $mediaFileQuery = MediaFile::query();
        if ($groupIds->isNotEmpty()) {
            $mediaFileQuery->whereIn('group_id', $groupIds);
        }
        if ($albumIds->isNotEmpty()) {
            $mediaFileQuery->orWhereIn('album_id', $albumIds);
        }
        if ($albumImageIds->isNotEmpty()) {
            $mediaFileQuery->orWhereIn('album_image_id', $albumImageIds);
        }
        $mediaFiles = $mediaFileQuery->get(['id', 'file_name', 'file_type']);

        foreach ($mediaFiles as $mediaFile) {
            if (empty($mediaFile->file_name)) {
                continue;
            }

            $directory = $mediaFile->file_type === MediaFileType::Video->value ? 'post/videos' : 'post/images';
            Storage::disk('public')->delete($directory.'/'.$mediaFile->file_name);
        }

        if ($mediaFiles->isNotEmpty()) {
            MediaFile::query()->whereIn('id', $mediaFiles->pluck('id'))->delete();
        }

        $postQuery = Posts::query()->where('description', 'like', 'Dusk group%');
        if ($groupIds->isNotEmpty()) {
            $postQuery->orWhere(function ($query) use ($groupIds): void {
                $query->where('publisher', 'group')->whereIn('publisher_id', $groupIds);
            });
        }
        if ($albumImageIds->isNotEmpty()) {
            $postQuery->orWhereIn('album_image_id', $albumImageIds);
        }
        $postQuery->delete();

        if ($albumImageIds->isNotEmpty()) {
            AlbumImage::query()->whereIn('id', $albumImageIds)->delete();
        }
        if ($albumIds->isNotEmpty()) {
            Albums::query()->whereIn('id', $albumIds)->delete();
        }
        if ($groupIds->isNotEmpty()) {
            Invite::query()->whereIn('group_id', $groupIds)->delete();
            Notification::query()->whereIn('group_id', $groupIds)->delete();
            Event::query()->whereIn('group_id', $groupIds)->delete();
            GroupMember::query()->whereIn('group_id', $groupIds)->delete();
            Group::query()->whereIn('id', $groupIds)->delete();
        }

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
        Event::query()
            ->whereIn('user_id', $userIds)
            ->delete();
        GroupMember::query()
            ->whereIn('user_id', $userIds)
            ->delete();
        Group::query()
            ->whereIn('user_id', $userIds)
            ->delete();
        User::query()
            ->whereIn('id', $userIds)
            ->delete();
    }
}
