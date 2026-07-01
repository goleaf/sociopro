<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\BlogController;
use App\Http\Requests\Blog\StoreBlogRequest;
use App\Http\Requests\Blog\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\Blogcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class BlogControllerResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_write_methods_use_form_request_validation(): void
    {
        $storeParameter = (new ReflectionMethod(BlogController::class, 'store'))
            ->getParameters()[0]
            ->getType();
        $updateParameter = (new ReflectionMethod(BlogController::class, 'update'))
            ->getParameters()[0]
            ->getType();

        $this->assertSame(StoreBlogRequest::class, (string) $storeParameter);
        $this->assertSame(UpdateBlogRequest::class, (string) $updateParameter);
    }

    public function test_store_rejects_missing_blog_title_and_category(): void
    {
        $user = $this->activeGeneralUser();

        $this->actingAs($user)
            ->from(route('create.blog'))
            ->post(route('blog.store'), [
                'description' => 'Missing required fields.',
            ])
            ->assertRedirect(route('create.blog'))
            ->assertSessionHasErrors(['title', 'category']);
    }

    public function test_store_creates_blog_from_validated_payload(): void
    {
        $user = $this->activeGeneralUser();
        $otherUser = $this->activeGeneralUser();
        $category = $this->blogCategory();

        $this->actingAs($user)
            ->post(route('blog.store'), [
                'title' => 'Validated store blog',
                'category' => $category->id,
                'description' => 'Only validated blog fields should persist.',
                'tag' => json_encode([
                    ['value' => 'laravel'],
                    ['value' => 'forms'],
                ]),
                'user_id' => $otherUser->id,
                'status' => 0,
            ])
            ->assertRedirect(route('blogs'));

        $this->assertDatabaseHas('blogs', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Validated store blog',
            'description' => 'Only validated blog fields should persist.',
            'tag' => json_encode(['laravel', 'forms']),
        ]);

        $this->assertDatabaseMissing('blogs', [
            'user_id' => $otherUser->id,
            'title' => 'Validated store blog',
        ]);
    }

    public function test_update_rejects_missing_blog_title_and_category(): void
    {
        $user = $this->activeGeneralUser();
        $blog = $this->blogFor($user);

        $this->actingAs($user)
            ->from(route('blog.edit', $blog->id))
            ->post(route('blog.update', $blog->id), [
                'description' => 'Missing required fields.',
            ])
            ->assertRedirect(route('blog.edit', $blog->id))
            ->assertSessionHasErrors(['title', 'category']);
    }

    public function test_update_changes_blog_from_validated_payload(): void
    {
        $user = $this->activeGeneralUser();
        $category = $this->blogCategory('Updated category');
        $blog = $this->blogFor($user);

        $this->actingAs($user)
            ->post(route('blog.update', $blog->id), [
                'title' => 'Validated update blog',
                'category' => $category->id,
                'description' => 'Updated through validated data.',
                'tag' => json_encode([
                    ['value' => 'updated'],
                ]),
                'status' => 0,
            ])
            ->assertRedirect(route('myblog'));

        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Validated update blog',
            'description' => 'Updated through validated data.',
            'tag' => json_encode(['updated']),
        ]);
    }

    public function test_destroy_returns_standard_json_payload_and_deletes_blog(): void
    {
        $user = $this->activeGeneralUser();
        $blog = $this->blogFor($user, [
            'title' => 'Legacy response test',
            'description' => 'Pinned delete response payload.',
        ]);

        $response = $this->actingAs($user)->get(route('blog.delete', ['blog_id' => $blog->id]));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertExactJson([
                'alertMessage' => get_phrase('Blog Deleted Successfully'),
                'fadeOutElem' => '#blog-'.$blog->id,
            ]);

        $this->assertDatabaseMissing('blogs', ['id' => $blog->id]);
    }

    public function test_frontend_ajax_distributor_accepts_standard_json_responses(): void
    {
        $contents = file_get_contents(resource_path('views/frontend/common_scripts.blade.php'));

        $this->assertStringContainsString('if (typeof response === "string")', $contents);
        $this->assertStringContainsString('response = JSON.parse(response);', $contents);
    }

    private function activeGeneralUser(): User
    {
        return User::factory()->create([
            'friends' => json_encode([]),
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
        ]);
    }

    private function blogCategory(string $name = 'Blog category'): Blogcategory
    {
        $category = new Blogcategory;
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
        $blog->title = $attributes['title'] ?? 'Existing blog';
        $blog->description = $attributes['description'] ?? 'Existing blog description.';
        $blog->tag = $attributes['tag'] ?? json_encode([]);
        $blog->view = $attributes['view'] ?? json_encode([]);
        $blog->save();

        return $blog;
    }
}
