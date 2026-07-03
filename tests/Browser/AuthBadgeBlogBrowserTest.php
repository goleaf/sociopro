<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthBadgeBlogBrowserTest extends DuskTestCase
{
    private const USER_EMAILS = [
        'dusk-auth-browser@example.test',
        'dusk-blog-browser@example.test',
        'dusk-badge-browser@example.test',
    ];

    private const BLOG_TITLES = [
        'Dusk Browser Created Blog',
        'Dusk Browser Updated Blog',
    ];

    private const BLOG_CATEGORY_NAMES = [
        'Dusk Browser Blog Category',
    ];

    /**
     * @var array<string, mixed>
     */
    private array $originalSettings = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalSettings = Setting::query()
            ->whereIn('type', ['public_signup', 'badge_price'])
            ->pluck('description', 'type')
            ->all();

        $this->deleteFixtures();

        Setting::query()->where('type', 'public_signup')->update(['description' => '1']);
        Setting::query()->where('type', 'badge_price')->update(['description' => '29']);
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        foreach ($this->originalSettings as $type => $description) {
            Setting::query()->where('type', $type)->update(['description' => $description]);
        }

        parent::tearDown();
    }

    public function test_auth_session_pages_confirm_password_and_logout_in_browser(): void
    {
        $user = $this->activeUser('dusk-auth-browser@example.test', 'Dusk Auth Browser');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                ->assertSee('Login')
                ->type('email', $user->email)
                ->type('password', 'password')
                ->press('Log In')
                ->waitForLocation('/')
                ->assertPathIs('/')
                ->visit('/confirm-password')
                ->assertSee('Please confirm your password before continuing')
                ->type('password', 'password')
                ->press('Confirm')
                ->waitForLocation('/')
                ->assertPathIs('/');

            $this->submitLogoutForm($browser);

            $browser->waitForLocation('/login')
                ->assertSee('Login');
        });
    }

    public function test_blog_pages_create_update_search_scroll_and_delete_in_browser(): void
    {
        $user = $this->activeUser('dusk-blog-browser@example.test', 'Dusk Blog Browser');
        $category = $this->blogCategory();

        $this->browse(function (Browser $browser) use ($user, $category) {
            $browser->loginAs($user)
                ->visit('/blogs')
                ->assertSee('Blogs')
                ->assertSee('Create Blog')
                ->visit('/create/blog')
                ->assertSee('Create New Blog')
                ->type('title', 'Dusk Browser Created Blog')
                ->select('category', (string) $category->id);

            $this->setBlogDescription($browser, 'Dusk browser created description.');

            $browser->press('Create Post')
                ->waitForLocation('/blogs')
                ->assertSee('Dusk Browser Created Blog');

            $blog = Blog::query()
                ->where('title', 'Dusk Browser Created Blog')
                ->firstOrFail();

            $browser->visit('/my/blog')
                ->assertSee('Dusk Browser Created Blog')
                ->visit('/edit/blog/'.$blog->id)
                ->assertSee('Edit Blog')
                ->type('title', 'Dusk Browser Updated Blog')
                ->select('category', (string) $category->id);

            $this->setBlogDescription($browser, 'Dusk browser updated description.');

            $browser->press('Update Post')
                ->waitForLocation('/my/blog')
                ->assertSee('Dusk Browser Updated Blog');

            $blog->refresh();

            $browser->visit('/blog/view/'.$blog->id)
                ->assertSee('Dusk Browser Updated Blog')
                ->visit('/blog/category/'.$category->id)
                ->assertSee('Dusk Browser Updated Blog')
                ->visit('/load_blog_by_scrolling?offset=0')
                ->assertSee('Dusk Browser Updated Blog')
                ->visit('/blog/search?search=Dusk%20Browser')
                ->assertSourceHas('Dusk Browser Updated Blog')
                ->visit('/blog/delete?blog_id='.$blog->id)
                ->assertSee('Blog Deleted Successfully');
        });

        $this->assertDatabaseMissing('blogs', ['title' => 'Dusk Browser Updated Blog']);
    }

    public function test_badge_checkout_submit_reaches_payment_summary_in_browser(): void
    {
        $user = $this->activeUser('dusk-badge-browser@example.test', 'Dusk Badge Browser');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/badge/info')
                ->assertSee('Confirm and pay')
                ->assertSee('Dusk Badge Browser')
                ->assertSee('29$')
                ->press('Pay Now')
                ->waitForLocation('/payment')
                ->assertPathIs('/payment')
                ->assertSee('Order summary');
        });
    }

    private function submitLogoutForm(Browser $browser): void
    {
        $browser->script(<<<'JS'
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = document.querySelector('meta[name="csrf_token"]').content;

            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        JS);
    }

    private function setBlogDescription(Browser $browser, string $description): void
    {
        $encodedDescription = json_encode($description, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            document.querySelector('textarea[name="description"]').value = {$encodedDescription};
        JS);
    }

    private function activeUser(string $email, string $name): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'username' => str_replace(['@', '.'], '-', $email),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
        $user->save();

        return $user;
    }

    private function blogCategory(): BlogCategory
    {
        $category = BlogCategory::query()
            ->where('name', self::BLOG_CATEGORY_NAMES[0])
            ->first() ?? new BlogCategory;
        $category->name = self::BLOG_CATEGORY_NAMES[0];
        $category->save();

        return $category;
    }

    private function deleteFixtures(): void
    {
        Blog::query()->whereIn('title', self::BLOG_TITLES)->delete();

        $userIds = User::query()
            ->whereIn('email', self::USER_EMAILS)
            ->pluck('id');

        if ($userIds->isNotEmpty()) {
            Blog::query()->whereIn('user_id', $userIds)->delete();
        }

        BlogCategory::query()->whereIn('name', self::BLOG_CATEGORY_NAMES)->delete();
        User::query()->whereIn('email', self::USER_EMAILS)->delete();
    }
}
