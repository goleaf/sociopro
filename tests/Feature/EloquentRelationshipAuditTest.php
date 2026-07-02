<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\BlockUser;
use App\Models\Blog;
use App\Models\Blogcategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Comments;
use App\Models\Currency;
use App\Models\Event;
use App\Models\Follower;
use App\Models\Friendships;
use App\Models\Fundraiser;
use App\Models\Group;
use App\Models\Group_member;
use App\Models\Invite;
use App\Models\Marketplace;
use App\Models\Media_files;
use App\Models\Notification;
use App\Models\Page;
use App\Models\Page_like;
use App\Models\Pagecategory;
use App\Models\Post_share;
use App\Models\Posts;
use App\Models\Report;
use App\Models\SavedProduct;
use App\Models\Saveforlater;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class EloquentRelationshipAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_relationships_declare_specific_return_types(): void
    {
        foreach ($this->relationshipContracts() as $contract) {
            $reflection = new ReflectionMethod($contract['model'], $contract['method']);

            $this->assertTrue(
                $reflection->hasReturnType(),
                "{$contract['model']}::{$contract['method']}() must declare a relationship return type."
            );
            $this->assertSame(
                $contract['relation'],
                (string) $reflection->getReturnType(),
                "{$contract['model']}::{$contract['method']}() declares the wrong relationship return type."
            );
        }
    }

    public function test_model_relationships_use_expected_related_models_and_keys(): void
    {
        foreach ($this->relationshipContracts() as $contract) {
            $relationship = (new $contract['model'])->{$contract['method']}();

            $this->assertInstanceOf(Relation::class, $relationship);
            $this->assertInstanceOf($contract['relation'], $relationship);
            $this->assertSame($contract['related'], get_class($relationship->getRelated()));
            $this->assertSame($contract['foreign'], $relationship->getQualifiedForeignKeyName());

            if ($relationship instanceof BelongsTo) {
                $this->assertSame($contract['owner'], $relationship->getQualifiedOwnerKeyName());
            }

            if ($relationship instanceof HasMany) {
                $this->assertSame($contract['parent'], $relationship->getQualifiedParentKeyName());
            }
        }
    }

    public function test_many_to_many_relationships_use_explicit_pivot_tables_keys_and_metadata(): void
    {
        foreach ($this->belongsToManyContracts() as $contract) {
            $relationship = (new $contract['model'])->{$contract['method']}();

            $this->assertInstanceOf(BelongsToMany::class, $relationship);
            $this->assertSame($contract['related'], get_class($relationship->getRelated()));
            $this->assertSame($contract['table'], $relationship->getTable());
            $this->assertSame($contract['foreign_pivot_key'], $relationship->getForeignPivotKeyName());
            $this->assertSame($contract['related_pivot_key'], $relationship->getRelatedPivotKeyName());

            foreach ($contract['pivot_columns'] as $column) {
                $this->assertTrue(
                    $relationship->hasPivotColumn($column),
                    "{$contract['model']}::{$contract['method']}() must expose {$column} on the pivot."
                );
            }
        }
    }

    public function test_pivot_like_models_define_parent_relationships_for_their_foreign_keys(): void
    {
        foreach ($this->pivotModelRelationshipContracts() as $contract) {
            $relationship = (new $contract['model'])->{$contract['method']}();

            $this->assertInstanceOf(BelongsTo::class, $relationship);
            $this->assertSame($contract['related'], get_class($relationship->getRelated()));
            $this->assertSame($contract['foreign'], $relationship->getQualifiedForeignKeyName());
            $this->assertSame($contract['owner'], $relationship->getQualifiedOwnerKeyName());
        }
    }

    public function test_post_relationships_scope_comments_and_eager_load_related_records(): void
    {
        $post = new Posts;
        $post->forceFill([
            'user_id' => 1,
            'publisher' => 'post',
            'publisher_id' => 1,
            'post_type' => 'general',
            'privacy' => 'public',
            'status' => 'active',
            'created_at' => time(),
            'updated_at' => time(),
        ])->save();

        $rootComment = new Comments;
        $rootComment->forceFill([
            'user_id' => 1,
            'parent_id' => 0,
            'is_type' => 'post',
            'id_of_type' => $post->post_id,
            'description' => 'Root comment',
        ])->save();

        $childComment = new Comments;
        $childComment->forceFill([
            'user_id' => 2,
            'parent_id' => $rootComment->comment_id,
            'is_type' => 'post',
            'id_of_type' => $post->post_id,
            'description' => 'Child comment',
        ])->save();

        $otherTypeComment = new Comments;
        $otherTypeComment->forceFill([
            'user_id' => 3,
            'parent_id' => 0,
            'is_type' => 'video',
            'id_of_type' => $post->post_id,
            'description' => 'Video comment',
        ])->save();

        $report = new Report;
        $report->forceFill([
            'user_id' => 4,
            'post_id' => $post->post_id,
            'report' => 'spam',
            'status' => 0,
        ])->save();

        $share = new Post_share;
        $share->forceFill([
            'user_id' => 5,
            'post_id' => $post->post_id,
            'shared_on' => 'timeline',
        ])->save();

        $loadedPost = Posts::query()
            ->with(['comments.children', 'comments.post', 'reports.post', 'shares.post'])
            ->findOrFail($post->post_id);

        $this->assertSame(
            [$rootComment->comment_id, $childComment->comment_id],
            $loadedPost->comments->pluck('comment_id')->sort()->values()->all()
        );
        $this->assertFalse($loadedPost->comments->pluck('comment_id')->contains($otherTypeComment->comment_id));
        $this->assertSame([$childComment->comment_id], $rootComment->children()->pluck('comment_id')->all());
        $this->assertTrue($childComment->post->is($post));
        $this->assertTrue($report->post->is($post));
        $this->assertTrue($share->post->is($post));
        $this->assertSame([$report->id], $loadedPost->reports->pluck('id')->all());
        $this->assertSame([$share->id], $loadedPost->shares->pluck('id')->all());
    }

    /**
     * @return list<array{
     *     model: class-string<Model>,
     *     method: string,
     *     relation: class-string<Relation>,
     *     related: class-string<Model>,
     *     foreign: string,
     *     owner?: string,
     *     parent?: string
     * }>
     */
    private function relationshipContracts(): array
    {
        return [
            ['model' => Badge::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'batchs.user_id', 'owner' => 'users.id'],
            ['model' => Blog::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'blogs.user_id', 'owner' => 'users.id'],
            ['model' => Blog::class, 'method' => 'category', 'relation' => BelongsTo::class, 'related' => Blogcategory::class, 'foreign' => 'blogs.category_id', 'owner' => 'blogcategories.id'],
            ['model' => Blog::class, 'method' => 'cagtegory', 'relation' => BelongsTo::class, 'related' => Blogcategory::class, 'foreign' => 'blogs.category_id', 'owner' => 'blogcategories.id'],
            ['model' => Comments::class, 'method' => 'user', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'comments.user_id', 'owner' => 'users.id'],
            ['model' => Comments::class, 'method' => 'post', 'relation' => BelongsTo::class, 'related' => Posts::class, 'foreign' => 'comments.id_of_type', 'owner' => 'posts.post_id'],
            ['model' => Comments::class, 'method' => 'parent', 'relation' => BelongsTo::class, 'related' => Comments::class, 'foreign' => 'comments.parent_id', 'owner' => 'comments.comment_id'],
            ['model' => Comments::class, 'method' => 'children', 'relation' => HasMany::class, 'related' => Comments::class, 'foreign' => 'comments.parent_id', 'parent' => 'comments.comment_id'],
            ['model' => Event::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'events.user_id', 'owner' => 'users.id'],
            ['model' => Event::class, 'method' => 'inviteEvent', 'relation' => HasMany::class, 'related' => Invite::class, 'foreign' => 'invites.event_id', 'parent' => 'events.id'],
            ['model' => Friendships::class, 'method' => 'getFriend', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'friendships.requester', 'owner' => 'users.id'],
            ['model' => Friendships::class, 'method' => 'getFriendAccepter', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'friendships.accepter', 'owner' => 'users.id'],
            ['model' => Group::class, 'method' => 'getMember', 'relation' => HasMany::class, 'related' => Group_member::class, 'foreign' => 'group_members.group_id', 'parent' => 'groups.id'],
            ['model' => Group::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'groups.user_id', 'owner' => 'users.id'],
            ['model' => Group_member::class, 'method' => 'getGroup', 'relation' => BelongsTo::class, 'related' => Group::class, 'foreign' => 'group_members.group_id', 'owner' => 'groups.id'],
            ['model' => Group_member::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'group_members.user_id', 'owner' => 'users.id'],
            ['model' => Marketplace::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'marketplaces.user_id', 'owner' => 'users.id'],
            ['model' => Marketplace::class, 'method' => 'getCategory', 'relation' => BelongsTo::class, 'related' => Category::class, 'foreign' => 'marketplaces.category', 'owner' => 'categories.id'],
            ['model' => Marketplace::class, 'method' => 'getBrand', 'relation' => BelongsTo::class, 'related' => Brand::class, 'foreign' => 'marketplaces.brand', 'owner' => 'brands.id'],
            ['model' => Marketplace::class, 'method' => 'getCurrency', 'relation' => BelongsTo::class, 'related' => Currency::class, 'foreign' => 'marketplaces.currency_id', 'owner' => 'currencies.id'],
            ['model' => Media_files::class, 'method' => 'post', 'relation' => BelongsTo::class, 'related' => Posts::class, 'foreign' => 'media_files.post_id', 'owner' => 'posts.post_id'],
            ['model' => Notification::class, 'method' => 'getUserData', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'notifications.sender_user_id', 'owner' => 'users.id'],
            ['model' => Notification::class, 'method' => 'getEventData', 'relation' => BelongsTo::class, 'related' => Event::class, 'foreign' => 'notifications.event_id', 'owner' => 'events.id'],
            ['model' => Notification::class, 'method' => 'getGroupData', 'relation' => BelongsTo::class, 'related' => Group::class, 'foreign' => 'notifications.group_id', 'owner' => 'groups.id'],
            ['model' => Notification::class, 'method' => 'getFundraiserData', 'relation' => BelongsTo::class, 'related' => Fundraiser::class, 'foreign' => 'notifications.fundraiser_id', 'owner' => 'fundraisers.id'],
            ['model' => Page::class, 'method' => 'getCategory', 'relation' => BelongsTo::class, 'related' => Pagecategory::class, 'foreign' => 'pages.category_id', 'owner' => 'pagecategories.id'],
            ['model' => Page::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'pages.user_id', 'owner' => 'users.id'],
            ['model' => Page_like::class, 'method' => 'pageData', 'relation' => BelongsTo::class, 'related' => Page::class, 'foreign' => 'page_likes.page_id', 'owner' => 'pages.id'],
            ['model' => Posts::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'posts.user_id', 'owner' => 'users.id'],
            ['model' => Posts::class, 'method' => 'media_files', 'relation' => HasMany::class, 'related' => Media_files::class, 'foreign' => 'media_files.post_id', 'parent' => 'posts.post_id'],
            ['model' => Posts::class, 'method' => 'comments', 'relation' => HasMany::class, 'related' => Comments::class, 'foreign' => 'comments.id_of_type', 'parent' => 'posts.post_id'],
            ['model' => Posts::class, 'method' => 'reports', 'relation' => HasMany::class, 'related' => Report::class, 'foreign' => 'reports.post_id', 'parent' => 'posts.post_id'],
            ['model' => Posts::class, 'method' => 'shares', 'relation' => HasMany::class, 'related' => Post_share::class, 'foreign' => 'post_shares.post_id', 'parent' => 'posts.post_id'],
            ['model' => Report::class, 'method' => 'userData', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'reports.user_id', 'owner' => 'users.id'],
            ['model' => Report::class, 'method' => 'post', 'relation' => BelongsTo::class, 'related' => Posts::class, 'foreign' => 'reports.post_id', 'owner' => 'posts.post_id'],
            ['model' => Post_share::class, 'method' => 'post', 'relation' => BelongsTo::class, 'related' => Posts::class, 'foreign' => 'post_shares.post_id', 'owner' => 'posts.post_id'],
            ['model' => SavedProduct::class, 'method' => 'productData', 'relation' => BelongsTo::class, 'related' => Marketplace::class, 'foreign' => 'saved_products.product_id', 'owner' => 'marketplaces.id'],
            ['model' => Saveforlater::class, 'method' => 'getVideo', 'relation' => BelongsTo::class, 'related' => Video::class, 'foreign' => 'saveforlaters.video_id', 'owner' => 'videos.id'],
            ['model' => Video::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'videos.user_id', 'owner' => 'users.id'],
        ];
    }

    /**
     * @return list<array{
     *     model: class-string<Model>,
     *     method: string,
     *     related: class-string<Model>,
     *     table: string,
     *     foreign_pivot_key: string,
     *     related_pivot_key: string,
     *     pivot_columns: list<string>
     * }>
     */
    private function belongsToManyContracts(): array
    {
        return [
            ['model' => User::class, 'method' => 'blockedUsers', 'related' => User::class, 'table' => 'block_users', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'block_user', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'blockedByUsers', 'related' => User::class, 'table' => 'block_users', 'foreign_pivot_key' => 'block_user', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'followingUsers', 'related' => User::class, 'table' => 'followers', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'follow_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'followedByUsers', 'related' => User::class, 'table' => 'followers', 'foreign_pivot_key' => 'follow_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'followedPages', 'related' => Page::class, 'table' => 'followers', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'page_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'followedGroups', 'related' => Group::class, 'table' => 'followers', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'group_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'joinedGroups', 'related' => Group::class, 'table' => 'group_members', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'group_id', 'pivot_columns' => ['id', 'is_accepted', 'role', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'likedPages', 'related' => Page::class, 'table' => 'page_likes', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'page_id', 'pivot_columns' => ['id', 'role', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'savedProducts', 'related' => Marketplace::class, 'table' => 'saved_products', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'product_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'savedVideos', 'related' => Video::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'video_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'savedGroups', 'related' => Group::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'group_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'savedPosts', 'related' => Posts::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'post_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'savedMarketplaceItems', 'related' => Marketplace::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'marketplace_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'savedEvents', 'related' => Event::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'event_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => User::class, 'method' => 'savedBlogs', 'related' => Blog::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'user_id', 'related_pivot_key' => 'blog_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => Group::class, 'method' => 'followedByUsers', 'related' => User::class, 'table' => 'followers', 'foreign_pivot_key' => 'group_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => Group::class, 'method' => 'members', 'related' => User::class, 'table' => 'group_members', 'foreign_pivot_key' => 'group_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'is_accepted', 'role', 'created_at', 'updated_at']],
            ['model' => Group::class, 'method' => 'savedByUsers', 'related' => User::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'group_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => Page::class, 'method' => 'followedByUsers', 'related' => User::class, 'table' => 'followers', 'foreign_pivot_key' => 'page_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => Page::class, 'method' => 'likedByUsers', 'related' => User::class, 'table' => 'page_likes', 'foreign_pivot_key' => 'page_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'role', 'created_at', 'updated_at']],
            ['model' => Marketplace::class, 'method' => 'savedByUsers', 'related' => User::class, 'table' => 'saved_products', 'foreign_pivot_key' => 'product_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => Marketplace::class, 'method' => 'savedForLaterByUsers', 'related' => User::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'marketplace_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => Posts::class, 'method' => 'savedByUsers', 'related' => User::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'post_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => Event::class, 'method' => 'savedByUsers', 'related' => User::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'event_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => Blog::class, 'method' => 'savedByUsers', 'related' => User::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'blog_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
            ['model' => Video::class, 'method' => 'savedByUsers', 'related' => User::class, 'table' => 'saveforlaters', 'foreign_pivot_key' => 'video_id', 'related_pivot_key' => 'user_id', 'pivot_columns' => ['id', 'created_at', 'updated_at']],
        ];
    }

    /**
     * @return list<array{
     *     model: class-string<Model>,
     *     method: string,
     *     related: class-string<Model>,
     *     foreign: string,
     *     owner: string
     * }>
     */
    private function pivotModelRelationshipContracts(): array
    {
        return [
            ['model' => BlockUser::class, 'method' => 'user', 'related' => User::class, 'foreign' => 'block_users.user_id', 'owner' => 'users.id'],
            ['model' => BlockUser::class, 'method' => 'blockedUser', 'related' => User::class, 'foreign' => 'block_users.block_user', 'owner' => 'users.id'],
            ['model' => Follower::class, 'method' => 'user', 'related' => User::class, 'foreign' => 'followers.user_id', 'owner' => 'users.id'],
            ['model' => Follower::class, 'method' => 'followedUser', 'related' => User::class, 'foreign' => 'followers.follow_id', 'owner' => 'users.id'],
            ['model' => Follower::class, 'method' => 'page', 'related' => Page::class, 'foreign' => 'followers.page_id', 'owner' => 'pages.id'],
            ['model' => Follower::class, 'method' => 'group', 'related' => Group::class, 'foreign' => 'followers.group_id', 'owner' => 'groups.id'],
            ['model' => Group_member::class, 'method' => 'group', 'related' => Group::class, 'foreign' => 'group_members.group_id', 'owner' => 'groups.id'],
            ['model' => Group_member::class, 'method' => 'user', 'related' => User::class, 'foreign' => 'group_members.user_id', 'owner' => 'users.id'],
            ['model' => Page_like::class, 'method' => 'user', 'related' => User::class, 'foreign' => 'page_likes.user_id', 'owner' => 'users.id'],
            ['model' => Page_like::class, 'method' => 'page', 'related' => Page::class, 'foreign' => 'page_likes.page_id', 'owner' => 'pages.id'],
            ['model' => Post_share::class, 'method' => 'user', 'related' => User::class, 'foreign' => 'post_shares.user_id', 'owner' => 'users.id'],
            ['model' => SavedProduct::class, 'method' => 'user', 'related' => User::class, 'foreign' => 'saved_products.user_id', 'owner' => 'users.id'],
            ['model' => SavedProduct::class, 'method' => 'product', 'related' => Marketplace::class, 'foreign' => 'saved_products.product_id', 'owner' => 'marketplaces.id'],
            ['model' => Saveforlater::class, 'method' => 'user', 'related' => User::class, 'foreign' => 'saveforlaters.user_id', 'owner' => 'users.id'],
            ['model' => Saveforlater::class, 'method' => 'video', 'related' => Video::class, 'foreign' => 'saveforlaters.video_id', 'owner' => 'videos.id'],
            ['model' => Saveforlater::class, 'method' => 'group', 'related' => Group::class, 'foreign' => 'saveforlaters.group_id', 'owner' => 'groups.id'],
            ['model' => Saveforlater::class, 'method' => 'post', 'related' => Posts::class, 'foreign' => 'saveforlaters.post_id', 'owner' => 'posts.post_id'],
            ['model' => Saveforlater::class, 'method' => 'marketplace', 'related' => Marketplace::class, 'foreign' => 'saveforlaters.marketplace_id', 'owner' => 'marketplaces.id'],
            ['model' => Saveforlater::class, 'method' => 'event', 'related' => Event::class, 'foreign' => 'saveforlaters.event_id', 'owner' => 'events.id'],
            ['model' => Saveforlater::class, 'method' => 'blog', 'related' => Blog::class, 'foreign' => 'saveforlaters.blog_id', 'owner' => 'blogs.id'],
        ];
    }
}
