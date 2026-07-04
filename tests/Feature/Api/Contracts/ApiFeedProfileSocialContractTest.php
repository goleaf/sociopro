<?php

namespace Tests\Feature\Api\Contracts;

use App\Enums\Visibility;
use App\Models\Follower;
use App\Models\Friendships;
use App\Models\Posts;

class ApiFeedProfileSocialContractTest extends ApiContractTestCase
{
    public function test_timeline_and_load_timeline_return_current_top_level_shape(): void
    {
        $user = $this->activeApiUser();
        $post = $this->postFor($user, [
            'description' => 'Contract timeline post',
        ]);
        $headers = $this->apiHeaders($user);

        $this->getJson(route('api.timeline.index'), $headers)
            ->assertOk()
            ->assertJsonStructure([
                'stories',
                'post' => [
                    '*' => [
                        'post_id',
                        'user_id',
                        'publisher',
                        'publisherId',
                        'post_type',
                        'privacy',
                        'description',
                        'commentsCount',
                        'reaction_counts',
                    ],
                ],
            ])
            ->assertJsonPath('post.0.post_id', $post->post_id);

        auth()->forgetGuards();

        $this->getJson(route('api.timeline.load'), $headers)
            ->assertOk()
            ->assertJsonStructure([
                'post' => [
                    '*' => [
                        'post_id',
                        'user_id',
                        'publisher',
                        'post_type',
                        'privacy',
                    ],
                ],
            ])
            ->assertJsonPath('post.0.post_id', $post->post_id);
    }

    public function test_create_post_currently_accepts_missing_business_fields_and_creates_empty_post(): void
    {
        $user = $this->activeApiUser();

        $this->postJson(route('api.posts.store'), [], $this->apiHeaders($user))
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Your post successfully publidhed',
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => 'general',
            'description' => '',
        ]);
    }

    public function test_create_edit_delete_post_smoke_contracts(): void
    {
        $user = $this->activeApiUser();
        $headers = $this->apiHeaders($user);

        $this->postJson(route('api.posts.store'), [
            'privacy' => Visibility::Public->value,
            'description' => 'Created through contract smoke test',
        ], $headers)
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Your post successfully publidhed',
            ]);

        $post = Posts::query()
            ->where('user_id', $user->id)
            ->where('description', 'Created through contract smoke test')
            ->firstOrFail();

        auth()->forgetGuards();

        $this->postJson(route('api.posts.update', $post->post_id), [
            'privacy' => Visibility::Public->value,
            'description' => 'Edited through contract smoke test',
        ], $headers)
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Your post successfully updated',
            ]);

        $this->assertDatabaseHas('posts', [
            'post_id' => $post->post_id,
            'description' => 'Edited through contract smoke test',
        ]);

        auth()->forgetGuards();

        $this->postJson(route('api.posts.update', 999999), [
            'privacy' => Visibility::Public->value,
            'description' => 'Missing post edit',
        ], $headers)
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Your post successfully updated',
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.posts.destroy', $post->post_id), [], $headers)
            ->assertOk()
            ->assertJsonStructure([
                'alertMessage',
                'fadeOutElem',
            ]);

        $this->assertDatabaseMissing('posts', [
            'post_id' => $post->post_id,
        ]);
    }

    public function test_post_comment_get_comment_and_comment_delete_contracts(): void
    {
        $user = $this->activeApiUser();
        $post = $this->postFor($user);
        $headers = $this->apiHeaders($user);

        $this->postJson(route('api.comments.store'), [
            'comment' => 'comment',
            'parent_id' => 0,
            'is_type' => 'post',
            'id_of_type' => $post->post_id,
            'description' => 'Contract comment body',
        ], $headers)
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Your comment successfully publidhed',
            ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'is_type' => 'post',
            'id_of_type' => $post->post_id,
            'description' => 'Contract comment body',
        ]);

        $comment = $post->comments()->firstOrFail();

        auth()->forgetGuards();

        $this->getJson(route('api.comments.index', $post->post_id), $headers)
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'comment_id',
                    'post_id',
                    'user_id',
                    'post_type',
                    'description',
                    'reaction_counts',
                    'replies',
                ],
            ])
            ->assertJsonPath('0.comment_id', $comment->comment_id);

        auth()->forgetGuards();

        $this->postJson(route('api.comments.destroy', $comment->comment_id), [], $headers)
            ->assertOk()
            ->assertJsonStructure([
                'alertMessage',
                'fadeOutElem',
            ]);

        $this->assertDatabaseMissing('comments', [
            'comment_id' => $comment->comment_id,
        ]);
    }

    public function test_profile_and_other_profile_return_current_top_level_keys(): void
    {
        $viewer = $this->activeApiUser(['name' => 'Contract Viewer']);
        $profile = $this->activeApiUser(['name' => 'Contract Profile']);

        $this->getJson(route('api.me.profile.show'), $this->apiHeaders($viewer))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_role',
                'username',
                'email',
                'name',
                'friend',
                'followers',
                'photo',
                'cover_photo',
                'posts',
                'friends',
            ])
            ->assertJsonPath('id', $viewer->id);

        auth()->forgetGuards();

        $this->getJson(route('api.profiles.show', $profile->id), $this->apiHeaders($viewer))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'thrade',
                'requested',
                'follow',
                'username',
                'name',
                'followers',
                'photo',
                'cover_photo',
                'posts',
                'friends',
            ])
            ->assertJsonPath('id', $profile->id)
            ->assertJsonPath('name', 'Contract Profile');
    }

    public function test_friend_and_follow_routes_preserve_simple_side_effect_contracts(): void
    {
        $user = $this->activeApiUser();
        $otherUser = $this->activeApiUser();
        $headers = $this->apiHeaders($user);

        $this->getJson(route('api.friends.index'), $headers)
            ->assertOk()
            ->assertJsonStructure([
                'friendsList',
            ]);

        auth()->forgetGuards();

        $this->getJson(route('api.friend_requests.index'), $headers)
            ->assertOk()
            ->assertJsonStructure([
                'friendsList',
            ]);

        auth()->forgetGuards();

        $this->postJson(route('api.friends.store', $otherUser->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'send friend request Successfully',
            ]);

        $this->assertDatabaseHas('friendships', [
            'requester' => $user->id,
            'accepter' => $otherUser->id,
            'is_accepted' => 0,
        ]);
        $this->assertDatabaseHas('notifications', [
            'sender_user_id' => $user->id,
            'reciver_user_id' => $otherUser->id,
            'type' => 'profile',
        ]);

        Friendships::query()
            ->where('requester', $user->id)
            ->where('accepter', $otherUser->id)
            ->update(['is_accepted' => 1]);

        auth()->forgetGuards();

        $this->postJson(route('api.friends.destroy', $otherUser->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'unfriend Successfully',
            ]);

        $this->assertDatabaseMissing('friendships', [
            'requester' => $user->id,
            'accepter' => $otherUser->id,
        ]);

        auth()->forgetGuards();

        $this->postJson(route('api.follows.store', $otherUser->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Follow Successfully',
            ]);

        $this->assertDatabaseHas('followers', [
            'user_id' => $user->id,
            'follow_id' => $otherUser->id,
        ]);

        auth()->forgetGuards();

        $this->postJson(route('api.follows.destroy', $otherUser->id), [], $headers)
            ->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Unfollow Successfully',
            ]);

        $this->assertSame(0, Follower::query()
            ->where('user_id', $user->id)
            ->where('follow_id', $otherUser->id)
            ->count());
    }
}
