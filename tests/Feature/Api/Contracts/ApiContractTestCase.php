<?php

namespace Tests\Feature\Api\Contracts;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Chat;
use App\Models\Comments;
use App\Models\Currency;
use App\Models\Event;
use App\Models\Group;
use App\Models\Marketplace;
use App\Models\MessageThread;
use App\Models\Notification;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiContractTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function activeApiUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'timezone' => 'UTC',
            'lastActive' => now()->subMinute(),
            'date_of_birth' => strtotime('1990-01-01'),
        ], $attributes));
    }

    /**
     * @param  list<string>  $abilities
     * @return array<string, string>
     */
    protected function apiHeaders(User $user, array $abilities = ['*']): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiTokenFor($user, $abilities),
        ];
    }

    /**
     * @param  list<string>  $abilities
     */
    protected function apiTokenFor(User $user, array $abilities = ['*']): string
    {
        return $user->createToken('api-contract-test', $abilities, now()->addDay())->plainTextToken;
    }

    /**
     * @return array<string, mixed>
     */
    protected function legacyAuthenticationPayload(): array
    {
        return [
            'success' => false,
            'message' => 'Unauthorized access',
            'error' => [
                'code' => 'AUTHENTICATION_ERROR',
                'category' => 'authentication',
                'message' => 'Unauthorized access',
                'http_status' => 401,
                'details' => [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function postFor(User $user, array $attributes = []): Posts
    {
        return Posts::factory()
            ->forOwner($user)
            ->create(array_merge([
                'privacy' => Visibility::Public->value,
                'tagged_user_ids' => json_encode([]),
                'user_reacts' => json_encode([]),
                'description' => 'API contract post body',
            ], $attributes));
    }

    protected function commentFor(User $user, Posts $post, string $description = 'API contract comment'): Comments
    {
        return Comments::factory()
            ->forOwner($user)
            ->forPost($post)
            ->create([
                'description' => $description,
                'parent_id' => 0,
                'user_reacts' => json_encode([]),
                'created_at' => (string) time(),
                'updated_at' => (string) time(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function pageFor(User $owner, array $attributes = []): Page
    {
        return Page::factory()
            ->forOwner($owner)
            ->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function groupFor(User $owner, array $attributes = []): Group
    {
        return Group::factory()->create(array_merge([
            'user_id' => $owner->id,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function eventFor(User $owner, array $attributes = []): Event
    {
        return Event::factory()
            ->forOwner($owner)
            ->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function blogFor(User $owner, array $attributes = []): Blog
    {
        return Blog::factory()
            ->forOwner($owner)
            ->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function marketplaceFor(User $owner, array $attributes = []): Marketplace
    {
        [$category, $brand, $currency] = $this->marketplaceLookups();

        return Marketplace::factory()
            ->forOwner($owner)
            ->forCategory($category)
            ->forBrand($brand)
            ->forCurrency($currency)
            ->active()
            ->create($attributes);
    }

    /**
     * @return array{Category, Brand, Currency}
     */
    protected function marketplaceLookups(): array
    {
        return [
            Category::factory()->create(),
            Brand::factory()->create(),
            Currency::factory()->create(),
        ];
    }

    protected function pageCategory(): PageCategory
    {
        return PageCategory::factory()->create();
    }

    protected function blogCategory(): BlogCategory
    {
        return BlogCategory::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function notificationFor(User $receiver, User $sender, array $attributes = []): Notification
    {
        $notification = new Notification;
        $notification->forceFill(array_merge([
            'sender_user_id' => $sender->id,
            'reciver_user_id' => $receiver->id,
            'type' => 'friend_request',
            'status' => 0,
            'view' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes))->save();

        return $notification;
    }

    protected function chatThreadBetween(User $sender, User $receiver): MessageThread
    {
        return MessageThread::factory()
            ->between($sender, $receiver)
            ->create(['chat_center' => 'chat']);
    }

    protected function chatMessage(MessageThread $thread, User $sender, User $receiver): Chat
    {
        return Chat::factory()
            ->forThread($thread)
            ->fromTo($sender, $receiver)
            ->create([
                'message' => 'API contract chat message',
                'read_status' => 0,
            ]);
    }
}
