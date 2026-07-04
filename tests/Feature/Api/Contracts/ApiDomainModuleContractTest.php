<?php

namespace Tests\Feature\Api\Contracts;

use App\Models\GroupMember;
use App\Models\PageLike;
use App\Models\SavedProduct;

class ApiDomainModuleContractTest extends ApiContractTestCase
{
    public function test_pages_list_show_create_validation_and_like_toggle_contracts(): void
    {
        $user = $this->activeApiUser();
        $category = $this->pageCategory();
        $page = $this->pageFor($user, [
            'category_id' => $category->id,
            'title' => 'Contract Page',
        ]);
        $headers = $this->apiHeaders($user);

        $this->getJson(route('api.pages.index'), $headers)
            ->assertOk()
            ->assertJsonPath('0.id', $page->id)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'user_id',
                    'title',
                    'category_id',
                    'category',
                    'like_count',
                    'is_Liked',
                    'my_page',
                    'owner',
                ],
            ]);

        auth()->forgetGuards();

        $this->getJson(route('api.pages.show', $page->id), $headers)
            ->assertOk()
            ->assertJsonPath('id', $page->id)
            ->assertJsonStructure([
                'id',
                'user_id',
                'title',
                'category_id',
                'category',
                'like_count',
                'is_Liked',
                'my_page',
                'owner',
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.pages.store'), [], $headers)
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'name',
                    'category',
                ],
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.pages.likes.store', $page->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User like the Page',
            ]);

        $this->assertDatabaseHas('page_likes', [
            'page_id' => $page->id,
            'user_id' => $user->id,
        ]);

        auth()->forgetGuards();

        $this->postJson(route('api.pages.likes.store', $page->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Page dislike successfully',
            ]);

        $this->assertSame(0, PageLike::query()
            ->where('page_id', $page->id)
            ->where('user_id', $user->id)
            ->count());
    }

    public function test_groups_list_show_create_validation_and_join_toggle_contracts(): void
    {
        $user = $this->activeApiUser();
        $group = $this->groupFor($user, ['title' => 'Contract Group']);
        $headers = $this->apiHeaders($user);

        $this->getJson(route('api.groups.index'), $headers)
            ->assertOk()
            ->assertJsonPath('0.id', $group->id)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'user_id',
                    'title',
                    'privacy',
                    'status',
                    'group_members_count',
                    'is_Joined',
                    'members',
                ],
            ]);

        auth()->forgetGuards();

        $this->getJson(route('api.groups.show', $group->id), $headers)
            ->assertOk()
            ->assertJsonPath('id', $group->id)
            ->assertJsonStructure([
                'id',
                'user_id',
                'title',
                'privacy',
                'status',
                'group_members_count',
                'is_Joined',
                'members',
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.groups.store'), [], $headers)
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'name',
                    'privacy',
                ],
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.groups.members.store', $group->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User joined the group successfully',
            ]);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'is_accepted' => 1,
        ]);

        auth()->forgetGuards();

        $this->postJson(route('api.groups.members.store', $group->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Job delete from wishlist successfully',
            ]);

        $this->assertSame(0, GroupMember::query()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->count());
    }

    public function test_events_list_show_create_validation_and_going_contracts(): void
    {
        $user = $this->activeApiUser();
        $event = $this->eventFor($user, ['title' => 'Contract Event']);
        $headers = $this->apiHeaders($user);

        $this->getJson(route('api.events.index'), $headers)
            ->assertOk()
            ->assertJsonPath('0.id', $event->id)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'user_id',
                    'my_event',
                    'title',
                    'description',
                    'event_date',
                    'date',
                    'event_time',
                    'going',
                    'interest',
                ],
            ]);

        auth()->forgetGuards();

        $this->getJson(route('api.events.show', $event->id), $headers)
            ->assertOk()
            ->assertJsonPath('id', $event->id)
            ->assertJsonStructure([
                'id',
                'user_id',
                'my_event',
                'title',
                'event_date',
                'date',
                'event_time',
                'going',
                'interest',
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.events.store'), [], $headers)
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'eventname',
                    'eventdate',
                    'eventtime',
                    'eventlocation',
                ],
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.events.going.store', $event->id), [], $headers)
            ->assertOk()
            ->assertJsonStructure([
                'alertMessage',
                'content',
            ]);

        $this->assertContains($user->id, json_decode($event->refresh()->going_users_id, true));
    }

    public function test_marketplace_list_lookup_filter_validation_and_save_contracts(): void
    {
        $user = $this->activeApiUser();
        $product = $this->marketplaceFor($user, [
            'title' => 'Contract Marketplace Product',
        ]);
        $headers = $this->apiHeaders($user);

        $this->getJson(route('api.marketplace.index'), $headers)
            ->assertOk()
            ->assertJsonPath('0.id', $product->id)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'thrade',
                    'user_id',
                    'title',
                    'price',
                    'category_id',
                    'brand_id',
                    'currency_id',
                    'is_Saved',
                    'my_product',
                ],
            ]);

        auth()->forgetGuards();

        $this->getJson(route('api.marketplace.categories.index'), $headers)
            ->assertOk()
            ->assertJsonPath('0.category_id', (int) $product->category);

        auth()->forgetGuards();

        $this->getJson(route('api.marketplace.brands.index'), $headers)
            ->assertOk()
            ->assertJsonPath('0.category_id', (int) $product->brand);

        auth()->forgetGuards();

        $this->getJson(route('api.currencies.index'), $headers)
            ->assertOk()
            ->assertJsonPath('0.category_id', (int) $product->currency_id);

        auth()->forgetGuards();

        $this->getJson(route('api.marketplace.filter'), $headers)
            ->assertOk()
            ->assertJsonPath('0.id', $product->id);

        auth()->forgetGuards();

        $this->postJson(route('api.marketplace.store'), [], $headers)
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'validationError',
                'error',
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.marketplace.saves.store', $product->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User saved the product',
            ]);

        $this->assertDatabaseHas('saved_products', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        auth()->forgetGuards();

        $this->postJson(route('api.marketplace.saves.destroy', $product->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'unsave successfully',
            ]);

        $this->assertSame(0, SavedProduct::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->count());
    }

    public function test_blogs_list_category_create_validation_view_and_missing_delete_contracts(): void
    {
        $user = $this->activeApiUser();
        $category = $this->blogCategory();
        $blog = $this->blogFor($user, [
            'category_id' => $category->id,
            'title' => 'Contract Blog',
        ]);
        $headers = $this->apiHeaders($user);

        $this->getJson(route('api.blogs.index'), $headers)
            ->assertOk()
            ->assertJsonPath('0.id', $blog->id)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'user_id',
                    'user',
                    'title',
                    'category_id',
                    'category',
                    'my_blog',
                    'description',
                    'view',
                    'tags',
                    'commentsCount',
                ],
            ]);

        auth()->forgetGuards();

        $this->getJson(route('api.blogs.categories.index'), $headers)
            ->assertOk()
            ->assertJsonPath('0.category_id', $category->id);

        auth()->forgetGuards();

        $this->postJson(route('api.blogs.store'), [], $headers)
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'title',
                    'category',
                ],
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.blogs.views.store', $blog->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'blog views',
            ]);

        $this->assertContains($user->id, json_decode($blog->refresh()->view, true));

        auth()->forgetGuards();

        $this->postJson(route('api.blogs.destroy', 999999), [], $headers)
            ->assertNotFound();
    }

    public function test_jobs_fundraisers_and_paid_content_route_level_contracts(): void
    {
        foreach ([
            ['GET', route('api.paid_content.index')],
            ['GET', route('api.paid_content.packages.index')],
            ['GET', route('api.jobs.index')],
            ['POST', route('api.jobs.store')],
            ['POST', route('api.jobs.update', 999999)],
            ['POST', route('api.jobs.destroy', 999999)],
            ['GET', route('api.jobs.wishlist.index')],
            ['POST', route('api.jobs.wishlist.store', 999999)],
            ['POST', route('api.jobs.applications.store', 999999)],
            ['GET', route('api.fundraisers.index')],
            ['POST', route('api.fundraisers.store')],
            ['POST', route('api.fundraisers.update', 999999)],
            ['POST', route('api.fundraisers.invitations.store', [1, 999999])],
        ] as [$method, $url]) {
            $this->json($method, $url)
                ->assertUnauthorized()
                ->assertJson($this->legacyAuthenticationPayload());
        }
    }

    public function test_jobs_and_fundraisers_write_validation_contracts_when_authenticated(): void
    {
        $user = $this->activeApiUser();
        $headers = $this->apiHeaders($user);

        $this->postJson(route('api.jobs.store'), [], $headers)
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'title',
                    'category',
                ],
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.fundraisers.store'), [], $headers)
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'title',
                    'description',
                    'goal_amount',
                    'timestamp_end',
                    'categories_id',
                ],
            ]);
    }
}
