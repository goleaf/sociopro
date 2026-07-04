<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Albums;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Comments;
use App\Models\Follower;
use App\Models\Job;
use App\Models\JobApply;
use App\Models\JobWishlist;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\PageLike;
use App\Models\PaymentHistoryEntry;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityOneAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_unfollow_deletes_only_the_authenticated_users_follow_row(): void
    {
        $viewer = $this->activeUser();
        $otherFollower = $this->activeUser();
        $target = $this->activeUser();

        $this->follower($viewer, $target);
        $this->follower($otherFollower, $target);

        $this
            ->withToken($this->apiTokenFor($viewer))
            ->postJson(route('api.follows.destroy', $target->id))
            ->assertOk();

        $this->assertDatabaseMissing('followers', [
            'user_id' => $viewer->id,
            'follow_id' => $target->id,
        ]);
        $this->assertDatabaseHas('followers', [
            'user_id' => $otherFollower->id,
            'follow_id' => $target->id,
        ]);
    }

    public function test_api_user_cannot_delete_another_users_comment(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $post = $this->postFor($owner);
        $comment = $this->commentFor($owner, $post);

        $this
            ->withToken($this->apiTokenFor($otherUser))
            ->postJson(route('api.comments.destroy', $comment->comment_id))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'comment_id' => $comment->comment_id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_api_user_cannot_delete_another_users_page_or_page_likes(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $page = Page::factory()->forOwner($owner)->create();
        $pageLike = PageLike::factory()->forUser($owner)->forPage($page)->create();

        $this
            ->withToken($this->apiTokenFor($otherUser))
            ->postJson(route('api.pages.destroy', $page->id))
            ->assertForbidden();

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('page_likes', [
            'id' => $pageLike->id,
            'page_id' => $page->id,
        ]);
    }

    public function test_api_user_cannot_delete_another_users_job_or_related_rows(): void
    {
        $owner = $this->activeUser();
        $applicant = $this->activeUser();
        $otherUser = $this->activeUser();
        $job = $this->jobFor($owner);
        $paymentHistory = PaymentHistoryEntry::factory()->create([
            'user_id' => $owner->id,
            'item_id' => $job->id,
        ]);
        $wishlist = JobWishlist::query()->create([
            'user_id' => $applicant->id,
            'job_id' => $job->id,
        ]);
        $application = JobApply::query()->create([
            'job_id' => $job->id,
            'owner_id' => $owner->id,
            'user_id' => $applicant->id,
            'email' => 'applicant@example.test',
        ]);

        $this
            ->withToken($this->apiTokenFor($otherUser))
            ->postJson(route('api.jobs.destroy', $job->id))
            ->assertForbidden();

        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('payment_histories', ['id' => $paymentHistory->id]);
        $this->assertDatabaseHas('job_wishlists', ['id' => $wishlist->id]);
        $this->assertDatabaseHas('job_applies', ['id' => $application->id]);
    }

    public function test_web_user_cannot_delete_another_users_comment_or_post_from_timeline(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $post = $this->postFor($owner);
        $comment = $this->commentFor($owner, $post);

        $this
            ->actingAs($otherUser)
            ->get(route('comment.delete', ['comment_id' => $comment->comment_id]))
            ->assertForbidden();

        $this
            ->actingAs($otherUser)
            ->get(route('post.delete', ['post_id' => $post->post_id]))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'comment_id' => $comment->comment_id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('posts', [
            'post_id' => $post->post_id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_web_unfriend_does_not_delete_another_users_follow_row_for_the_target(): void
    {
        $viewer = $this->activeUser();
        $target = $this->activeUser();
        $otherFollower = $this->activeUser();

        $this->follower($viewer, $target);
        $this->follower($otherFollower, $target);

        $this
            ->actingAs($viewer)
            ->get(route('user.unfriend', $target->id))
            ->assertOk();

        $this->assertDatabaseMissing('followers', [
            'user_id' => $viewer->id,
            'follow_id' => $target->id,
        ]);
        $this->assertDatabaseHas('followers', [
            'user_id' => $otherFollower->id,
            'follow_id' => $target->id,
        ]);
    }

    public function test_profile_user_cannot_delete_another_users_album_or_media(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $album = Albums::factory()->create(['user_id' => $owner->id]);
        $media = MediaFile::factory()->image()->create([
            'user_id' => $owner->id,
            'album_id' => $album->id,
        ]);

        $this
            ->actingAs($otherUser)
            ->get(route('profile.album', [
                'action_type' => 'delete',
                'album_id' => $album->id,
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('albums', [
            'id' => $album->id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'album_id' => $album->id,
        ]);
    }

    public function test_blog_user_cannot_edit_update_or_delete_another_users_blog(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $category = $this->blogCategory();
        $blog = $this->blogFor($owner, [
            'category_id' => $category->id,
            'title' => 'Owner blog',
            'description' => 'Owner description',
        ]);

        $this
            ->actingAs($otherUser)
            ->get(route('blog.edit', $blog->id))
            ->assertForbidden();

        $this
            ->actingAs($otherUser)
            ->post(route('blog.update', $blog->id), [
                'title' => 'Hijacked blog',
                'category' => $category->id,
                'description' => 'Hijacked description',
            ])
            ->assertForbidden();

        $this
            ->actingAs($otherUser)
            ->get(route('blog.delete', ['blog_id' => $blog->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'user_id' => $owner->id,
            'title' => 'Owner blog',
            'description' => 'Owner description',
        ]);
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
        ]);
    }

    private function apiTokenFor(User $user): string
    {
        return $user->createToken('priority-one-access-control-test')->plainTextToken;
    }

    private function follower(User $user, User $target): Follower
    {
        $follower = new Follower;
        $follower->user_id = $user->id;
        $follower->follow_id = $target->id;
        $follower->save();

        return $follower;
    }

    private function postFor(User $user): Posts
    {
        return Posts::factory()->forOwner($user)->create([
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => 'general',
            'privacy' => Visibility::Public->value,
            'tagged_user_ids' => json_encode([]),
            'location' => '',
            'description' => 'Priority one post',
            'status' => ContentStatus::Active->value,
            'user_reacts' => json_encode([]),
            'shared_user' => json_encode([]),
        ]);
    }

    private function commentFor(User $user, Posts $post): Comments
    {
        return Comments::query()->create([
            'parent_id' => 0,
            'user_id' => $user->id,
            'is_type' => 'post',
            'id_of_type' => $post->post_id,
            'description' => 'Priority one comment',
            'user_reacts' => json_encode([]),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function jobFor(User $user): Job
    {
        return Job::query()->create([
            'user_id' => $user->id,
            'title' => 'Priority one job',
            'company' => 'Sociopro',
            'type' => 'full_time',
            'location' => 'Remote',
            'description' => 'Priority one job description',
            'status' => '1',
            'is_published' => true,
        ]);
    }

    private function blogCategory(string $name = 'Priority category'): BlogCategory
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
        $blog->title = $attributes['title'] ?? 'Priority blog';
        $blog->description = $attributes['description'] ?? 'Priority blog description';
        $blog->tag = $attributes['tag'] ?? json_encode([]);
        $blog->view = $attributes['view'] ?? json_encode([]);
        $blog->save();

        return $blog;
    }
}
