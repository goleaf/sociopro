<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\MediaFile;
use App\Models\Stories;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StoryControllerBrowserTest extends DuskTestCase
{
    private const EMAIL = 'dusk-story-user@example.test';

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

    public function test_story_partials_render_and_text_story_can_be_created_in_browser(): void
    {
        $user = $this->fixtureUser();
        $story = Stories::factory()->forUser($user)->text('Dusk Browser Visible Story')->create([
            'privacy' => Visibility::Public->value,
        ]);

        $this->browse(function (Browser $browser) use ($story, $user) {
            $browser->loginAs($user)
                ->visitRoute('stories')
                ->assertSee('Dusk Browser Visible Story')
                ->visitRoute('story_details', $story->story_id)
                ->assertSee('Dusk Browser Visible Story')
                ->assertSee('Dusk Story User')
                ->visitRoute('single_story_details', $story->story_id)
                ->assertSee('Dusk Browser Visible Story')
                ->visitRoute('load_modal_content', 'frontend.story.create_story')
                ->assertSee('Create a Text Story')
                ->type('description', 'Dusk Browser Created Story')
                ->script(<<<'JS'
                    document.querySelector('.text-story-form').submit();
                JS);

            $browser->waitForLocation('/', 5);
        });

        $this->assertDatabaseHas('stories', [
            'user_id' => $user->id,
            'publisher' => 'user',
            'content_type' => 'text',
            'privacy' => Visibility::Public->value,
            'status' => 'active',
        ]);
        $this->assertTrue(
            Stories::query()->where('description', 'like', '%Dusk Browser Created Story%')->exists()
        );
    }

    private function fixtureUser(): User
    {
        $user = User::query()->where('email', self::EMAIL)->first() ?? new User;
        $user->forceFill([
            'name' => 'Dusk Story User',
            'email' => self::EMAIL,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => 'dusk-story-user',
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

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->where('email', self::EMAIL)
            ->pluck('id');

        $storyIds = Stories::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('description', 'like', '%Dusk Browser%')
            ->pluck('story_id');

        if ($storyIds->isNotEmpty()) {
            MediaFile::query()->whereIn('story_id', $storyIds)->delete();
            Stories::query()->whereIn('story_id', $storyIds)->delete();
        }

        User::query()->whereIn('id', $userIds)->delete();
    }
}
