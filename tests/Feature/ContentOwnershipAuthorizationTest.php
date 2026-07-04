<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Comments;
use App\Models\Follower;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentOwnershipAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_user_cannot_delete_another_users_post(): void
    {
        $owner = $this->activeUser();
        $attacker = $this->activeUser();
        $post = Posts::factory()->forOwner($owner)->create();

        $this
            ->actingAs($attacker)
            ->getJson(route('post.delete', ['post_id' => $post->post_id]))
            ->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'post_id' => $post->post_id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_web_user_cannot_delete_another_users_comment(): void
    {
        $owner = $this->activeUser();
        $attacker = $this->activeUser();
        $post = Posts::factory()->forOwner($owner)->create();
        $comment = Comments::factory()->forPost($post)->forOwner($owner)->create();

        $this
            ->actingAs($attacker)
            ->getJson(route('comment.delete', ['comment_id' => $comment->comment_id]))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'comment_id' => $comment->comment_id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_api_user_cannot_delete_another_users_comment(): void
    {
        $owner = $this->activeUser();
        $attacker = $this->activeUser();
        $post = Posts::factory()->forOwner($owner)->create();
        $comment = Comments::factory()->forPost($post)->forOwner($owner)->create();

        $this
            ->withToken($this->apiTokenFor($attacker))
            ->postJson(route('api.comments.destroy', ['comment_id' => $comment->comment_id]))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'comment_id' => $comment->comment_id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_api_unfollow_only_removes_the_current_users_follow_row(): void
    {
        $followed = $this->activeUser();
        $currentUser = $this->activeUser();
        $otherFollower = $this->activeUser();

        $currentFollow = Follower::factory()->forFollower($currentUser)->following($followed)->create();
        $otherFollow = Follower::factory()->forFollower($otherFollower)->following($followed)->create();

        $this
            ->withToken($this->apiTokenFor($currentUser))
            ->postJson(route('api.follows.destroy', ['id' => $followed->id]))
            ->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Unfollow Successfully',
            ]);

        $this->assertDatabaseMissing('followers', [
            'id' => $currentFollow->id,
        ]);
        $this->assertDatabaseHas('followers', [
            'id' => $otherFollow->id,
            'user_id' => $otherFollower->id,
            'follow_id' => $followed->id,
        ]);
    }

    public function test_web_user_cannot_update_another_users_blog_or_take_ownership(): void
    {
        $owner = $this->activeUser();
        $attacker = $this->activeUser();
        $category = BlogCategory::factory()->create();
        $blog = Blog::factory()->forOwner($owner)->forCategory($category)->create([
            'title' => 'Original owner blog',
        ]);

        $this
            ->actingAs($attacker)
            ->post(route('blog.update', ['id' => $blog->id]), [
                'title' => 'Cross-owner blog update',
                'category' => $category->id,
                'description' => 'Attacker body',
            ])
            ->assertForbidden();

        $blog->refresh();

        $this->assertSame('Original owner blog', $blog->title);
        $this->assertSame($owner->id, (int) $blog->user_id);
    }

    public function test_web_user_cannot_delete_another_users_blog(): void
    {
        $owner = $this->activeUser();
        $attacker = $this->activeUser();
        $blog = Blog::factory()->forOwner($owner)->create();

        $this
            ->actingAs($attacker)
            ->getJson(route('blog.delete', ['blog_id' => $blog->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_api_user_cannot_update_another_users_blog_or_take_ownership(): void
    {
        $owner = $this->activeUser();
        $attacker = $this->activeUser();
        $category = BlogCategory::factory()->create();
        $blog = Blog::factory()->forOwner($owner)->forCategory($category)->create([
            'title' => 'Original API owner blog',
        ]);

        $this
            ->withToken($this->apiTokenFor($attacker))
            ->postJson(route('api.blogs.update', ['id' => $blog->id]), [
                'title' => 'Cross-owner API blog update',
                'category' => $category->id,
                'description' => 'Attacker API body',
            ])
            ->assertForbidden();

        $blog->refresh();

        $this->assertSame('Original API owner blog', $blog->title);
        $this->assertSame($owner->id, (int) $blog->user_id);
    }

    public function test_api_user_cannot_delete_another_users_blog(): void
    {
        $owner = $this->activeUser();
        $attacker = $this->activeUser();
        $blog = Blog::factory()->forOwner($owner)->create();

        $this
            ->withToken($this->apiTokenFor($attacker))
            ->postJson(route('api.blogs.destroy', ['id' => $blog->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'user_id' => $owner->id,
        ]);
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
        ]);
    }

    private function apiTokenFor(User $user): string
    {
        return $user->createToken('content-ownership-test')->plainTextToken;
    }
}
