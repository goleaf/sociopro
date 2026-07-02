<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\BlogCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PageCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniqueValidationRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lookup_unique_validation_blocks_duplicate_creates(): void
    {
        $admin = $this->adminUser();

        foreach ($this->lookupValidationCases() as $case) {
            $name = $this->uniqueName('Duplicate');
            $this->createLookupModel($case['model'], $name);

            $this->actingAs($admin)
                ->from(route($case['createRoute']))
                ->post(route($case['storeRoute']), [
                    $case['field'] => $name,
                ])
                ->assertRedirect(route($case['createRoute']))
                ->assertSessionHasErrors($case['field']);

            $this->assertSame(1, $case['model']::query()->where('name', $name)->count());
        }
    }

    public function test_admin_lookup_unique_validation_allows_update_to_keep_current_name(): void
    {
        $admin = $this->adminUser();

        foreach ($this->lookupValidationCases() as $case) {
            $name = $this->uniqueName('Current');
            $model = $this->createLookupModel($case['model'], $name);

            $this->actingAs($admin)
                ->post(route($case['updateRoute'], ['id' => $model->getKey()]), [
                    $case['field'] => $name,
                ])
                ->assertRedirect(route($case['indexRoute']))
                ->assertSessionDoesntHaveErrors($case['field']);

            $this->assertSame($name, $model->refresh()->getAttribute('name'));
        }
    }

    public function test_admin_lookup_unique_validation_blocks_update_conflicts(): void
    {
        $admin = $this->adminUser();

        foreach ($this->lookupValidationCases() as $case) {
            $current = $this->createLookupModel($case['model'], $this->uniqueName('Current'));
            $otherName = $this->uniqueName('Other');
            $this->createLookupModel($case['model'], $otherName);

            $this->actingAs($admin)
                ->from(route($case['editRoute'], ['id' => $current->getKey()]))
                ->post(route($case['updateRoute'], ['id' => $current->getKey()]), [
                    $case['field'] => $otherName,
                ])
                ->assertRedirect(route($case['editRoute'], ['id' => $current->getKey()]))
                ->assertSessionHasErrors($case['field']);

            $this->assertNotSame($otherName, $current->refresh()->getAttribute('name'));
        }
    }

    public function test_admin_user_email_unique_validation_allows_current_email_and_blocks_conflicts(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create([
            'email' => $this->uniqueEmail('current'),
        ]);
        $otherUser = User::factory()->create([
            'email' => $this->uniqueEmail('other'),
        ]);
        $payload = [
            'name' => 'Edited User',
            'gender' => 'male',
            'date_of_birth' => '1992-04-15',
        ];

        $this->actingAs($admin)
            ->post(route('admin.user.update', ['id' => $user->id]), $payload + [
                'email' => $user->email,
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionDoesntHaveErrors('email');

        $this->actingAs($admin)
            ->from(route('admin.user.edit', ['id' => $user->id]))
            ->post(route('admin.user.update', ['id' => $user->id]), $payload + [
                'email' => $otherUser->email,
            ])
            ->assertRedirect(route('admin.user.edit', ['id' => $user->id]))
            ->assertSessionHasErrors('email');

        $this->assertNotSame($otherUser->email, $user->refresh()->email);
    }

    public function test_unique_validation_rules_use_fluent_rules_and_model_instance_ignores(): void
    {
        $files = [
            app_path('Http/Controllers/AdminCrudController.php'),
            app_path('Http/Controllers/ApiController.php'),
            app_path('Http/Controllers/Auth/RegisteredUserController.php'),
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertDoesNotMatchRegularExpression('/unique:[^\'"]*/', $contents, $file);
            $this->assertStringNotContainsString('->ignore($id)', $contents, $file);
        }
    }

    /**
     * @return list<array{model: class-string<Model>, field: string, createRoute: string, storeRoute: string, editRoute: string, updateRoute: string, indexRoute: string}>
     */
    private function lookupValidationCases(): array
    {
        return [
            [
                'model' => PageCategory::class,
                'field' => 'pagecategory',
                'createRoute' => 'admin.create.category',
                'storeRoute' => 'admin.save.category',
                'editRoute' => 'admin.edit.category',
                'updateRoute' => 'admin.update.category',
                'indexRoute' => 'admin.view.category',
            ],
            [
                'model' => Category::class,
                'field' => 'productcategory',
                'createRoute' => 'admin.create.product.category',
                'storeRoute' => 'admin.save.product.category',
                'editRoute' => 'admin.edit.product.category',
                'updateRoute' => 'admin.update.product.category',
                'indexRoute' => 'admin.view.product.category',
            ],
            [
                'model' => Brand::class,
                'field' => 'brand',
                'createRoute' => 'admin.create.product.brand',
                'storeRoute' => 'admin.save.product.brand',
                'editRoute' => 'admin.edit.product.brand',
                'updateRoute' => 'admin.update.product.brand',
                'indexRoute' => 'admin.view.product.brand',
            ],
            [
                'model' => BlogCategory::class,
                'field' => 'blogcategory',
                'createRoute' => 'admin.create.blog.category',
                'storeRoute' => 'admin.save.blog.category',
                'editRoute' => 'admin.edit.blog.category',
                'updateRoute' => 'admin.update.blog.category',
                'indexRoute' => 'admin.view.blog.category',
            ],
        ];
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::Admin->value,
        ]);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function createLookupModel(string $modelClass, string $name): Model
    {
        $model = new $modelClass;
        $model->forceFill(['name' => $name]);
        $model->save();

        return $model;
    }

    private function uniqueName(string $prefix): string
    {
        return $prefix.' '.str_replace('.', '', uniqid('', true));
    }

    private function uniqueEmail(string $prefix): string
    {
        return $prefix.'.'.str_replace('.', '', uniqid('', true)).'@example.test';
    }
}
