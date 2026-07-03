<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Http\Controllers\SettingController;
use App\Mail\ContactMail;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Posts;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private const METHODS = [
        'about_view',
        'policy_view',
        'contact_view',
        'contact_send',
        'term_view',
        'update_about_page_data',
        'update_about_page_data_update',
        'update_privacy_page_data_update',
        'update_term_page_data_update',
        'reported_post_to_admin',
        'reported_post_remove_by_admin',
        'smtp_settings_view',
        'smtp_settings_save',
        'system_settings_view',
        'system_settings_save',
        'amazon_s3',
        'amazon_s3_update',
        'system_settings_logo_save',
        'live_video_edit_form',
        'live_video_update',
        'system_settings_color_save',
        'zitsi_video_edit_form',
        'zitsi_live_video_update',
        'all_settings_view',
    ];

    /**
     * @var array<string, array{0: string, 1: list<string>, 2: string, 3: list<string>}>
     */
    private const ROUTES = [
        'about.view' => ['about_view', ['GET', 'HEAD'], 'about/page/view', ['auth', 'user', 'verified', 'prevent-back-history']],
        'policy.view' => ['policy_view', ['GET', 'HEAD'], 'policy/page/view', ['auth', 'user', 'verified', 'prevent-back-history']],
        'contact.view' => ['contact_view', ['GET', 'HEAD'], 'contact/us/view', []],
        'contact.send' => ['contact_send', ['POST'], 'contact/us/send', ['throttle:contact']],
        'term.view' => ['term_view', ['GET', 'HEAD'], 'term/condition/view', []],
        'all_settings.view' => ['all_settings_view', ['GET', 'HEAD'], 'settings/page/view', ['auth', 'user', 'verified', 'prevent-back-history']],
        'admin.about.page.data.view' => ['update_about_page_data', ['GET', 'HEAD'], 'admin/about/page/data', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.about.page.data.update' => ['update_about_page_data_update', ['POST'], 'admin/about/page/data/update/{id}', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.privacy.page.data.update' => ['update_privacy_page_data_update', ['POST'], 'admin/privacy/page/data/update/{id}', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.term.page.data.update' => ['update_term_page_data_update', ['POST'], 'admin/term/page/data/update/{id}', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.reported.post.view' => ['reported_post_to_admin', ['GET', 'HEAD'], 'admin/reported/post', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.reported.post.delete.by.admin' => ['reported_post_remove_by_admin', ['GET', 'HEAD'], 'admin/reported/post/delete/{id}', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.live-video.view' => ['live_video_edit_form', ['GET', 'HEAD'], 'admin/live-video/setting/view', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.live-video.update' => ['live_video_update', ['POST'], 'admin/live-video/setting/update', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.smtp.settings.view' => ['smtp_settings_view', ['GET', 'HEAD'], 'admin/smtp/setting/view', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.smtp.settings.view.save' => ['smtp_settings_save', ['POST'], 'admin/smtp/setting/save/{id}', ['auth', 'user', 'verified', 'admin', 'prevent-back-history']],
        'admin.system.settings.view' => ['system_settings_view', ['GET', 'HEAD'], 'admin/system/setting/view', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.system.settings.view.save' => ['system_settings_save', ['POST'], 'admin/system/setting/save', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.system.settings.logo.view.save' => ['system_settings_logo_save', ['POST'], 'admin/system/setting/logo/save', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.settings.amazon_s3' => ['amazon_s3', ['GET', 'HEAD'], 'admin/settings/amazon_s3', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.settings.amazon_s3.update' => ['amazon_s3_update', ['POST'], 'admin/settings/amazon_s3/update', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.system.settings.color.save' => ['system_settings_color_save', ['GET', 'HEAD'], 'admin/system/settings/color/save/{themeColor}', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.zitsi-video.view' => ['zitsi_video_edit_form', ['GET', 'HEAD'], 'admin/zitsi-video/setting/view', ['auth', 'verified', 'admin', 'prevent-back-history']],
        'admin.zitsi.live.settings.update' => ['zitsi_live_video_update', ['POST'], 'admin/jitsi/live/settings/update', ['auth', 'verified', 'admin', 'prevent-back-history']],
    ];

    private ?string $originalConfigJson = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configPath = base_path('config/config.json');
        $this->originalConfigJson = is_file($configPath) ? file_get_contents($configPath) : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalConfigJson !== null) {
            file_put_contents(base_path('config/config.json'), $this->originalConfigJson);
        }

        parent::tearDown();
    }

    public function test_requested_setting_controller_methods_stay_public(): void
    {
        $controller = new ReflectionClass(SettingController::class);

        foreach (self::METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "SettingController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "SettingController::{$method} should stay public.");
        }
    }

    public function test_setting_routes_keep_expected_actions_uris_methods_and_middleware(): void
    {
        foreach (self::ROUTES as $routeName => [$method, $verbs, $uri, $middleware]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame(SettingController::class.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");

            foreach ($middleware as $expectedMiddleware) {
                $this->assertContains($expectedMiddleware, $route->gatherMiddleware(), "Route [{$routeName}] should include [{$expectedMiddleware}].");
            }
        }
    }

    public function test_public_setting_pages_return_expected_views_and_data(): void
    {
        $this->seedSettingRows();
        $user = $this->activeUser();

        $this->actingAs($user);

        $about = $this->get(route('about.view'))->assertOk();
        $this->assertSame('frontend.settings.about', $about->viewData('view_path'));
        $this->assertSame('<p>About copy</p>', $about->viewData('about'));

        $policy = $this->get(route('policy.view'))->assertOk();
        $this->assertSame('frontend.settings.policy', $policy->viewData('view_path'));
        $this->assertSame('<p>Policy copy</p>', $policy->viewData('policy'));

        $allSettings = $this->get(route('all_settings.view'))->assertOk();
        $this->assertSame('frontend.settings.all_settings', $allSettings->viewData('view_path'));

        $term = $this->get(route('term.view'))->assertOk();
        $term->assertViewIs('frontend.settings.term');
        $this->assertSame('<p>Term copy</p>', $term->viewData('term'));

        $this->get(route('contact.view'))
            ->assertOk()
            ->assertViewIs('frontend.settings.contact');
    }

    public function test_contact_send_uses_admin_recipient(): void
    {
        Mail::fake();
        $this->activeUser([
            'email' => 'setting-admin@example.test',
            'user_role' => UserRole::Admin->value,
        ]);

        $this->post(route('contact.send'), [
            'name' => 'Setting Contact',
            'email' => 'setting-contact@example.test',
            'subject' => 'Settings question',
            'details' => 'Can settings contact send mail?',
        ])->assertRedirect();

        Mail::assertSent(ContactMail::class, fn (ContactMail $mail): bool => $mail->hasTo('setting-admin@example.test'));
    }

    public function test_admin_setting_pages_return_expected_view_data(): void
    {
        $this->seedSettingRows();
        Currency::factory()->usd()->create();
        Language::factory()->create(['name' => 'english']);
        $reporter = $this->activeUser(['name' => 'Reporter']);
        $post = $this->postFor($reporter, ['description' => 'Reported settings post']);
        $report = Report::factory()->create([
            'user_id' => $reporter->id,
            'post_id' => $post->post_id,
            'report' => 'Settings report reason',
            'status' => 0,
        ]);

        $this->actingAs($this->adminUser());

        $about = $this->get(route('admin.about.page.data.view'))->assertOk();
        $this->assertSame('setting.about', $about->viewData('view_path'));
        $this->assertSame('about', $about->viewData('about')->type);
        $this->assertSame('policy', $about->viewData('privacy')->type);
        $this->assertSame('term', $about->viewData('term')->type);

        $reports = $this->get(route('admin.reported.post.view'))->assertOk();
        $this->assertSame('reported_post.report', $reports->viewData('view_path'));
        $this->assertSame([$report->id], $reports->viewData('reported_post')->pluck('id')->all());

        $smtp = $this->get(route('admin.smtp.settings.view'))->assertOk();
        $this->assertSame('setting.smtp', $smtp->viewData('view_path'));
        $this->assertSame('smtp.test', $smtp->viewData('smptData')->smtp_host);

        $system = $this->get(route('admin.system.settings.view'))->assertOk();
        $this->assertSame('setting.system', $system->viewData('view_path'));
        $this->assertSame('Sociopro Settings', $system->viewData('system_name'));
        $this->assertSame('english', $system->viewData('system_language'));

        $s3 = $this->get(route('admin.settings.amazon_s3'))->assertOk();
        $this->assertSame('setting.amazon_s3_settings', $s3->viewData('view_path'));
        $this->assertSame('settings-bucket', $s3->viewData('amazon_s3_data')['AWS_BUCKET']);

        $live = $this->get(route('admin.live-video.view'))->assertOk();
        $this->assertSame('setting.live_video', $live->viewData('view_path'));

        $zitsi = $this->get(route('admin.zitsi-video.view'))->assertOk();
        $this->assertSame('setting.zitsi_live_settings', $zitsi->viewData('view_path'));
    }

    public function test_admin_setting_update_actions_mutate_expected_rows(): void
    {
        Storage::fake('public');
        $settings = $this->seedSettingRows();
        Currency::factory()->usd()->create();
        Language::factory()->create(['name' => 'english']);

        $this->actingAs($this->adminUser());

        $this->post(route('admin.about.page.data.update', $settings['about']->setting_id), [
            'about' => '<p>Updated about</p>',
        ])->assertRedirect();
        $this->assertSame('<p>Updated about</p>', $this->settingDescription('about'));

        $this->post(route('admin.privacy.page.data.update', $settings['policy']->setting_id), [
            'privacy' => '<p>Updated policy</p>',
        ])->assertRedirect();
        $this->assertSame('<p>Updated policy</p>', $this->settingDescription('policy'));

        $this->post(route('admin.term.page.data.update', $settings['term']->setting_id), [
            'term' => '<p>Updated term</p>',
        ])->assertRedirect();
        $this->assertSame('<p>Updated term</p>', $this->settingDescription('term'));

        $this->post(route('admin.smtp.settings.view.save', $settings['smtp']->setting_id), [
            'smtp_protocol' => 'smtp',
            'smtp_crypto' => 'tls',
            'smtp_host' => 'mail.updated.test',
            'smtp_port' => '2525',
            'smtp_user' => 'mailer',
            'smtp_pass' => 'secret',
        ])->assertRedirect();
        $smtpData = json_decode($this->settingDescription('smtp'), true);
        $this->assertSame('mail.updated.test', $smtpData['smtp_host']);
        $this->assertSame('2525', $smtpData['smtp_port']);

        $this->post(route('admin.system.settings.view.save'), [
            'system_name' => 'Updated System',
            'system_title' => 'Updated Title',
            'system_email' => 'updated-system@example.test',
            'system_phone' => '12345',
            'system_fax' => '67890',
            'system_address' => 'Updated Address',
            'system_footer' => 'Updated Footer',
            'system_footer_link' => 'https://example.test',
            'public_signup' => '0',
            'system_currency' => 'USD',
            'ad_charge_per_day' => '9.99',
            'google_analytics_id' => 'G-UPDATED',
            'meta_pixel_id' => 'PIXEL-UPDATED',
            'commission_rate' => '12',
            'system_language' => 'English',
        ])->assertRedirect();
        $this->assertSame('Updated System', $this->settingDescription('system_name'));
        $this->assertSame('updated-system@example.test', $this->settingDescription('system_email'));
        $this->assertSame('english', $this->settingDescription('system_language'));
        $this->assertSame('12', $this->settingDescription('commission_rate'));

        $this->post(route('admin.settings.amazon_s3.update'), [
            'active' => '1',
            'AWS_ACCESS_KEY_ID' => 'updated-key',
            'AWS_SECRET_ACCESS_KEY' => 'updated-secret',
            'AWS_DEFAULT_REGION' => 'eu-test-1',
            'AWS_BUCKET' => 'updated-bucket',
        ])->assertRedirect();
        $s3Data = json_decode($this->settingDescription('amazon_s3'), true);
        $this->assertSame('1', $s3Data['active']);
        $this->assertSame('updated-bucket', $s3Data['AWS_BUCKET']);
        $this->disableS3Uploads();

        $this->post(route('admin.system.settings.logo.view.save'), [
            'dark_logo' => UploadedFile::fake()->image('dark-logo.png', 120, 40),
            'light_logo' => UploadedFile::fake()->image('light-logo.png', 120, 40),
            'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
        ])->assertRedirect();
        Storage::disk('public')->assertExists('logo/dark/'.$this->settingDescription('system_dark_logo'));
        Storage::disk('public')->assertExists('logo/light/'.$this->settingDescription('system_light_logo'));
        Storage::disk('public')->assertExists('logo/favicon/'.$this->settingDescription('system_fav_icon'));

        $this->post(route('admin.live-video.update'), [
            'api_key' => 'updated-zoom-key',
            'api_secret' => 'updated-zoom-secret',
        ])->assertRedirect(route('admin.live-video.view'));
        $zoomData = json_decode($this->settingDescription('zoom_configuration'), true);
        $this->assertSame('updated-zoom-key', $zoomData['api_key']);
        $this->assertSame('updated-zoom-secret', $zoomData['api_secret']);

        $this->get(route('admin.system.settings.color.save', ['themeColor' => 'settings-purple']))->assertRedirect();
        $this->assertSame('settings-purple', $this->settingDescription('theme_color'));

        $this->post(route('admin.zitsi.live.settings.update'), [
            'account_email' => 'updated-zitsi@example.test',
            'jitsi_app_id' => 'updated-app',
            'jitsi_jwt' => 'updated-jwt',
        ])->assertRedirect(route('admin.zitsi-video.view'));
        $zitsiData = json_decode($this->settingDescription('zitsi_configuration'), true);
        $this->assertSame('updated-zitsi@example.test', $zitsiData['account_email']);
        $this->assertSame('updated-app', $zitsiData['jitsi_app_id']);
        $this->assertSame('updated-jwt', $zitsiData['jitsi_jwt']);
    }

    public function test_reported_post_remove_marks_post_and_report_inactive(): void
    {
        $this->seedSettingRows();
        $reporter = $this->activeUser();
        $post = $this->postFor($reporter);
        $post->forceFill(['report_status' => 0])->save();
        Report::factory()->create([
            'user_id' => $reporter->id,
            'post_id' => $post->post_id,
            'status' => 0,
        ]);

        $this->actingAs($this->adminUser());

        $this->get(route('admin.reported.post.delete.by.admin', $post->post_id))->assertRedirect();
        $this->assertDatabaseHas('posts', ['post_id' => $post->post_id, 'report_status' => 1]);
        $this->assertDatabaseHas('reports', ['post_id' => $post->post_id, 'status' => 1]);
    }

    private function adminUser(array $overrides = []): User
    {
        return $this->activeUser(array_merge([
            'name' => 'Settings Admin',
            'email' => 'settings-admin@example.test',
            'user_role' => UserRole::Admin->value,
        ], $overrides));
    }

    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ], $overrides));
    }

    private function postFor(User $user, array $overrides = []): Posts
    {
        return Posts::factory()->forOwner($user)->create(array_merge([
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => PostType::General->value,
            'privacy' => Visibility::Public->value,
            'tagged_user_ids' => json_encode([]),
            'activity_id' => 0,
            'description' => 'Settings reported post',
            'user_reacts' => json_encode([]),
            'status' => ContentStatus::Active->value,
        ], $overrides));
    }

    private function settingDescription(string $type): string
    {
        return (string) Setting::query()
            ->where('type', $type)
            ->value('description');
    }

    private function disableS3Uploads(): void
    {
        Setting::query()
            ->where('type', 'amazon_s3')
            ->update([
                'description' => json_encode([
                    'active' => 0,
                    'AWS_ACCESS_KEY_ID' => 'settings-key',
                    'AWS_SECRET_ACCESS_KEY' => 'settings-secret',
                    'AWS_DEFAULT_REGION' => 'us-test-1',
                    'AWS_BUCKET' => 'settings-bucket',
                ]),
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array<string, Setting>
     */
    private function seedSettingRows(): array
    {
        $settings = [
            'about' => '<p>About copy</p>',
            'policy' => '<p>Policy copy</p>',
            'term' => '<p>Term copy</p>',
            'smtp' => json_encode([
                'smtp_protocol' => 'smtp',
                'smtp_crypto' => 'tls',
                'smtp_host' => 'smtp.test',
                'smtp_port' => '587',
                'smtp_user' => 'user',
                'smtp_pass' => 'pass',
            ]),
            'system_name' => 'Sociopro Settings',
            'system_title' => 'Settings Title',
            'system_email' => 'settings@example.test',
            'system_phone' => '555',
            'system_fax' => '556',
            'system_address' => 'Settings Address',
            'system_footer' => 'Settings Footer',
            'system_footer_link' => 'https://settings.example.test',
            'system_dark_logo' => 'dark.png',
            'system_light_logo' => 'light.png',
            'system_fav_icon' => 'favicon.png',
            'google_analytics_id' => 'G-TEST',
            'meta_pixel_id' => 'PIXEL-TEST',
            'commission_rate' => '10',
            'system_currency' => 'USD',
            'system_language' => 'english',
            'public_signup' => '1',
            'theme_color' => 'default',
            'ad_charge_per_day' => '5',
            'amazon_s3' => json_encode([
                'active' => 0,
                'AWS_ACCESS_KEY_ID' => 'settings-key',
                'AWS_SECRET_ACCESS_KEY' => 'settings-secret',
                'AWS_DEFAULT_REGION' => 'us-test-1',
                'AWS_BUCKET' => 'settings-bucket',
            ]),
            'zoom_configuration' => json_encode([
                'api_key' => 'zoom-key',
                'api_secret' => 'zoom-secret',
            ]),
            'zitsi_configuration' => json_encode([
                'account_email' => 'zitsi@example.test',
                'jitsi_app_id' => 'zitsi-app',
                'jitsi_jwt' => 'zitsi-jwt',
            ]),
        ];

        $models = [];
        foreach ($settings as $type => $description) {
            $updated = Setting::query()
                ->where('type', $type)
                ->update([
                    'description' => $description,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                $setting = new Setting;
                $setting->forceFill([
                    'type' => $type,
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->save();
            }

            $models[$type] = Setting::query()->where('type', $type)->firstOrFail();
        }

        return $models;
    }
}
