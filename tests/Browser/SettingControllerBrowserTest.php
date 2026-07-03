<?php

namespace Tests\Browser;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Posts;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SettingControllerBrowserTest extends DuskTestCase
{
    private const EMAILS = [
        'dusk-settings-admin@example.test',
        'dusk-settings-reporter@example.test',
    ];

    /**
     * @var array<string, string|null>
     */
    private array $originalSettings = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFixtures();
        $this->seedRuntimeSettings();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();
        $this->restoreSettings();

        parent::tearDown();
    }

    public function test_setting_pages_render_in_browser(): void
    {
        $admin = $this->activeUser('dusk-settings-admin@example.test', 'Dusk Settings Admin', UserRole::Admin);
        $reporter = $this->activeUser('dusk-settings-reporter@example.test', 'Dusk Settings Reporter', UserRole::General);
        $post = $this->postFor($reporter, 'Dusk Settings reported post');
        Report::factory()->create([
            'user_id' => $reporter->id,
            'post_id' => $post->post_id,
            'report' => 'Dusk Settings report reason',
            'status' => 0,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->visit('/contact/us/view')
                ->assertPathIs('/contact/us/view')
                ->assertSee('Contact Us')
                ->assertSee('Full Name')
                ->visit('/term/condition/view')
                ->assertPathIs('/term/condition/view')
                ->assertSee('Dusk Settings Term')
                ->loginAs($admin)
                ->visit('/about/page/view')
                ->assertPathIs('/about/page/view')
                ->assertSee('Dusk Settings About')
                ->visit('/policy/page/view')
                ->assertPathIs('/policy/page/view')
                ->assertSee('Dusk Settings Policy')
                ->visit('/settings/page/view')
                ->assertPathIs('/settings/page/view')
                ->assertSee('Account Deactivate')
                ->visit('/admin/about/page/data')
                ->assertPathIs('/admin/about/page/data')
                ->assertSee('Update Custom Pages Information')
                ->assertSee('Dusk Settings About')
                ->visit('/admin/reported/post')
                ->assertPathIs('/admin/reported/post')
                ->assertSee('All Reported Post List')
                ->assertSee('Dusk Settings report reason')
                ->visit('/admin/smtp/setting/view')
                ->assertPathIs('/admin/smtp/setting/view')
                ->assertSee('Update SMTP Information')
                ->visit('/admin/system/setting/view')
                ->assertPathIs('/admin/system/setting/view')
                ->assertSee('System Title')
                ->visit('/admin/settings/amazon_s3')
                ->assertPathIs('/admin/settings/amazon_s3')
                ->assertSee('Configure amazon s3 settings')
                ->visit('/admin/live-video/setting/view')
                ->assertPathIs('/admin/live-video/setting/view')
                ->assertSee('Update zoom api keys')
                ->visit('/admin/zitsi-video/setting/view')
                ->assertPathIs('/admin/zitsi-video/setting/view')
                ->assertSee('Update Zitsi Api keys');
        });
    }

    private function activeUser(string $email, string $name, UserRole $role): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'username' => str($email)->before('@')->replace('.', '-')->toString(),
            'phone' => '1777'.random_int(100000, 999999),
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
            'about' => 'Dusk settings bio',
        ]);
    }

    private function postFor(User $user, string $description): Posts
    {
        return Posts::factory()->forOwner($user)->create([
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => PostType::General->value,
            'privacy' => Visibility::Public->value,
            'tagged_user_ids' => json_encode([]),
            'activity_id' => 0,
            'description' => $description,
            'user_reacts' => json_encode([]),
            'status' => ContentStatus::Active->value,
        ]);
    }

    private function seedRuntimeSettings(): void
    {
        foreach ($this->settingsPayload() as $type => $description) {
            $this->upsertSetting($type, $description);
        }

        $this->ensureCurrency();
        $this->ensureLanguage();
    }

    /**
     * @return array<string, string>
     */
    private function settingsPayload(): array
    {
        return [
            'about' => '<p>Dusk Settings About</p>',
            'policy' => '<p>Dusk Settings Policy</p>',
            'term' => '<p>Dusk Settings Term</p>',
            'smtp' => json_encode([
                'smtp_protocol' => 'smtp',
                'smtp_crypto' => 'tls',
                'smtp_host' => 'smtp.dusk.test',
                'smtp_port' => '587',
                'smtp_user' => 'dusk',
                'smtp_pass' => 'secret',
            ]),
            'system_name' => 'Dusk Settings App',
            'system_title' => 'Dusk Settings Title',
            'system_email' => 'dusk-settings@example.test',
            'system_phone' => '555',
            'system_fax' => '556',
            'system_address' => 'Dusk Address',
            'system_footer' => 'Dusk Footer',
            'system_footer_link' => 'https://dusk.example.test',
            'system_dark_logo' => '',
            'system_light_logo' => '',
            'system_fav_icon' => '',
            'google_analytics_id' => '',
            'meta_pixel_id' => '',
            'commission_rate' => '10',
            'system_currency' => 'USD',
            'system_language' => 'english',
            'public_signup' => '1',
            'theme_color' => 'default',
            'ad_charge_per_day' => '5',
            'amazon_s3' => json_encode([
                'active' => 0,
                'AWS_ACCESS_KEY_ID' => 'dusk-key',
                'AWS_SECRET_ACCESS_KEY' => 'dusk-secret',
                'AWS_DEFAULT_REGION' => 'us-dusk-1',
                'AWS_BUCKET' => 'dusk-bucket',
            ]),
            'zoom_configuration' => json_encode([
                'api_key' => 'dusk-zoom-key',
                'api_secret' => 'dusk-zoom-secret',
            ]),
            'zitsi_configuration' => json_encode([
                'account_email' => 'dusk-zitsi@example.test',
                'jitsi_app_id' => 'dusk-app',
                'jitsi_jwt' => 'dusk-jwt',
            ]),
        ];
    }

    private function upsertSetting(string $type, string $description): void
    {
        if (! array_key_exists($type, $this->originalSettings)) {
            $this->originalSettings[$type] = Setting::query()->where('type', $type)->value('description');
        }

        $updated = Setting::query()
            ->where('type', $type)
            ->update([
                'description' => $description,
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            return;
        }

        $setting = new Setting;
        $setting->forceFill([
            'type' => $type,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();
    }

    private function ensureCurrency(): void
    {
        if (Currency::query()->where('code', 'USD')->exists()) {
            return;
        }

        $currency = new Currency;
        $currency->forceFill([
            'name' => 'Dollars',
            'code' => 'USD',
            'symbol' => '$',
            'paypal_supported' => true,
            'stripe_supported' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();
    }

    private function ensureLanguage(): void
    {
        if (Language::query()
            ->where('name', 'english')
            ->where('phrase', 'dusk_settings_phrase')
            ->exists()) {
            return;
        }

        $language = new Language;
        $language->forceFill([
            'name' => 'english',
            'phrase' => 'dusk_settings_phrase',
            'translated' => 'Dusk settings phrase',
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();
    }

    private function restoreSettings(): void
    {
        foreach ($this->originalSettings as $type => $description) {
            if ($description === null) {
                Setting::query()->where('type', $type)->delete();

                continue;
            }

            $this->upsertSetting($type, $description);
        }
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', self::EMAILS)
            ->pluck('id');

        Report::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('report', 'like', 'Dusk Settings%')
            ->delete();

        Posts::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('description', 'like', 'Dusk Settings%')
            ->delete();

        User::query()
            ->whereIn('email', self::EMAILS)
            ->delete();
    }
}
