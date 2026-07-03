<?php

namespace Tests\Browser;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\VideoCategory;
use App\Enums\Visibility;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Event;
use App\Models\Group;
use App\Models\Marketplace;
use App\Models\Page;
use App\Models\Posts;
use App\Models\User;
use App\Models\Video;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ReportSearchControllerBrowserTest extends DuskTestCase
{
    private const EMAILS = [
        'dusk-search-owner@example.test',
        'dusk-search-person@example.test',
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

    public function test_report_search_pages_render_in_browser(): void
    {
        $owner = $this->activeUser('dusk-search-owner@example.test', 'Dusk Search Owner');
        $this->activeUser('dusk-search-person@example.test', 'DuskFind Person');
        $this->postFor($owner, 'DuskFind post body', 'DuskFind Place');
        Marketplace::factory()->forOwner($owner)->forCategory($this->category())->forBrand($this->brand())->forCurrency($this->currency())->create([
            'title' => 'DuskFind Product',
            'location' => 'DuskFind Market',
            'status' => '1',
        ]);
        Page::factory()->forOwner($owner)->create(['title' => 'DuskFind Page']);
        Group::factory()->create([
            'user_id' => $owner->id,
            'title' => 'DuskFind Group',
            'privacy' => Visibility::Public->value,
        ]);
        Event::factory()->forOwner($owner)->create([
            'title' => 'DuskFind Event',
            'location' => 'DuskFind Venue',
            'privacy' => Visibility::Public->value,
        ]);
        $this->videoFor($owner, 'DuskFind Video');

        $this->browse(function (Browser $browser) use ($owner) {
            $browser->loginAs($owner)
                ->visit('/search?search=DuskFind')
                ->assertPathIs('/search')
                ->assertSee('Search Results')
                ->assertSee('DuskFind Person')
                ->assertSee('DuskFind Product')
                ->assertSee('DuskFind Page')
                ->assertSee('DuskFind Group')
                ->assertSee('DuskFind Event')
                ->assertSee('DuskFind Video')
                ->assertSee('DuskFind Place')
                ->visit('/search/people?search=DuskFind')
                ->assertPathIs('/search/people')
                ->assertSee('People')
                ->assertSee('DuskFind Person')
                ->visit('/search/post?search=DuskFind')
                ->assertPathIs('/search/post')
                ->assertSee('Posts')
                ->assertSee('DuskFind Place')
                ->visit('/search/video?search=DuskFind')
                ->assertPathIs('/search/video')
                ->assertSee('Videos')
                ->assertSee('DuskFind Video')
                ->visit('/search/product?search=DuskFind')
                ->assertPathIs('/search/product')
                ->assertSee('Marketplace')
                ->assertSee('DuskFind Product')
                ->visit('/search/page?search=DuskFind')
                ->assertPathIs('/search/page')
                ->assertSee('Pages')
                ->assertSee('DuskFind')
                ->visit('/search/group?search=DuskFind')
                ->assertPathIs('/search/group')
                ->assertSee('Groups')
                ->assertSee('DuskFind Group')
                ->visit('/search/event?search=DuskFind')
                ->assertPathIs('/search/event')
                ->assertSee('Events')
                ->assertSee('DuskFind Event');
        });
    }

    private function activeUser(string $email, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'username' => str($email)->before('@')->replace('.', '-')->toString(),
            'phone' => '1666'.random_int(100000, 999999),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
            'about' => 'Dusk search bio',
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
            'user_reacts' => json_encode([]),
            'status' => ContentStatus::Active->value,
        ]);
    }

    private function videoFor(User $user, string $title): Video
    {
        $video = Video::factory()->forOwner($user)->create([
            'title' => $title,
            'category' => VideoCategory::Video->value,
            'privacy' => Visibility::Public->value,
            'file' => 'dusk-search-video.mp4',
            'view' => json_encode([]),
        ]);

        Posts::factory()->forOwner($user)->create([
            'publisher' => 'video_and_shorts',
            'publisher_id' => $video->id,
            'post_type' => VideoCategory::Video->value,
            'privacy' => Visibility::Public->value,
            'description' => $title,
            'tagged_user_ids' => json_encode([]),
            'activity_id' => 0,
            'user_reacts' => json_encode([]),
            'status' => ContentStatus::Active->value,
        ]);

        return $video;
    }

    private function category(): Category
    {
        return Category::factory()->create(['name' => 'DuskFind Category']);
    }

    private function brand(): Brand
    {
        return Brand::factory()->create(['name' => 'DuskFind Brand']);
    }

    private function currency(): Currency
    {
        return Currency::factory()->create([
            'name' => 'DuskFind Currency',
            'code' => 'DSF',
            'symbol' => 'DSF',
            'paypal_supported' => true,
            'stripe_supported' => true,
        ]);
    }

    private function deleteFixtures(): void
    {
        $userIds = User::query()
            ->whereIn('email', self::EMAILS)
            ->pluck('id');

        Posts::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('description', 'like', 'DuskFind%')
            ->orWhere('location', 'like', 'DuskFind%')
            ->delete();

        Video::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('title', 'like', 'DuskFind%')
            ->delete();

        Marketplace::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('title', 'like', 'DuskFind%')
            ->delete();

        Page::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('title', 'like', 'DuskFind%')
            ->delete();

        Group::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('title', 'like', 'DuskFind%')
            ->delete();

        Event::query()
            ->whereIn('user_id', $userIds)
            ->orWhere('title', 'like', 'DuskFind%')
            ->delete();

        User::query()
            ->whereIn('email', self::EMAILS)
            ->delete();

        Category::query()->where('name', 'DuskFind Category')->delete();
        Brand::query()->where('name', 'DuskFind Brand')->delete();
        Currency::query()->where('code', 'DSF')->delete();
    }
}
