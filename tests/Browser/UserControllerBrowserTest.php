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

class UserControllerBrowserTest extends DuskTestCase
{
    private const USER_EMAIL = 'dusk-user-controller@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFixtures();
        $this->disableS3Uploads();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        parent::tearDown();
    }

    public function test_user_can_use_ad_buttons_and_forms_in_browser(): void
    {
        $user = $this->fixtureUser();
        $imagePath = public_path('storage/images/happy.png');

        $this->browse(function (Browser $browser) use ($user, $imagePath) {
            $browser->loginAs($user)
                ->visitRoute('user.ads')
                ->assertSee('Your ads')
                ->clickLink('Create a new Ad')
                ->assertPathIs('/user/ad/create')
                ->assertSee('Create your new Ad')
                ->type('name', 'Dusk User Ad Created')
                ->type('ext_url', 'https://example.test/dusk-user-ad')
                ->attach('image', $imagePath)
                ->script(<<<'JS'
                    const description = document.getElementById('description');
                    description.value = 'Created by UserController Dusk test';
                    description.dispatchEvent(new Event('input', { bubbles: true }));
                    description.dispatchEvent(new Event('change', { bubbles: true }));
                JS);

            $browser->press('Submit')
                ->waitForLocation('/user/ads', 5)
                ->assertSee('Dusk User Ad Created');

            $ad = Sponsor::query()->where('name', 'Dusk User Ad Created')->firstOrFail();

            $browser->press('Actions')
                ->clickLink('Edit')
                ->assertPathIs('/user/ad/edit/'.$ad->id)
                ->assertSee('Edit your Ad')
                ->type('name', 'Dusk User Ad Updated')
                ->type('ext_url', 'https://example.test/dusk-user-ad-updated')
                ->script(<<<'JS'
                    const description = document.getElementById('description');
                    description.value = 'Updated by UserController Dusk test';
                    description.dispatchEvent(new Event('input', { bubbles: true }));
                    description.dispatchEvent(new Event('change', { bubbles: true }));
                JS);

            $browser->press('Submit')
                ->waitForLocation('/user/ads', 5)
                ->assertSee('Dusk User Ad Updated')
                ->script('window.confirm = () => true;');

            $browser->press('Actions')
                ->clickLink('Delete')
                ->waitForLocation('/user/ads', 5)
                ->assertDontSee('Dusk User Ad Updated');
        });

        $this->assertDatabaseMissing('sponsors', [
            'name' => 'Dusk User Ad Updated',
        ]);
    }

    private function fixtureUser(): User
    {
        $user = User::query()->where('email', self::USER_EMAIL)->first() ?? new User;
        $user->forceFill([
            'name' => 'Dusk User Controller',
            'email' => self::USER_EMAIL,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => 'dusk-user-controller',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'profile_status' => 'unlock',
        ])->save();

        return $user;
    }

    private function disableS3Uploads(): void
    {
        $values = [
            'description' => json_encode([
                'active' => 0,
                'AWS_ACCESS_KEY_ID' => 'dusk-key',
                'AWS_SECRET_ACCESS_KEY' => 'dusk-secret',
                'AWS_DEFAULT_REGION' => 'us-dusk-1',
                'AWS_BUCKET' => 'dusk-bucket',
            ]),
            'updated_at' => now(),
        ];

        if (Setting::query()->where('type', 'amazon_s3')->update($values) === 0) {
            (new Setting)->forceFill($values + ['type' => 'amazon_s3'])->save();
        }
    }

    private function deleteFixtures(): void
    {
        Sponsor::query()
            ->where('name', 'like', 'Dusk User Ad%')
            ->get()
            ->each(function (Sponsor $sponsor): void {
                if ($sponsor->image) {
                    File::delete(public_path('storage/sponsor/thumbnail/'.$sponsor->image));
                    File::delete(public_path('storage/sponsor/thumbnail/optimized/'.$sponsor->image));
                }

                $sponsor->delete();
            });

        User::query()->where('email', self::USER_EMAIL)->delete();
    }
}
