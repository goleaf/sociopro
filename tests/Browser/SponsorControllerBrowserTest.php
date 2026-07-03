<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SponsorControllerBrowserTest extends DuskTestCase
{
    private const ADMIN_EMAIL = 'dusk-sponsor-admin@example.test';

    private ?string $originalAmazonS3 = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFixtures();
        $this->disableS3Uploads();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();
        $this->restoreS3Setting();

        parent::tearDown();
    }

    public function test_admin_can_use_sponsor_buttons_and_forms(): void
    {
        $admin = $this->fixtureAdmin();
        $imagePath = public_path('storage/images/happy.png');

        $this->browse(function (Browser $browser) use ($admin, $imagePath) {
            $browser->loginAs($admin)
                ->visitRoute('admin.view.sponsor')
                ->assertSee('All Sponsors')
                ->clickLink('Create')
                ->assertPathIs('/admin/sponsor/create')
                ->assertSee('Add Sponsors Post')
                ->type('name', 'Dusk Sponsor Created')
                ->type('ext_url', 'https://example.test/dusk-sponsor')
                ->attach('image', $imagePath)
                ->script(<<<'JS'
                    const description = document.getElementById('description');
                    description.value = 'Created by SponsorController Dusk test';
                    description.dispatchEvent(new Event('input', { bubbles: true }));
                    description.dispatchEvent(new Event('change', { bubbles: true }));
                JS);

            $browser->press('Submit')
                ->waitForText('Add Sponsors Post', 5);

            $sponsor = Sponsor::query()->where('name', 'Dusk Sponsor Created')->firstOrFail();

            $browser->visitRoute('admin.view.sponsor')
                ->assertSee('Dusk Sponsor Created')
                ->press('Actions')
                ->clickLink('Edit')
                ->assertPathIs('/admin/sponsor/edit/'.$sponsor->id)
                ->assertSee('Edit Sponsor Post')
                ->type('name', 'Dusk Sponsor Updated')
                ->type('ext_url', 'https://example.test/dusk-sponsor-updated')
                ->script(<<<'JS'
                    const endDate = document.getElementById('end_date');
                    endDate.value = '2026-07-10T12:00';
                    endDate.dispatchEvent(new Event('input', { bubbles: true }));
                    endDate.dispatchEvent(new Event('change', { bubbles: true }));
                JS);

            $browser->script(<<<'JS'
                    const description = document.getElementById('description');
                    description.value = 'Updated by SponsorController Dusk test';
                    description.dispatchEvent(new Event('input', { bubbles: true }));
                    description.dispatchEvent(new Event('change', { bubbles: true }));
                JS);

            $browser->select('status', '0')
                ->press('Submit')
                ->waitForLocation('/admin/sponsor/view', 5)
                ->assertSee('Dusk Sponsor Updated')
                ->script('window.confirm = () => true;');

            $browser->press('Actions')
                ->clickLink('Delete')
                ->waitForLocation('/admin/sponsor/view', 5);
        });

        $this->assertDatabaseMissing('sponsors', [
            'name' => 'Dusk Sponsor Updated',
        ]);
    }

    private function fixtureAdmin(): User
    {
        $user = User::query()->where('email', self::ADMIN_EMAIL)->first() ?? new User;
        $user->forceFill([
            'name' => 'Dusk Sponsor Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => 'dusk-sponsor-admin',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'profile_status' => 'unlock',
        ])->save();

        return $user;
    }

    private function disableS3Uploads(): void
    {
        $this->originalAmazonS3 = Setting::query()->where('type', 'amazon_s3')->value('description');

        $updated = Setting::query()
            ->where('type', 'amazon_s3')
            ->update([
                'description' => json_encode([
                    'active' => 0,
                    'AWS_ACCESS_KEY_ID' => 'dusk-key',
                    'AWS_SECRET_ACCESS_KEY' => 'dusk-secret',
                    'AWS_DEFAULT_REGION' => 'us-dusk-1',
                    'AWS_BUCKET' => 'dusk-bucket',
                ]),
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            return;
        }

        $setting = new Setting;
        $setting->forceFill([
            'type' => 'amazon_s3',
            'description' => json_encode([
                'active' => 0,
                'AWS_ACCESS_KEY_ID' => 'dusk-key',
                'AWS_SECRET_ACCESS_KEY' => 'dusk-secret',
                'AWS_DEFAULT_REGION' => 'us-dusk-1',
                'AWS_BUCKET' => 'dusk-bucket',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();
    }

    private function restoreS3Setting(): void
    {
        if ($this->originalAmazonS3 === null) {
            Setting::query()->where('type', 'amazon_s3')->delete();

            return;
        }

        Setting::query()
            ->where('type', 'amazon_s3')
            ->update([
                'description' => $this->originalAmazonS3,
                'updated_at' => now(),
            ]);
    }

    private function deleteFixtures(): void
    {
        Sponsor::query()
            ->where('name', 'like', 'Dusk Sponsor%')
            ->get()
            ->each(function (Sponsor $sponsor): void {
                if ($sponsor->image) {
                    File::delete(public_path('storage/sponsor/thumbnail/'.$sponsor->image));
                }

                $sponsor->delete();
            });

        User::query()->where('email', self::ADMIN_EMAIL)->delete();
    }
}
