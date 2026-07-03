<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApiHelperTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            File::delete($path);
        }

        parent::tearDown();
    }

    public function test_user_helpers_return_user_info_and_profile_asset_urls(): void
    {
        $this->putPublicFile('storage/userimage/optimized/api-helper-avatar.jpg');
        $this->putPublicFile('storage/cover_photo/optimized/api-helper-cover.jpg');

        $user = User::factory()->create([
            'name' => 'API Helper User',
            'photo' => 'api-helper-avatar.jpg',
            'cover_photo' => 'api-helper-cover.jpg',
            'friends' => json_encode([]),
        ]);

        $userInfo = get_user_info($user->id);

        $this->assertSame($user->id, $userInfo->id);
        $this->assertSame('API Helper User', $userInfo->name);
        $this->assertSame(
            url('public/storage/userimage/optimized/api-helper-avatar.jpg'),
            get_user_images($user->id, 'optimized')
        );
        $this->assertSame(
            url('public/storage/userimage/default.png'),
            get_user_images('missing-avatar.jpg')
        );
        $this->assertSame(
            'https://cdn.example.test/avatar.jpg',
            get_user_images('https://cdn.example.test/avatar.jpg')
        );
        $this->assertSame(
            url('public/storage/cover_photo/optimized/api-helper-cover.jpg'),
            get_cover_photos($user->id, 'optimized')
        );
        $this->assertSame(
            url('public/storage/cover_photo/default.jpg'),
            get_cover_photos('missing-cover.jpg')
        );
        $this->assertSame(
            'https://cdn.example.test/cover.jpg',
            get_cover_photos('https://cdn.example.test/cover.jpg')
        );
    }

    public function test_post_and_story_asset_helpers_return_local_remote_and_default_urls(): void
    {
        $this->putPublicFile('storage/post/images/optimized/api-helper-post.jpg');
        $this->putPublicFile('storage/post/videos/api-helper-post.mp4');
        $this->putPublicFile('storage/story/images/optimized/api-helper-story.jpg');
        $this->putPublicFile('storage/story/videos/api-helper-story.mp4');

        $this->assertSame(
            url('public/storage/post/images/optimized/api-helper-post.jpg'),
            get_post_images('api-helper-post.jpg', 'optimized')
        );
        $this->assertSame('https://cdn.example.test/post.jpg', get_post_images('https://cdn.example.test/post.jpg'));
        $this->assertSame(url('public/storage/post/images/default.png'), get_post_images('missing-post.jpg'));

        $this->assertSame(
            url('public/storage/post/videos/api-helper-post.mp4'),
            get_post_videos('api-helper-post.mp4')
        );
        $this->assertSame('https://cdn.example.test/post.mp4', get_post_videos('https://cdn.example.test/post.mp4'));
        $this->assertSame(url('public/storage/post/videos/default.png'), get_post_videos('missing-post.mp4'));

        $this->assertSame(
            url('public/storage/story/images/optimized/api-helper-story.jpg'),
            get_story_images('api-helper-story.jpg', 'optimized')
        );
        $this->assertSame('https://cdn.example.test/story.jpg', get_story_images('https://cdn.example.test/story.jpg'));
        $this->assertSame(url('public/storage/story/images/default.jpg'), get_story_images('missing-story.jpg'));

        $this->assertSame(
            url('public/storage/story/videos/api-helper-story.mp4'),
            get_story_videos('api-helper-story.mp4')
        );
        $this->assertSame('https://cdn.example.test/story.mp4', get_story_videos('https://cdn.example.test/story.mp4'));
        $this->assertSame(url('public/storage/story/videos/default.jpg'), get_story_videos('missing-story.mp4'));
    }

    public function test_group_and_generic_asset_helpers_build_legacy_urls(): void
    {
        $this->assertSame(
            url('public/storage/groups/logo/group-logo.jpg'),
            get_group_logos('group-logo.jpg', 'logo')
        );
        $this->assertSame(url('public/storage/groups/logo/default/default.jpg'), get_group_logos('', 'logo'));
        $this->assertSame('https://cdn.example.test/group-logo.jpg', get_group_logos('https://cdn.example.test/group-logo.jpg', 'logo'));

        $this->assertSame(
            url('public/storage/groups/coverphoto/group-cover.jpg'),
            get_group_cover_photos('group-cover.jpg', 'coverphoto')
        );
        $this->assertSame(url('public/storage/groups/coverphoto/default/default.jpg'), get_group_cover_photos('', 'coverphoto'));

        $this->assertSame(
            url('public/assets/frontend/images/campaign/campaign.jpg'),
            get_all_assets_photos('campaign.jpg', 'campaign', 'images')
        );
        $this->assertSame(url('public/assets/frontend/images/campaign/default/default.jpg'), get_all_assets_photos('', 'campaign', 'images'));

        $this->assertSame(
            url('public/storage/event/coverphoto/event.jpg'),
            get_group_event_photos('event.jpg', 'coverphoto', 'event')
        );
        $this->assertSame(url('public/storage/event/coverphoto/default/default.jpg'), get_group_event_photos('', 'coverphoto', 'event'));

        $this->assertSame(url('public/storage/videos/video.mp4'), get_one_folder_files('video.mp4', 'videos'));
        $this->assertSame(url('public/storage/videos/default/default.jpg'), get_one_folder_files('', 'videos'));
        $this->assertSame('https://cdn.example.test/video.mp4', get_one_folder_files('https://cdn.example.test/video.mp4', 'videos'));
    }

    public function test_members_by_group_id_returns_member_payloads_without_user_query_loop(): void
    {
        $this->putPublicFile('storage/userimage/api-helper-member-one.jpg');
        $group = Group::factory()->create();
        $firstUser = User::factory()->create([
            'name' => 'First Member',
            'photo' => 'api-helper-member-one.jpg',
        ]);
        $secondUser = User::factory()->create([
            'name' => 'Second Member',
            'photo' => null,
            'friends' => json_encode([]),
        ]);

        $firstUser->forceFill([
            'friends' => json_encode([$firstUser->id, $secondUser->id]),
        ])->save();

        $firstMember = GroupMember::factory()->create([
            'group_id' => $group->id,
            'user_id' => $firstUser->id,
        ]);
        $secondMember = GroupMember::factory()->create([
            'group_id' => $group->id,
            'user_id' => $secondUser->id,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $members = members_by_group_id($group->id);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(2, count($queries));
        $this->assertSame([
            [
                'id' => $firstMember->id,
                'user_id' => $firstUser->id,
                'group_id' => $group->id,
                'name' => 'First Member',
                'photo' => url('public/storage/userimage/api-helper-member-one.jpg'),
                'countfriends' => 2,
                'matching_friends_count' => 1,
            ],
            [
                'id' => $secondMember->id,
                'user_id' => $secondUser->id,
                'group_id' => $group->id,
                'name' => 'Second Member',
                'photo' => url('public/storage/userimage/default.png'),
                'countfriends' => 0,
                'matching_friends_count' => 0,
            ],
        ], $members);
    }

    public function test_api_helper_uses_eloquent_instead_of_database_facade_queries(): void
    {
        $contents = $this->withoutComments(File::get(app_path('Helpers/ApiHelper.php')));

        $this->assertStringNotContainsString('DB::', $contents);
        $this->assertStringContainsString('User::query()', $contents);
        $this->assertStringContainsString('GroupMember::query()', $contents);
        $this->assertStringContainsString("with(['user'", $contents);
    }

    private function putPublicFile(string $relativePath): void
    {
        $path = public_path($relativePath);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'api-helper-test-file');

        $this->createdFiles[] = $path;
    }

    private function withoutComments(string $contents): string
    {
        $tokens = token_get_all($contents);

        return collect($tokens)
            ->reject(fn ($token): bool => is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true))
            ->map(fn ($token): string => is_array($token) ? $token[1] : $token)
            ->implode('');
    }
}
