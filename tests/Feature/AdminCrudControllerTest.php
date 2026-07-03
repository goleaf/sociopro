<?php

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\AdminCrudController;
use App\Models\AccountActiveRequest;
use App\Models\Badge;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Group;
use App\Models\Job;
use App\Models\JobApply;
use App\Models\JobCategory;
use App\Models\JobWishlist;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PaymentGateway;
use App\Models\PaymentHistoryEntry;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Unique;
use ReflectionMethod;
use Tests\TestCase;

class AdminCrudControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_constructor_helpers_and_core_admin_pages_have_safe_contracts(): void
    {
        $admin = $this->adminUser();
        $this->setting('version', '13.18.0');
        $this->setting('purchase_code', 'valid-code');

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Dashboard');

        $this->assertSame(1, session('admin_login'));

        foreach ([
            'admin.change.password',
            'admin.profile',
            'admin.about',
            'admin.users',
            'admin.user.add',
            'admin.settings.payment',
            'admin.users.accountActiveReq',
        ] as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk();
        }

        $controller = app(AdminCrudController::class);

        $this->assertSame(10, $this->invokePrivate($controller, 'integerWithin', ['500', 1, 1, 10]));
        $this->assertSame(5, $this->invokePrivate($controller, 'integerWithin', [['bad'], 5, 1, 10]));
        $this->assertSame(5, $this->invokePrivate($controller, 'integerWithin', ['0', 5, 1, 10]));

        $uniqueRule = $this->invokePrivate($controller, 'uniqueRule', ['users', 'email']);
        $this->assertInstanceOf(Unique::class, $uniqueRule);
        $this->assertStringContainsString('unique:users,email', (string) $uniqueRule);

        $parameters = $this->invokePrivate($controller, 'adminUsersDataTableParameters', [
            Request::create('/admin/server_side_users_data', 'POST', [
                'draw' => '7',
                'start' => '-20',
                'length' => '500',
                'order' => [['column' => '3', 'dir' => 'SIDEWAYS']],
                'search' => ['value' => str_repeat('a', 300)],
            ]),
        ]);

        $this->assertSame([
            'draw' => 7,
            'start' => 0,
            'length' => 100,
            'sort' => 'email',
            'direction' => 'desc',
            'search' => str_repeat('a', 255),
        ], $parameters);
    }

    public function test_category_crud_methods_create_update_validate_uniqueness_and_delete(): void
    {
        $admin = $this->adminUser();

        foreach ($this->categoryContracts() as $contract) {
            [$modelClass, $field, $viewRoute, $createRoute, $saveRoute, $editRoute, $updateRoute, $deleteRoute] = $contract;

            $this->actingAs($admin)->get(route($viewRoute))->assertOk();
            $this->actingAs($admin)->get(route($createRoute))->assertOk();

            $createdName = 'Created '.$field;
            $this->actingAs($admin)
                ->post(route($saveRoute), [$field => $createdName])
                ->assertRedirect();

            $created = $modelClass::query()->where('name', $createdName)->firstOrFail();

            $this->actingAs($admin)->get(route($editRoute, $created->id))->assertOk();

            $updatedName = 'Updated '.$field;
            $this->actingAs($admin)
                ->post(route($updateRoute, $created->id), [$field => $updatedName])
                ->assertRedirect(route($viewRoute));

            $this->assertDatabaseHas($created->getTable(), [
                'id' => $created->id,
                'name' => $updatedName,
            ]);

            $this->actingAs($admin)
                ->post(route($updateRoute, $created->id), [$field => $updatedName])
                ->assertRedirect(route($viewRoute));

            $duplicate = $this->namedModel($modelClass, 'Duplicate '.$field);

            $this->actingAs($admin)
                ->post(route($saveRoute), [$field => $duplicate->name])
                ->assertSessionHasErrors($field);

            $this->actingAs($admin)
                ->get(route($deleteRoute, $created->id))
                ->assertRedirect();

            $this->assertDatabaseMissing($created->getTable(), ['id' => $created->id]);
        }
    }

    public function test_group_page_blog_badge_and_job_admin_workflows_preserve_database_state(): void
    {
        $admin = $this->adminUser();
        $this->setting('badge_price', '5');
        $this->setting('job_price', '9');
        $this->setting('day', '7');

        $this->actingAs($admin)->get(route('admin.group'))->assertOk();
        $this->actingAs($admin)->get(route('admin.group.create'))->assertOk();
        $this->actingAs($admin)
            ->post(route('admin.group.created'), [
                'title' => 'Admin Test Group',
                'subtitle' => 'Group subtitle',
                'status' => '1',
                'privacy' => 'public',
                'about' => 'Group about',
            ])
            ->assertRedirect(route('admin.group'));

        $group = Group::query()->where('title', 'Admin Test Group')->firstOrFail();
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => MembershipRole::Admin->value,
            'is_accepted' => '1',
        ]);

        $this->actingAs($admin)->get(route('admin.group.edit', $group->id))->assertOk();
        $this->actingAs($admin)
            ->post(route('admin.group.updated', $group->id), [
                'title' => 'Updated Admin Group',
                'subtitle' => 'Updated subtitle',
                'status' => '0',
                'privacy' => 'private',
                'about' => 'Updated about',
            ])
            ->assertRedirect(route('admin.group'));
        $this->assertDatabaseHas('groups', ['id' => $group->id, 'title' => 'Updated Admin Group']);

        $pageCategory = $this->namedModel(PageCategory::class, 'Admin Pages');
        $this->actingAs($admin)->get(route('admin.page'))->assertOk();
        $this->actingAs($admin)->get(route('admin.page.create'))->assertOk();
        $this->actingAs($admin)
            ->post(route('admin.page.created'), [
                'title' => 'Admin Test Page',
                'category' => $pageCategory->id,
                'description' => 'Page description',
            ])
            ->assertRedirect(route('admin.page'));
        $page = Page::query()->where('title', 'Admin Test Page')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.page.edit', $page->id))->assertOk();
        $this->actingAs($admin)
            ->post(route('admin.page.updated', $page->id), [
                'title' => 'Updated Admin Page',
                'category' => $pageCategory->id,
                'description' => 'Updated page description',
            ])
            ->assertRedirect(route('admin.page'));
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'title' => 'Updated Admin Page']);

        $blogCategory = $this->namedModel(BlogCategory::class, 'Admin Blogs');
        $olderBlog = $this->blog($admin, $blogCategory, 'Older Blog');
        $targetBlog = $this->blog($admin, $blogCategory, 'Target Blog');
        $this->actingAs($admin)->get(route('admin.blog'))->assertOk();
        $this->actingAs($admin)->get(route('admin.blog.create'))->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.blog.edit', $targetBlog->id))
            ->assertOk()
            ->assertSee('Target Blog')
            ->assertDontSee('Older Blog');
        $this->actingAs($admin)
            ->post(route('admin.blog.created'), [
                'title' => 'Created Blog',
                'category' => $blogCategory->id,
                'description' => 'Blog description',
                'tag' => json_encode([['value' => 'admin']]),
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-10',
            ])
            ->assertRedirect(route('admin.blog'));
        $createdBlog = Blog::query()->where('title', 'Created Blog')->firstOrFail();
        $this->actingAs($admin)
            ->post(route('admin.blog.updated', $createdBlog->id), [
                'title' => 'Updated Blog',
                'category' => $blogCategory->id,
                'description' => 'Updated blog description',
                'tag' => json_encode([['value' => 'updated']]),
            ])
            ->assertRedirect(route('admin.blog'));
        $this->assertDatabaseHas('blogs', ['id' => $createdBlog->id, 'title' => 'Updated Blog']);

        $badge = $this->badge($admin);
        $this->actingAs($admin)->get(route('admin.badge'))->assertOk();
        $this->actingAs($admin)
            ->post(route('admin.badge.price.save'), ['badge_price' => '19'])
            ->assertRedirect();
        $this->assertDatabaseHas('settings', ['type' => 'badge_price', 'description' => '19']);
        $this->actingAs($admin)
            ->get(route('admin.badge.delete', $badge->id))
            ->assertRedirect();
        $this->assertDatabaseMissing('batchs', ['id' => $badge->id]);

        $jobCategory = $this->namedModel(JobCategory::class, 'Admin Jobs');
        foreach ([
            'admin.view.job.category',
            'admin.create.job.category',
            'admin.job',
            'admin.job.create',
            'admin.pending.job',
            'admin.job.apply.all.list',
            'admin.job.payment.history',
            'admin.job.price.view',
        ] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }

        $this->actingAs($admin)
            ->post(route('admin.job.created'), $this->jobPayload($jobCategory, 'Created Admin Job'))
            ->assertRedirect(route('admin.job'));
        $job = Job::query()->where('title', 'Created Admin Job')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.job.edit', $job->id))->assertOk();
        $this->actingAs($admin)
            ->post(route('admin.job.updated', $job->id), $this->jobPayload($jobCategory, 'Updated Admin Job', ['is_published' => '1']))
            ->assertRedirect(route('admin.job'));
        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'title' => 'Updated Admin Job', 'is_published' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.job.price.view.save'), ['job_price' => '29', 'day' => '14'])
            ->assertRedirect();
        $this->assertDatabaseHas('settings', ['type' => 'job_price', 'description' => '29']);
        $this->assertDatabaseHas('settings', ['type' => 'day', 'description' => '14']);

        $history = PaymentHistoryEntry::query()->create([
            'item_type' => 'job',
            'item_id' => $job->id,
            'user_id' => $admin->id,
            'amount' => 10,
            'currency' => 'USD',
            'identifier' => 'stripe',
        ]);
        $this->actingAs($admin)
            ->get(route('admin.delete.job.payment.history', $history->id))
            ->assertRedirect();
        $this->assertDatabaseMissing('payment_histories', ['id' => $history->id]);

        $application = JobApply::query()->create([
            'job_id' => $job->id,
            'owner_id' => $admin->id,
            'user_id' => $admin->id,
            'email' => 'job-applicant@example.test',
            'phone' => '+37060000000',
            'attachment' => 'admin-crud-test.pdf',
        ]);
        File::ensureDirectoryExists(public_path('storage/job/cv'));
        File::put(public_path('storage/job/cv/admin-crud-test.pdf'), '%PDF test');
        $this->actingAs($admin)
            ->get(route('admin.job.apply.list-delete', $application->id))
            ->assertRedirect();
        $this->assertDatabaseMissing('job_applies', ['id' => $application->id]);
        $this->assertFileDoesNotExist(public_path('storage/job/cv/admin-crud-test.pdf'));

        JobWishlist::query()->create(['job_id' => $job->id, 'user_id' => $admin->id]);
        JobApply::query()->create(['job_id' => $job->id, 'owner_id' => $admin->id, 'user_id' => $admin->id]);
        PaymentHistoryEntry::query()->create([
            'item_type' => 'job',
            'item_id' => $job->id,
            'user_id' => $admin->id,
            'amount' => 10,
            'currency' => 'USD',
            'identifier' => 'stripe',
        ]);
        $this->actingAs($admin)
            ->get(route('admin.delete.job', $job->id))
            ->assertRedirect();
        $this->assertDatabaseMissing('jobs', ['id' => $job->id]);
        $this->assertDatabaseMissing('job_wishlists', ['job_id' => $job->id]);
        $this->assertDatabaseMissing('job_applies', ['job_id' => $job->id]);
        $this->assertDatabaseMissing('payment_histories', ['item_type' => 'job', 'item_id' => $job->id]);

        $this->actingAs($admin)
            ->get(route('admin.page', ['delete' => 'yes', 'id' => $page->id]))
            ->assertRedirect();
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->actingAs($admin)
            ->get(route('admin.blog', ['delete' => 'yes', 'id' => $createdBlog->id]))
            ->assertRedirect();
        $this->assertDatabaseMissing('blogs', ['id' => $createdBlog->id]);
        $this->actingAs($admin)
            ->get(route('admin.group.delete', $group->id))
            ->assertRedirect();
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('group_members', ['group_id' => $group->id]);
    }

    public function test_users_payment_purchase_code_and_account_activation_admin_flows(): void
    {
        $admin = $this->adminUser();
        $this->setting('purchase_code', 'old-code');

        $this->actingAs($admin)
            ->post(route('admin.save_valid_purchase_code', 'update'), ['purchase_code' => 'new-code'])
            ->assertOk()
            ->assertSee('1', false);
        $this->assertDatabaseHas('settings', ['type' => 'purchase_code', 'description' => 'new-code']);

        $this->actingAs($admin)
            ->post(route('admin.user.store'), [
                'name' => 'Managed User',
                'email' => 'managed-user@example.test',
                'password' => 'secret-password',
                'gender' => 'male',
                'date_of_birth' => '1990-01-01',
                'phone' => '+37060000001',
                'address' => 'Admin Street',
                'bio' => 'Managed by admin',
            ])
            ->assertRedirect(route('admin.users'));
        $managedUser = User::query()->where('email', 'managed-user@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('secret-password', $managedUser->password));
        $this->assertNotNull($managedUser->email_verified_at);

        $this->actingAs($admin)->get(route('admin.user.edit', $managedUser->id))->assertOk();
        $this->actingAs($admin)
            ->post(route('admin.user.update', $managedUser->id), [
                'name' => 'Updated Managed User',
                'email' => 'updated-managed-user@example.test',
                'gender' => 'female',
                'date_of_birth' => '1992-02-02',
                'phone' => '+37060000002',
                'address' => 'Updated Street',
                'bio' => 'Updated bio',
            ])
            ->assertRedirect(route('admin.users'));
        $this->assertDatabaseHas('users', ['id' => $managedUser->id, 'email' => 'updated-managed-user@example.test']);

        $this->actingAs($admin)
            ->get(route('admin.user.status', $managedUser->id))
            ->assertRedirect(route('admin.users'));
        $this->assertDatabaseHas('users', ['id' => $managedUser->id, 'status' => 0]);

        $this->actingAs($admin)
            ->postJson(route('admin.server_side_users_data'), [
                'draw' => '2',
                'start' => '0',
                'length' => '10',
                'order' => [['column' => '3', 'dir' => 'asc']],
                'search' => ['value' => 'updated-managed-user'],
            ])
            ->assertOk()
            ->assertJsonPath('draw', 2)
            ->assertJsonPath('data.0.email', 'updated-managed-user@example.test');

        Currency::factory()->usd()->create();
        $paymentGateway = $this->paymentGateway();
        $this->actingAs($admin)->get(route('admin.payment_gateway.edit', $paymentGateway->id))->assertOk();
        $this->actingAs($admin)
            ->post(route('admin.payment_gateway.update', $paymentGateway->id), [
                'currency' => 'USD',
                'public_key' => 'pk_test',
                'secret_key' => 'sk_test',
                'unexpected_secret' => 'must-not-save',
            ])
            ->assertRedirect(route('admin.settings.payment'));

        $paymentGateway->refresh();
        $this->assertSame('USD', $paymentGateway->currency);
        $this->assertSame([
            'public_key' => 'pk_test',
            'secret_key' => 'sk_test',
        ], $paymentGateway->decodedKeys());

        $this->actingAs($admin)->get(route('admin.payment_gateway.status', $paymentGateway->id))->assertRedirect(route('admin.settings.payment'));
        $this->assertFalse($paymentGateway->refresh()->status);
        $this->actingAs($admin)->get(route('admin.payment_gateway.environment', $paymentGateway->id))->assertRedirect();
        $this->assertFalse($paymentGateway->refresh()->test_mode);

        $disabledUser = $this->generalUser([
            'email' => 'disabled-managed-user@example.test',
            'status' => UserAccountStatus::Disabled->value,
        ]);
        $request = AccountActiveRequest::factory()->create([
            'user_id' => $disabledUser->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.accActiveReqApp'))
            ->assertRedirect(route('admin.users.accountActiveReq'));

        $this->actingAs($admin)
            ->get(route('admin.users.acActiveReqApp', ['id' => $request->id, 'user_id' => $disabledUser->id]))
            ->assertRedirect(route('admin.users'));
        $this->assertDatabaseHas('users', ['id' => $disabledUser->id, 'status' => 1]);
        $this->assertDatabaseMissing('account_active_requests', ['id' => $request->id]);

        $deleteRequest = AccountActiveRequest::factory()->create([
            'user_id' => $disabledUser->id,
            'status' => 'pending',
        ]);
        $this->actingAs($admin)
            ->get(route('admin.users.acActiveReDlt', ['id' => $deleteRequest->id]))
            ->assertRedirect();
        $this->assertDatabaseMissing('account_active_requests', ['id' => $deleteRequest->id]);

        $this->actingAs($admin)
            ->get(route('admin.user.delete', $managedUser->id))
            ->assertRedirect(route('admin.users'));
        $this->assertDatabaseMissing('users', ['id' => $managedUser->id]);
    }

    public function test_admin_crud_blades_do_not_query_database_directly(): void
    {
        foreach ([
            resource_path('views/backend/admin/page/create.blade.php'),
            resource_path('views/backend/admin/page/edit.blade.php'),
            resource_path('views/backend/admin/blog/create.blade.php'),
            resource_path('views/backend/admin/blog/edit.blade.php'),
            resource_path('views/backend/admin/badge/badge-history.blade.php'),
            resource_path('views/backend/admin/jobs/partials/form.blade.php'),
        ] as $path) {
            $contents = file_get_contents($path);

            $this->assertStringNotContainsString('DB::', $contents, $path);
            $this->assertStringNotContainsString('App\\Models', $contents, $path);
            $this->assertStringNotContainsString('::query(', $contents, $path);
            $this->assertStringNotContainsString('::where', $contents, $path);
        }
    }

    /**
     * @return list<array{class-string, string, string, string, string, string, string, string}>
     */
    private function categoryContracts(): array
    {
        return [
            [PageCategory::class, 'pagecategory', 'admin.view.category', 'admin.create.category', 'admin.save.category', 'admin.edit.category', 'admin.update.category', 'admin.delete.category'],
            [Category::class, 'productcategory', 'admin.view.product.category', 'admin.create.product.category', 'admin.save.product.category', 'admin.edit.product.category', 'admin.update.product.category', 'admin.delete.product.category'],
            [Brand::class, 'brand', 'admin.view.product.brand', 'admin.create.product.brand', 'admin.save.product.brand', 'admin.edit.product.brand', 'admin.update.product.brand', 'admin.delete.product.brand'],
            [BlogCategory::class, 'blogcategory', 'admin.view.blog.category', 'admin.create.blog.category', 'admin.save.blog.category', 'admin.edit.blog.category', 'admin.update.blog.category', 'admin.delete.blog.category'],
            [JobCategory::class, 'jobcategory', 'admin.view.job.category', 'admin.create.job.category', 'admin.save.job.category', 'admin.edit.job.category', 'admin.update.job.category', 'admin.delete.job.category'],
        ];
    }

    /**
     * @param  class-string  $modelClass
     */
    private function namedModel(string $modelClass, string $name): object
    {
        $model = new $modelClass;
        $model->forceFill(['name' => $name])->save();

        return $model;
    }

    private function adminUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
        ], $overrides));
    }

    private function generalUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
        ], $overrides));
    }

    private function setting(string $type, string $description): Setting
    {
        $setting = Setting::query()->where('type', $type)->first() ?? new Setting;
        $setting->forceFill([
            'type' => $type,
            'description' => $description,
        ])->save();

        return $setting;
    }

    private function blog(User $user, BlogCategory $category, string $title): Blog
    {
        $blog = new Blog;
        $blog->forceFill([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => $title,
            'description' => $title.' description',
            'tag' => json_encode(['admin']),
            'view' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();

        return $blog;
    }

    private function badge(User $user): Badge
    {
        $badge = new Badge;
        $badge->forceFill([
            'user_id' => $user->id,
            'title' => 'Admin Badge',
            'description' => 'Badge description',
            'icon' => 'fa-circle-check',
            'status' => 1,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ])->save();

        return $badge;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(JobCategory $category, string $title, array $overrides = []): array
    {
        return array_merge([
            'title' => $title,
            'category' => $category->id,
            'starting_salary_range' => '1000',
            'ending_salary_range' => '2000',
            'company' => 'Admin Company',
            'type' => 'Full Time',
            'location' => 'Vilnius',
            'description' => $title.' description',
            'status' => '1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ], $overrides);
    }

    private function paymentGateway(): PaymentGateway
    {
        $gateway = new PaymentGateway;
        $gateway->forceFill([
            'identifier' => 'stripe',
            'currency' => 'EUR',
            'title' => 'Stripe',
            'description' => 'Stripe gateway',
            'keys' => [
                'public_key' => 'old_pk',
                'secret_key' => 'old_sk',
            ],
            'test_mode' => 1,
            'status' => 1,
            'is_addon' => 0,
        ])->save();

        return $gateway;
    }

    /**
     * @param  list<mixed>  $arguments
     */
    private function invokePrivate(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}
