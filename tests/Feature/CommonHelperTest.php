<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Albums;
use App\Models\Language;
use App\Models\MediaFile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CommonHelperTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $createdFiles = [];

    /**
     * @var list<string>
     */
    private array $createdDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            File::delete($path);
        }

        foreach (array_reverse($this->createdDirectories) as $path) {
            File::deleteDirectory($path);
        }

        parent::tearDown();
    }

    public function test_database_backed_common_helpers_return_values_through_eloquent_models(): void
    {
        $this->putPublicFile('storage/thumbnails/album/optimized/common-album-thumb.jpg');
        $this->putPublicFile('storage/post/images/optimized/common-album-fallback.jpg');

        $addon = Addon::factory()->create([
            'unique_identifier' => 'common-helper-addon',
            'status' => 1,
        ]);
        $album = Albums::factory()->create([
            'thumbnail' => 'common-album-thumb.jpg',
        ]);
        $fallbackAlbum = Albums::factory()->create([
            'thumbnail' => null,
        ]);
        MediaFile::factory()->create([
            'album_id' => $fallbackAlbum->id,
            'file_name' => 'common-album-old.jpg',
        ]);
        MediaFile::factory()->create([
            'album_id' => $fallbackAlbum->id,
            'file_name' => 'common-album-fallback.jpg',
        ]);

        Language::factory()->create([
            'name' => 'qa-common-helper',
            'phrase' => 'Profile ____ saved',
            'translated' => 'Profil ____ ulozen',
        ]);
        session(['active_language' => 'qa-common-helper']);

        $settingType = 'common_helper_payload_'.uniqid();
        Setting::factory()->create([
            'type' => $settingType,
            'description' => json_encode(['enabled' => true, 'limit' => 9]),
        ]);

        $this->assertSame($addon->status, addon_status('common-helper-addon'));
        $this->assertTrue(get_all_language()->pluck('name')->contains('qa-common-helper'));
        $this->assertSame('Profil avatar ulozen', get_phrase('Profile ____ saved', ['avatar']));
        $this->assertSame(['enabled' => true, 'limit' => 9], get_settings($settingType, true));
        $this->assertEquals((object) ['enabled' => true, 'limit' => 9], get_settings($settingType, 'object'));
        $this->assertSame(
            asset('storage/thumbnails/album/optimized/common-album-thumb.jpg'),
            get_album_thumbnail($album->id, 'optimized')
        );
        $this->assertSame(
            asset('storage/post/images/optimized/common-album-fallback.jpg'),
            get_album_thumbnail($fallbackAlbum->id, 'optimized')
        );

        $missingPhrase = 'Common helper missing phrase '.uniqid();

        $this->assertSame($missingPhrase, get_phrase($missingPhrase));
        $this->assertDatabaseHas('languages', [
            'name' => 'qa-common-helper',
            'phrase' => $missingPhrase,
            'translated' => $missingPhrase,
        ]);
    }

    public function test_profile_post_and_domain_asset_helpers_return_existing_remote_and_default_urls(): void
    {
        $this->putPublicFile('storage/common-helper/existing.jpg');
        $this->putPublicFile('storage/userimage/optimized/common-avatar.jpg');
        $this->putPublicFile('storage/cover_photo/optimized/common-cover.jpg');
        $this->putPublicFile('storage/post/images/optimized/common-post.jpg');
        $this->putPublicFile('storage/post/videos/common-video.mp4');
        $this->putPublicFile('storage/marketplace/thumbnail/common-product.jpg');
        $this->putPublicFile('storage/sponsor/thumbnail/common-sponsor.jpg');
        $this->putPublicFile('storage/blog/thumbnail/common-blog.jpg');
        $this->putPublicFile('storage/job/thumbnail/common-job.jpg');
        $this->putPublicFile('storage/pages/logo/common-page-logo.jpg');
        $this->putPublicFile('storage/pages/coverphoto/common-page-cover.jpg');
        $this->putPublicFile('storage/groups/logo/common-group-logo.jpg');
        $this->putPublicFile('storage/groups/coverphoto/common-group-cover.jpg');
        $this->putPublicFile('storage/logo/favicon/common-favicon.ico');

        $user = User::factory()->create([
            'photo' => 'common-avatar.jpg',
            'cover_photo' => 'common-cover.jpg',
        ]);

        $this->assertSame(asset('storage/common-helper/existing.jpg'), get_image('storage/common-helper/existing.jpg'));
        $this->assertSame(asset('storage/common-helper/default/default.png'), get_image('storage/common-helper/missing.jpg'));
        $this->assertSame(asset('storage/userimage/optimized/common-avatar.jpg'), get_user_image($user->id, 'optimized'));
        $this->assertSame('https://cdn.example.test/avatar.jpg', get_user_image('https://cdn.example.test/avatar.jpg'));
        $this->assertSame(asset('storage/userimage/default.png'), get_user_image('missing-avatar.jpg'));
        $this->assertSame(asset('storage/cover_photo/optimized/common-cover.jpg'), get_cover_photo($user->id, 'optimized'));
        $this->assertSame('https://cdn.example.test/cover.jpg', get_cover_photo('https://cdn.example.test/cover.jpg'));
        $this->assertSame(asset('storage/cover_photo/default.jpg'), get_cover_photo('missing-cover.jpg'));
        $this->assertSame(asset('storage/post/images/optimized/common-post.jpg'), get_post_image('common-post.jpg', 'optimized'));
        $this->assertSame('https://cdn.example.test/post.jpg', get_post_image('https://cdn.example.test/post.jpg'));
        $this->assertSame(asset('storage/post/images/default.jpg'), get_post_image('missing-post.jpg'));
        $this->assertSame(asset('storage/post/videos/common-video.mp4'), get_post_video('common-video.mp4'));
        $this->assertSame('https://cdn.example.test/post.mp4', get_post_video('https://cdn.example.test/post.mp4'));
        $this->assertSame(asset('storage/post/videos/default.jpg'), get_post_video('missing-post.mp4'));
        $this->assertSame(asset('storage/marketplace/thumbnail/common-product.jpg'), get_product_image('common-product.jpg', 'thumbnail'));
        $this->assertSame(asset('storage/sponsor/thumbnail/common-sponsor.jpg'), get_sponsor_image('common-sponsor.jpg', 'thumbnail'));
        $this->assertSame(asset('storage/blog/thumbnail/common-blog.jpg'), get_blog_image('common-blog.jpg', 'thumbnail'));
        $this->assertSame(asset('storage/job/thumbnail/common-job.jpg'), get_job_image('common-job.jpg', 'thumbnail'));
        $this->assertSame(asset('storage/pages/logo/common-page-logo.jpg'), get_page_logo('common-page-logo.jpg', 'logo'));
        $this->assertSame(asset('storage/pages/coverphoto/common-page-cover.jpg'), get_page_cover_photo('common-page-cover.jpg', 'coverphoto'));
        $this->assertSame(asset('storage/groups/logo/common-group-logo.jpg'), get_group_logo('common-group-logo.jpg', 'logo'));
        $this->assertSame(asset('storage/groups/coverphoto/common-group-cover.jpg'), get_group_cover_photo('common-group-cover.jpg', 'coverphoto'));
        $this->assertSame(asset('storage/logo/favicon/common-favicon.ico'), get_system_logo_favicon('common-favicon.ico', 'favicon'));
        $this->assertSame(asset('storage/blog/thumbnail/default/default.jpg'), get_blog_image('', 'thumbnail'));
    }

    public function test_file_helpers_create_view_and_remove_public_storage_files_safely(): void
    {
        $mainPath = $this->putPublicFile('storage/common-helper-remove/remove-me.jpg');
        $optimizedPath = $this->putPublicFile('storage/common-helper-remove/optimized/remove-me.jpg');
        $legacyCoverPath = $this->putPublicFile('storage/common-helper-legacy/coverphoto/remove-me.jpg');
        $legacyThumbPath = $this->putPublicFile('storage/common-helper-legacy/thumbnail/remove-me.jpg');

        $uploadPath = uploadTo('common-helper-upload');
        $this->createdDirectories[] = public_path('storage/common-helper-upload');

        $this->assertDirectoryExists($uploadPath);
        $this->assertStringEndsWith('/storage/common-helper-upload/', $uploadPath);
        $this->assertSame(asset('storage/event/thumbnail/event.jpg'), viewImage('event', 'event.jpg', 'thumbnail'));
        $this->assertSame(asset('storage/event/thumbnail/default/default.jpg'), viewImage('event', '', 'thumbnail'));

        remove_file('public/storage/common-helper-remove/remove-me.jpg');
        removeFile('common-helper-legacy', 'remove-me.jpg');

        $this->assertFileDoesNotExist($mainPath);
        $this->assertFileDoesNotExist($optimizedPath);
        $this->assertFileDoesNotExist($legacyCoverPath);
        $this->assertFileDoesNotExist($legacyThumbPath);
    }

    public function test_string_date_sort_config_and_url_helpers_preserve_expected_contracts(): void
    {
        $rows = [
            'first' => ['score' => 1],
            'second' => ['score' => 3],
            'third' => ['score' => 2],
        ];
        aasort($rows, 'score');

        $this->assertSame("alert(&#039;x&#039;)<br />\nok", script_checker("<script>alert('x')</script>\n<b>ok</b>"));
        $this->assertSame("<script>alert('x')</script>", script_checker("<script>alert('x')</script>", false));
        $this->assertTrue(is_image('avatar.jpg'));
        $this->assertTrue(is_image('AVATAR.PNG'));
        $this->assertFalse(is_image('archive.zip'));
        $this->assertSame('02 Jul 2026', date_formatter(strtotime('2026-07-02 13:14:15')));
        $this->assertSame('02 Jul 2026, 01:14:15 PM', date_formatter(strtotime('2026-07-02 13:14:15'), 4));
        $this->assertSame('15$', currency(15));
        $this->assertSame('hello-sociopro-world', slugify('Hello, Sociopro World!'));
        $this->assertSame('mp4', get_video_extension('https://cdn.example.test/video.mp4?download=1'));
        $this->assertSame('webm', get_video_extension('clip.webm'));
        $this->assertSame('unknown', get_video_extension('clip.mov'));
        $this->assertSame('abcdef...', ellipsis('<b>abcdefghi</b>', 6));
        $this->assertSame(['second', 'third', 'first'], array_keys($rows));
        $this->assertTrue(is_url('https://example.com/path?x=1'));
        $this->assertTrue(is_url('ftp://example.com/file.zip'));
        $this->assertFalse(is_url('javascript:alert(1)'));
        $this->assertFalse(get_url_contents('http://169.254.169.254/latest/meta-data'));

        $random = random(16, true);

        $this->assertSame(16, strlen($random));
        $this->assertSame(strtolower($random), $random);
        $this->assertConfigFileCanBeUpdatedAndRestored();
    }

    public function test_common_helper_uses_eloquent_models_and_guarded_url_fetches(): void
    {
        $contents = $this->withoutComments(File::get(app_path('Helpers/CommonHelper.php')));

        $this->assertStringNotContainsString('DB::', $contents);
        $this->assertStringContainsString('Addon::query()', $contents);
        $this->assertStringContainsString('User::query()', $contents);
        $this->assertStringContainsString('Albums::query()', $contents);
        $this->assertStringContainsString('MediaFile::query()', $contents);
        $this->assertStringContainsString('Language::query()', $contents);
        $this->assertStringContainsString('Setting::query()', $contents);
        $this->assertStringContainsString("function_exists('get_url_contents')", $contents);
        $this->assertStringContainsString('ServerSideUrl::forConfiguredHttpFetch', $contents);
    }

    private function putPublicFile(string $relativePath): string
    {
        $path = public_path($relativePath);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'common-helper-test-file');

        $this->createdFiles[] = $path;

        return $path;
    }

    private function assertConfigFileCanBeUpdatedAndRestored(): void
    {
        $path = base_path('config/config.json');
        $original = File::get($path);

        try {
            set_config('COMMON_HELPER_TEST_KEY', 'present');

            $config = json_decode(File::get($path), true);

            $this->assertSame('present', $config['COMMON_HELPER_TEST_KEY']);
        } finally {
            File::put($path, $original);
        }
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
