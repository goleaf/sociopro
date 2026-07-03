<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthBadgeBlogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_auth_checker_returns_boolean_contract_for_guest_and_authenticated_user(): void
    {
        $this->get(route('auth-checker'))
            ->assertOk()
            ->assertContent('');

        $this->actingAs($this->activeUser())
            ->get(route('auth-checker'))
            ->assertOk()
            ->assertContent('1');
    }

    public function test_badge_payment_configuration_validates_payload_and_does_not_prepare_payment_session(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->from(route('badge.info'))
            ->post(route('badge.payment_configuration', 9), [
                'title' => '',
                'description' => '',
                'start_date' => 'not-a-date',
            ])
            ->assertRedirect(route('badge.info'))
            ->assertSessionHasErrors(['title', 'description', 'start_date']);

        $this->assertNull(session('payment_details'));
    }

    public function test_badge_payment_configuration_stores_payment_details_with_requested_dates(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-03 14:15:16', config('app.timezone')));
        Setting::query()->where('type', 'badge_price')->update(['description' => '23']);
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post(route('badge.payment_configuration', 42), [
                'title' => 'Verified profile',
                'description' => 'Badge checkout description',
                'start_date' => '2026-08-10',
            ])
            ->assertRedirect(route('payment'));

        $paymentDetails = session('payment_details');

        $this->assertIsArray($paymentDetails);
        $this->assertSame(42, $paymentDetails['items'][0]['id']);
        $this->assertSame('Verified profile', $paymentDetails['items'][0]['title']);
        $this->assertSame('Badge checkout description', $paymentDetails['items'][0]['subtitle']);
        $this->assertSame('23', $paymentDetails['items'][0]['price']);
        $this->assertSame('23', $paymentDetails['payable_amount']);
        $this->assertSame('Badge', $paymentDetails['success_method']['model_name']);
        $this->assertSame('add_payment_success', $paymentDetails['success_method']['function_name']);
        $this->assertSame($user->id, $paymentDetails['custom_field']['user_id']);
        $this->assertSame('2026-08-10 14:15:16', $paymentDetails['custom_field']['start_date']);
        $this->assertSame('2026-09-09 14:15:16', $paymentDetails['custom_field']['end_date']);
        $this->assertSame(route('badge'), $paymentDetails['cancel_url']);
        $this->assertSame(url('/payment/success'), $paymentDetails['success_url']);
    }

    public function test_blog_alias_methods_return_same_views_as_canonical_methods(): void
    {
        $user = $this->activeUser();
        $category = $this->blogCategory('Alias category');
        $this->blogFor($user, [
            'title' => 'Alias blog',
            'category_id' => $category->id,
        ]);

        $blogsResponse = $this->actingAs($user)->get(route('blogs'))->assertOk();
        $this->assertSame('frontend.blogs.blogs', $blogsResponse->viewData('view_path'));

        $singleResponse = $this->actingAs($user)->get(route('single.blog', Blog::query()->first()->id))->assertOk();
        $this->assertSame('frontend.blogs.single_blog', $singleResponse->viewData('view_path'));
    }

    public function test_blog_show_appends_current_user_to_view_list_once(): void
    {
        $user = $this->activeUser();
        $blog = $this->blogFor($this->activeUser(), [
            'title' => 'Viewed blog',
            'view' => json_encode([]),
        ]);

        $this->actingAs($user)->get(route('single.blog', $blog->id))->assertOk();
        $this->assertSame([$user->id], json_decode((string) $blog->refresh()->view, true));

        $this->actingAs($user)->get(route('single.blog', $blog->id))->assertOk();
        $this->assertSame([$user->id], json_decode((string) $blog->refresh()->view, true));
    }

    public function test_blog_category_and_infinite_scroll_return_filtered_preloaded_blog_views(): void
    {
        $user = $this->activeUser();
        $matchedCategory = $this->blogCategory('Matched category');
        $otherCategory = $this->blogCategory('Other category');
        $matchedBlog = $this->blogFor($user, ['title' => 'Matched blog', 'category_id' => $matchedCategory->id]);
        $this->blogFor($user, ['title' => 'Other blog', 'category_id' => $otherCategory->id]);

        $categoryResponse = $this->actingAs($user)->get(route('category.blog', $matchedCategory->id))->assertOk();

        $this->assertSame('frontend.blogs.category_blog', $categoryResponse->viewData('view_path'));
        $this->assertSame([$matchedBlog->id], $categoryResponse->viewData('blogs')->pluck('id')->all());

        $scrollResponse = $this->actingAs($user)->get(route('load_blog_by_scrolling', ['offset' => 1]))->assertOk();

        $scrollResponse->assertViewIs('frontend.blogs.blog-single');
        $this->assertCount(1, $scrollResponse->viewData('blogs'));
    }

    public function test_blog_search_escapes_titles_in_legacy_html_response(): void
    {
        $user = $this->activeUser();
        $this->blogFor($user, [
            'title' => '<script>alert("xss")</script> Searchable',
        ]);

        $response = $this->actingAs($user)
            ->get(route('search.blog', ['search' => 'Searchable']))
            ->assertOk();

        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; Searchable', false);
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'email_verified_at' => now(),
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
    }

    private function blogCategory(string $name = 'Controller category'): BlogCategory
    {
        $category = new BlogCategory;
        $category->name = $name;
        $category->save();

        return $category;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function blogFor(User $user, array $attributes = []): Blog
    {
        $blog = new Blog;
        $blog->user_id = $user->id;
        $blog->category_id = $attributes['category_id'] ?? $this->blogCategory()->id;
        $blog->title = $attributes['title'] ?? 'Controller blog';
        $blog->description = $attributes['description'] ?? 'Controller blog description.';
        $blog->tag = $attributes['tag'] ?? json_encode([]);
        $blog->view = $attributes['view'] ?? json_encode([]);
        $blog->created_at = $attributes['created_at'] ?? now();
        $blog->updated_at = $attributes['updated_at'] ?? now();
        $blog->save();

        return $blog;
    }
}
