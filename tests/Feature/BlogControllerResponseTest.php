<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\BlogController;
use App\Http\Requests\Blog\BlogRequest;
use App\Http\Requests\Blog\StoreBlogRequest;
use App\Http\Requests\Blog\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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

    public function test_blog_write_requests_share_standardized_rules(): void
    {
        $this->assertTrue(class_exists(BlogRequest::class), 'Blog write requests should share a common rules source.');

        $storeRequest = new StoreBlogRequest;
        $updateRequest = new UpdateBlogRequest;

        $this->assertInstanceOf(BlogRequest::class, $storeRequest);
        $this->assertInstanceOf(BlogRequest::class, $updateRequest);
        $this->assertSame($storeRequest->rules(), $updateRequest->rules());
        $this->assertSame(['required', 'string', 'max:255'], $storeRequest->rules()['title']);
        $this->assertSame(['required', 'integer', 'exists:blogcategories,id'], $storeRequest->rules()['category']);
        $this->assertSame(['nullable', 'string'], $storeRequest->rules()['description']);
        $this->assertSame(['nullable'], $storeRequest->rules()['tag']);
        $this->assertSame(['sometimes', 'array'], $storeRequest->rules()['tags']);
        $this->assertSame(['required', 'string', 'max:100'], $storeRequest->rules()['tags.*.value']);
        $this->assertSame(
            ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'extensions:jpg,jpeg,png,gif,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            $storeRequest->rules()['image']
        );
    }

    public function test_blog_category_select_scope_orders_lookup_options_without_extra_columns(): void
    {
        $firstCategory = $this->blogCategory('First category');
        $secondCategory = $this->blogCategory('Second category');

        $categories = BlogCategory::query()->forSelect()->get();
        $firstOption = $categories->first();

        $this->assertNotNull($firstOption);
        $this->assertSame([$firstCategory->id, $secondCategory->id], $categories->pluck('id')->all());
        $this->assertSame(['id', 'name'], array_keys($firstOption->getAttributes()));
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

    public function test_store_rejects_unknown_blog_category_id(): void
    {
        $user = $this->activeGeneralUser();

        $this->actingAs($user)
            ->from(route('create.blog'))
            ->post(route('blog.store'), [
                'title' => 'Unknown category blog',
                'category' => 999999,
                'description' => 'This should not be stored.',
            ])
            ->assertRedirect(route('create.blog'))
            ->assertSessionHasErrors(['category']);

        $this->assertDatabaseMissing('blogs', [
            'title' => 'Unknown category blog',
        ]);
    }

    public function test_store_rejects_invalid_blog_image_uploads(): void
    {
        $user = $this->activeGeneralUser();
        $category = $this->blogCategory();

        $this->actingAs($user)
            ->from(route('create.blog'))
            ->post(route('blog.store'), [
                'title' => 'Invalid image blog',
                'category' => $category->id,
                'image' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
            ])
            ->assertRedirect(route('create.blog'))
            ->assertSessionHasErrors(['image']);

        $this->assertDatabaseMissing('blogs', [
            'title' => 'Invalid image blog',
        ]);
    }

    public function test_guest_cannot_store_blog_image_upload(): void
    {
        Storage::fake('public');
        $category = $this->blogCategory();

        $this->post(route('blog.store'), [
            'title' => 'Guest image blog',
            'category' => $category->id,
            'image' => UploadedFile::fake()->image('guest.jpg')->size(128),
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('blogs', [
            'title' => 'Guest image blog',
        ]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_store_rejects_blog_image_with_disallowed_extension(): void
    {
        $user = $this->activeGeneralUser();
        $category = $this->blogCategory();

        $this->actingAs($user)
            ->from(route('create.blog'))
            ->post(route('blog.store'), [
                'title' => 'Unsafe extension blog',
                'category' => $category->id,
                'image' => UploadedFile::fake()->image('payload.php')->size(128),
            ])
            ->assertRedirect(route('create.blog'))
            ->assertSessionHasErrors(['image']);

        $this->assertDatabaseMissing('blogs', [
            'title' => 'Unsafe extension blog',
        ]);
    }

    public function test_store_rejects_blog_image_with_unsafe_dimensions(): void
    {
        $user = $this->activeGeneralUser();
        $category = $this->blogCategory();

        $this->actingAs($user)
            ->from(route('create.blog'))
            ->post(route('blog.store'), [
                'title' => 'Oversized dimensions blog',
                'category' => $category->id,
                'image' => UploadedFile::fake()->image('wide.jpg', 5000, 10)->size(128),
            ])
            ->assertRedirect(route('create.blog'))
            ->assertSessionHasErrors(['image']);

        $this->assertDatabaseMissing('blogs', [
            'title' => 'Oversized dimensions blog',
        ]);
    }

    public function test_store_uploads_blog_image_to_public_disk_with_safe_filename(): void
    {
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('blog/thumbnail');
        Storage::disk('public')->makeDirectory('blog/coverphoto');

        $user = $this->activeGeneralUser();
        $category = $this->blogCategory();
        $blog = null;

        try {
            $this->actingAs($user)
                ->post(route('blog.store'), [
                    'title' => 'Image-backed blog',
                    'category' => $category->id,
                    'description' => 'Stores image through the public disk.',
                    'image' => UploadedFile::fake()->image('cover.jpg', 1200, 800)->size(512),
                ])
                ->assertRedirect(route('blogs'));

            $blog = Blog::where('title', 'Image-backed blog')->firstOrFail();

            $this->assertMatchesRegularExpression('/^[0-9]+-[A-Za-z0-9]+\.jpg$/', $blog->thumbnail);
            Storage::disk('public')->assertExists('blog/thumbnail/'.$blog->thumbnail);
            Storage::disk('public')->assertExists('blog/coverphoto/'.$blog->thumbnail);
            $this->assertSame('public', Storage::disk('public')->getVisibility('blog/thumbnail/'.$blog->thumbnail));
        } finally {
            if ($blog instanceof Blog && is_string($blog->thumbnail)) {
                File::delete(public_path('storage/blog/thumbnail/'.$blog->thumbnail));
                File::delete(public_path('storage/blog/coverphoto/'.$blog->thumbnail));
            }
        }
    }

    public function test_store_rejects_nested_tags_without_values(): void
    {
        $user = $this->activeGeneralUser();
        $category = $this->blogCategory();

        $this->actingAs($user)
            ->from(route('create.blog'))
            ->post(route('blog.store'), [
                'title' => 'Invalid tags blog',
                'category' => $category->id,
                'tag' => [
                    ['label' => 'missing value'],
                ],
            ])
            ->assertRedirect(route('create.blog'))
            ->assertSessionHasErrors(['tags.0.value']);

        $this->assertDatabaseMissing('blogs', [
            'title' => 'Invalid tags blog',
        ]);
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

    public function test_store_accepts_nested_tag_array_values(): void
    {
        $user = $this->activeGeneralUser();
        $category = $this->blogCategory();

        $this->actingAs($user)
            ->post(route('blog.store'), [
                'title' => 'Nested tag blog',
                'category' => $category->id,
                'tag' => [
                    ['value' => 'nested'],
                    ['value' => 'validated'],
                ],
            ])
            ->assertRedirect(route('blogs'));

        $this->assertDatabaseHas('blogs', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Nested tag blog',
            'tag' => json_encode(['nested', 'validated']),
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

    private function blogCategory(string $name = 'Blog category'): BlogCategory
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
        $blog->title = $attributes['title'] ?? 'Existing blog';
        $blog->description = $attributes['description'] ?? 'Existing blog description.';
        $blog->tag = $attributes['tag'] ?? json_encode([]);
        $blog->view = $attributes['view'] ?? json_encode([]);
        $blog->save();

        return $blog;
    }
}
