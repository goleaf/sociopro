<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogControllerResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_destroy_returns_standard_json_payload_and_deletes_blog(): void
    {
        $user = User::factory()->create([
            'friends' => json_encode([]),
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
        ]);

        $blog = new Blog;
        $blog->user_id = $user->id;
        $blog->title = 'Legacy response test';
        $blog->description = 'Pinned delete response payload.';
        $blog->tag = json_encode([]);
        $blog->view = json_encode([]);
        $blog->save();

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
}
