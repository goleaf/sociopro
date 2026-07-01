<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Blog;
use App\Models\Blogcategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Comments;
use App\Models\Currency;
use App\Models\Event;
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
use App\Models\Posts;
use App\Models\Report;
use App\Models\SavedProduct;
use App\Models\Saveforlater;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionMethod;
use Tests\TestCase;

class EloquentRelationshipAuditTest extends TestCase
{
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
            ['model' => Report::class, 'method' => 'userData', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'reports.user_id', 'owner' => 'users.id'],
            ['model' => SavedProduct::class, 'method' => 'productData', 'relation' => BelongsTo::class, 'related' => Marketplace::class, 'foreign' => 'saved_products.product_id', 'owner' => 'marketplaces.id'],
            ['model' => Saveforlater::class, 'method' => 'getVideo', 'relation' => BelongsTo::class, 'related' => Video::class, 'foreign' => 'saveforlaters.video_id', 'owner' => 'videos.id'],
            ['model' => Video::class, 'method' => 'getUser', 'relation' => BelongsTo::class, 'related' => User::class, 'foreign' => 'videos.user_id', 'owner' => 'users.id'],
        ];
    }
}
