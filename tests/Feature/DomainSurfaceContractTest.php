<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\MediaFileType;
use App\Enums\PaymentGatewayIdentifier;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\BlogCategory;
use App\Models\Chat;
use App\Models\Follower;
use App\Models\Friendships;
use App\Models\JobApply;
use App\Models\Marketplace;
use App\Models\MediaFile;
use App\Models\MessageThread;
use App\Models\Page;
use App\Models\PageLike;
use App\Models\PaymentGateway;
use App\Models\Sponsor;
use App\Models\Stories;
use App\Models\User;
use App\Policies\MarketplacePolicy;
use App\Policies\PagePolicy;
use App\Providers\RouteServiceProvider;
use App\Queries\Jobs\JobApplicationExportQuery;
use App\Queries\Pages\PageCardsQuery;
use App\Queries\StoriesQuery;
use App\Rules\PostMediaFile;
use App\Support\Addons\AddonPackageImportResult;
use App\Support\Addons\AddonPackageManifestParser;
use App\Support\Install\InstallSqlImportResult;
use App\Support\Install\InstallSqlStatementReader;
use App\View\Components\GuestLayout;
use App\ViewModels\ProfileFollowList;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class DomainSurfaceContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $methods
     */
    #[DataProvider('requestedMethodsProvider')]
    public function test_requested_domain_methods_are_present(string $class, array $methods): void
    {
        $this->assertTrue(class_exists($class), "{$class} is not loadable.");

        foreach ($methods as $method) {
            $this->assertTrue(method_exists($class, $method), "{$class}::{$method} is missing.");
            $this->assertFalse((new ReflectionMethod($class, $method))->isAbstract(), "{$class}::{$method} is abstract.");
        }
    }

    #[DataProvider('requestedModelClassesWithoutListedMethodsProvider')]
    public function test_requested_model_classes_without_listed_methods_are_eloquent_models(string $class): void
    {
        $this->assertTrue(class_exists($class), "{$class} is not loadable.");
        $this->assertTrue(is_subclass_of($class, Model::class), "{$class} is not an Eloquent model.");
    }

    public function test_chat_and_message_thread_keep_legacy_aliases_and_query_scopes(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $outsider = User::factory()->create();

        $thread = MessageThread::factory()->between($sender, $receiver)->create([
            'chat_center' => 'marketplace',
        ]);

        $aliasThread = new MessageThread;
        $aliasThread->receiver_id = $receiver->id;
        $aliasThread->chat_center = 'marketplace';

        $this->assertSame($receiver->id, $aliasThread->reciver_id);
        $this->assertSame('marketplace', $aliasThread->chatcenter);
        $this->assertSame($receiver->id, $thread->receiver_id);
        $this->assertSame('marketplace', $thread->chat_center);

        $unreadToReceiver = Chat::factory()
            ->forThread($thread)
            ->fromTo($sender, $receiver)
            ->unread()
            ->create();
        $reverseUnread = Chat::factory()
            ->forThread($thread)
            ->fromTo($receiver, $sender)
            ->unread()
            ->create();
        Chat::factory()
            ->forThread($thread)
            ->fromTo($sender, $receiver)
            ->read()
            ->create();
        Chat::factory()
            ->fromTo($sender, $outsider)
            ->unread()
            ->create();

        $aliasChat = new Chat;
        $aliasChat->message_thread_id = $thread->id;
        $aliasChat->receiver_id = $receiver->id;
        $aliasChat->chat_center = 'marketplace';

        $this->assertSame($thread->id, $aliasChat->message_thrade);
        $this->assertSame($receiver->id, $aliasChat->reciver_id);
        $this->assertSame('marketplace', $aliasChat->chatcenter);
        $this->assertTrue($unreadToReceiver->isParticipant($sender->id));
        $this->assertFalse($unreadToReceiver->isParticipant($outsider->id));

        $this->assertSame(
            [$unreadToReceiver->id],
            Chat::query()
                ->forMessageThread($thread->id)
                ->unreadForReceiver($receiver->id)
                ->orderBy('id')
                ->pluck('id')
                ->all()
        );
        $this->assertSame(
            [$unreadToReceiver->id, $reverseUnread->id],
            Chat::query()
                ->betweenParticipants($sender->id, $receiver->id)
                ->where('read_status', 0)
                ->orderBy('id')
                ->pluck('id')
                ->all()
        );
        $this->assertSame(
            [$thread->id],
            MessageThread::query()
                ->betweenParticipants($sender->id, $receiver->id)
                ->forParticipant($sender->id)
                ->pluck('id')
                ->all()
        );
    }

    public function test_model_scope_contracts_filter_the_expected_legacy_columns(): void
    {
        $firstCategory = BlogCategory::unguarded(fn () => BlogCategory::query()->create(['name' => 'First']));
        $secondCategory = BlogCategory::unguarded(fn () => BlogCategory::query()->create(['name' => 'Second']));

        $this->assertSame(
            [$firstCategory->id, $secondCategory->id],
            BlogCategory::query()->forSelect()->pluck('id')->all()
        );

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownerSponsor = Sponsor::factory()->forUser($owner)->create();
        Sponsor::factory()->forUser($otherUser)->create();

        $this->assertSame([$ownerSponsor->id], Sponsor::query()->forUser($owner->id)->pluck('id')->all());

        $image = MediaFile::factory()->image()->create();
        $video = MediaFile::factory()->video()->create();

        $this->assertSame([$image->id], MediaFile::query()->ofType(MediaFileType::Image)->pluck('id')->all());
        $this->assertSame([$video->id], MediaFile::query()->ofType(MediaFileType::Video->value)->pluck('id')->all());

        $stripe = PaymentGateway::factory()->stripe()->create([
            'keys' => ['public_key' => 'pk_test_contract'],
        ]);
        $paypal = PaymentGateway::factory()->paypal()->create();

        $stripeGatewayIds = PaymentGateway::query()
            ->forIdentifier(PaymentGatewayIdentifier::Stripe)
            ->pluck('id')
            ->all();

        $this->assertContains($stripe->id, $stripeGatewayIds);
        $this->assertNotContains($paypal->id, $stripeGatewayIds);
        $this->assertTrue($stripe->isEnabled());
        $this->assertTrue($stripe->isInTestMode());
        $this->assertSame(['public_key' => 'pk_test_contract'], $stripe->decodedKeys());
    }

    public function test_friendship_other_user_id_returns_the_opposite_participant(): void
    {
        $requester = User::factory()->create();
        $accepter = User::factory()->create();
        $friendship = Friendships::factory()
            ->requester($requester)
            ->accepter($accepter)
            ->accepted()
            ->create();

        $this->assertSame($accepter->id, $friendship->otherUserId($requester->id));
        $this->assertSame($requester->id, $friendship->otherUserId($accepter->id));
        $this->assertSame($accepter->id, $friendship->otherUserId(User::factory()->create()->id));
    }

    public function test_page_and_marketplace_policies_enforce_admin_owner_and_message_contracts(): void
    {
        $admin = User::factory()->create(['user_role' => UserRole::Admin->value]);
        $owner = User::factory()->create(['user_role' => UserRole::General->value]);
        $otherUser = User::factory()->create(['user_role' => UserRole::General->value]);

        $marketplace = Marketplace::factory()->forOwner($owner)->create();
        $page = Page::factory()->forOwner($owner)->create();

        $marketplacePolicy = new MarketplacePolicy;
        $pagePolicy = new PagePolicy;

        $this->assertTrue($marketplacePolicy->before($admin, 'update'));
        $this->assertNull($marketplacePolicy->before($admin, 'messageSeller'));
        $this->assertTrue($marketplacePolicy->create($owner));
        $this->assertTrue($marketplacePolicy->update($owner, $marketplace));
        $this->assertTrue($marketplacePolicy->delete($owner, $marketplace));
        $this->assertFalse($marketplacePolicy->update($otherUser, $marketplace));
        $this->assertFalse($marketplacePolicy->delete($otherUser, $marketplace));
        $this->assertFalse($marketplacePolicy->messageSeller($owner, $marketplace));
        $this->assertTrue($marketplacePolicy->messageSeller($otherUser, $marketplace));

        $this->assertTrue($pagePolicy->before($admin, 'update'));
        $this->assertTrue($pagePolicy->create($owner));
        $this->assertTrue($pagePolicy->update($owner, $page));
        $this->assertTrue($pagePolicy->delete($owner, $page));
        $this->assertFalse($pagePolicy->update($otherUser, $page));
        $this->assertFalse($pagePolicy->delete($otherUser, $page));
    }

    public function test_query_objects_filter_rows_and_preload_card_contracts(): void
    {
        $viewer = User::factory()->create();
        $friend = User::factory()->create();
        $pageLikedByViewer = Page::factory()->create();
        $suggestedPage = Page::factory()->create();
        $unrelatedPage = Page::factory()->create();

        PageLike::factory()->forUser($viewer)->forPage($pageLikedByViewer)->create();
        PageLike::factory()->forUser($friend)->forPage($suggestedPage)->create();
        PageLike::factory()->forUser($friend)->forPage($unrelatedPage)->create();
        PageLike::factory()->forUser($viewer)->forPage($unrelatedPage)->create();

        $pageCards = new PageCardsQuery;

        $this->assertSame(
            [$suggestedPage->id],
            $pageCards
                ->suggestedForViewer($viewer->id, [$friend->id])
                ->pluck('id')
                ->all()
        );

        $profileCard = $pageCards
            ->profileForViewer($viewer->id)
            ->whereKey($pageLikedByViewer->id)
            ->firstOrFail();

        $this->assertArrayHasKey('liked_by_current_user', $profileCard->getAttributes());
        $this->assertArrayHasKey('liked_by_users_count', $profileCard->getAttributes());
        $this->assertArrayHasKey('posts_count', $profileCard->getAttributes());

        $firstApplication = JobApply::query()->create([
            'owner_id' => $viewer->id,
            'user_id' => $friend->id,
            'email' => 'first@example.test',
        ]);
        JobApply::query()->create([
            'owner_id' => $friend->id,
            'user_id' => $viewer->id,
            'email' => 'other@example.test',
        ]);

        $this->assertSame(
            [$firstApplication->id],
            (new JobApplicationExportQuery)->forOwner($viewer->id)->pluck('id')->all()
        );
    }

    public function test_stories_query_returns_visible_active_recent_stories_with_owner_columns(): void
    {
        $viewer = User::factory()->create();
        $friendOwner = User::factory()->create([
            'friends' => json_encode([$viewer->id]),
        ]);
        $otherOwner = User::factory()->create([
            'friends' => json_encode([]),
        ]);

        $visibleFriendStory = Stories::factory()->forUser($friendOwner)->create([
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Active->value,
            'created_at' => time(),
        ]);
        $ownPrivateStory = Stories::factory()->forUser($viewer)->create([
            'privacy' => Visibility::Private->value,
            'status' => ContentStatus::Active->value,
            'created_at' => time(),
        ]);
        Stories::factory()->forUser($otherOwner)->create([
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Active->value,
            'created_at' => time(),
        ]);
        Stories::factory()->forUser($friendOwner)->create([
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Active->value,
            'created_at' => time() - 90000,
        ]);

        $storyIds = StoriesQuery::visibleFor($viewer)
            ->orderBy('stories.story_id')
            ->pluck('stories.story_id')
            ->all();

        $this->assertSame([$ownPrivateStory->story_id, $visibleFriendStory->story_id], $storyIds);
        $this->assertNotNull(StoriesQuery::findWithOwner($visibleFriendStory->story_id));
    }

    public function test_route_service_provider_rate_limit_helpers_normalize_request_keys(): void
    {
        $provider = new RouteServiceProvider($this->app);
        $request = Request::create(
            '/api/search',
            'POST',
            ['email' => '  Person@Example.TEST  '],
            [],
            [],
            ['REMOTE_ADDR' => '203.0.113.10']
        );
        $route = new Route(['POST'], 'api/search', []);
        $route->name('api.search');
        $request->setRouteResolver(fn () => $route);

        $this->assertSame(
            'email:person@example.test',
            $this->invokeRouteProviderMethod($provider, 'normalizedInput', $request, 'email')
        );
        $this->assertSame('ip:203.0.113.10', $this->invokeRouteProviderMethod($provider, 'clientKey', $request));
        $this->assertSame('api.search', $this->invokeRouteProviderMethod($provider, 'routeName', $request));
        $this->assertSame(
            'ip:203.0.113.10:api.search',
            $this->invokeRouteProviderMethod($provider, 'routeScopedKey', $request)
        );
        $this->assertSame(
            'email:person@example.test:ip:203.0.113.10',
            $this->invokeRouteProviderMethod($provider, 'emailAndClientKey', $request)
        );

        $limit = $this->invokeRouteProviderMethod($provider, 'apiLimit', 17, 'contract-key');
        $response = $this->invokeRouteProviderMethod($provider, 'apiRateLimitResponse', ['Retry-After' => '9']);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame(9, $response->getData(true)['error']['details']['retry_after']);
        $this->assertIsBool($this->invokeRouteProviderMethod($provider, 'shouldLoadFundraiserRoutes'));
    }

    public function test_support_value_objects_sql_reader_and_post_media_rule_contracts(): void
    {
        $manifest = (new AddonPackageManifestParser)->parse(json_encode([
            'is_addon' => '1',
            'product_version' => ['minimum_required_version' => '2.0.0'],
            'addon_version' => [
                'minimum_required_version' => '1.0.0',
                'update_version' => '1.2.0',
            ],
            'addons' => [
                [
                    'unique_identifier' => 'contract-addon',
                    'title' => 'Contract Addon',
                    'features' => 'Tests',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->assertTrue($manifest->isAddon);
        $this->assertSame('2.0.0', $manifest->minimumProductVersion);
        $this->assertSame('1.0.0', $manifest->minimumAddonVersion);
        $this->assertSame('1.2.0', $manifest->updateVersion);
        $this->assertSame('contract-addon', $manifest->addons[0]['unique_identifier']);

        $sqlResult = new InstallSqlImportResult;
        $sqlResult->recordSchemaStatement();
        $sqlResult->recordInsertStatement();
        $sqlResult->recordInsertedRows(2);
        $sqlResult->recordDuplicateRow();
        $sqlResult->recordSkippedStatement();
        $sqlResult->recordRowError('users', 3, 'bad row');

        $importResult = new AddonPackageImportResult('Imported.', 1, 2, $sqlResult, false);

        $this->assertSame('Imported.', $importResult->message);
        $this->assertSame(1, $importResult->addonRowsUpserted);
        $this->assertSame(2, $importResult->filesDistributed);
        $this->assertFalse($importResult->skippedPhpSteps);
        $this->assertSame(1, $sqlResult->schemaStatements());
        $this->assertSame(1, $sqlResult->insertStatements());
        $this->assertSame(2, $sqlResult->insertedRows());
        $this->assertSame(1, $sqlResult->duplicateRows());
        $this->assertSame(1, $sqlResult->skippedStatements());
        $this->assertSame(1, $sqlResult->failedRows());
        $this->assertTrue($sqlResult->hasFailures());
        $this->assertSame('bad row', $sqlResult->errors()[0]['message']);

        $dumpPath = storage_path('framework/testing/install-contract.sql');
        File::ensureDirectoryExists(dirname($dumpPath));
        File::put($dumpPath, "-- ignored\n/*!40101 SET something */;\nINSERT INTO demo VALUES ('semi;colon');\nCREATE TABLE demo (id int);\n");

        $this->assertSame(
            ["INSERT INTO demo VALUES ('semi;colon')", 'CREATE TABLE demo (id int)'],
            iterator_to_array((new InstallSqlStatementReader)->statements($dumpPath))
        );

        $this->assertSame(500000, PostMediaFile::forCreate()->maxKilobytes());
        $this->assertSame(20480, PostMediaFile::forUpdate()->maxKilobytes());
        $this->assertContains('mp4', PostMediaFile::allowedExtensions());

        File::delete($dumpPath);
    }

    public function test_guest_layout_and_profile_follow_list_return_view_ready_data(): void
    {
        $viewer = User::factory()->create();
        $mutualFriend = User::factory()->create();
        $follower = User::factory()->create([
            'friends' => json_encode([$mutualFriend->id]),
        ]);
        $following = User::factory()->create();

        $viewer->forceFill(['friends' => json_encode([$mutualFriend->id])])->save();

        Follower::unguarded(function () use ($viewer, $follower, $following): void {
            Follower::query()->create([
                'user_id' => $follower->id,
                'follow_id' => $viewer->id,
                'created_at' => now(),
            ]);
            Follower::query()->create([
                'user_id' => $viewer->id,
                'follow_id' => $follower->id,
                'created_at' => now(),
            ]);
            Follower::query()->create([
                'user_id' => $viewer->id,
                'follow_id' => $following->id,
                'created_at' => now(),
            ]);
        });

        $followList = ProfileFollowList::forUser($viewer);
        $followerRow = $followList['followers']['items']->first();

        $this->assertInstanceOf(View::class, (new GuestLayout)->render());
        $this->assertSame(1, $followList['followers']['count']);
        $this->assertSame(2, $followList['following']['count']);
        $this->assertSame($follower->id, $followerRow['user']->id);
        $this->assertTrue($followerRow['is_following']);
        $this->assertSame(1, $followerRow['mutual_friends']);
    }

    /**
     * @return array<string, array{0: class-string, 1: list<string>}>
     */
    public static function requestedMethodsProvider(): array
    {
        return [
            'model AccountActiveRequest' => ['App\\Models\\AccountActiveRequest', ['casts', 'user']],
            'model Addon' => ['App\\Models\\Addon', ['casts']],
            'model AlbumImage' => ['App\\Models\\AlbumImage', ['casts']],
            'model Albums' => ['App\\Models\\Albums', ['casts']],
            'model Badge' => ['App\\Models\\Badge', ['casts', 'getUser', 'add_payment_success']],
            'model BlockUser' => ['App\\Models\\BlockUser', ['casts', 'user', 'blockedUser']],
            'model Blog' => ['App\\Models\\Blog', ['casts', 'getUser', 'category', 'savedByUsers']],
            'model BlogCategory' => ['App\\Models\\BlogCategory', ['scopeOrdered', 'scopeForSelect']],
            'model Chat' => ['App\\Models\\Chat', ['casts', 'scopeForMessageThread', 'scopeUnreadForReceiver', 'scopeBetweenParticipants', 'messageThread', 'sender', 'receiver', 'messageThreadId', 'receiverId', 'chatCenter']],
            'model Comments' => ['App\\Models\\Comments', ['casts', 'user', 'post', 'parent', 'children']],
            'model Currency' => ['App\\Models\\Currency', ['casts']],
            'model Event' => ['App\\Models\\Event', ['casts', 'getUser', 'inviteEvent', 'savedByUsers']],
            'model Follower' => ['App\\Models\\Follower', ['casts', 'user', 'followedUser', 'page', 'group']],
            'model Friendships' => ['App\\Models\\Friendships', ['casts', 'getFriend', 'getFriendAccepter', 'otherUserId']],
            'model Group' => ['App\\Models\\Group', ['getMember', 'getUser', 'followedByUsers', 'members', 'savedByUsers']],
            'model GroupMember' => ['App\\Models\\GroupMember', ['scopeAccepted', 'getGroup', 'getUser', 'group', 'user']],
            'model Invite' => ['App\\Models\\Invite', ['casts']],
            'model Job' => ['App\\Models\\Job', ['casts']],
            'model LiveStreaming' => ['App\\Models\\LiveStreaming', ['casts']],
            'model Marketplace' => ['App\\Models\\Marketplace', ['casts', 'getUser', 'getCategory', 'getBrand', 'getCurrency', 'savedByUsers', 'savedForLaterByUsers']],
            'model MediaFile' => ['App\\Models\\MediaFile', ['casts', 'scopeOfType', 'post']],
            'model MessageThread' => ['App\\Models\\MessageThread', ['casts', 'scopeBetweenParticipants', 'scopeForParticipant', 'sender', 'receiver', 'receiverId', 'chatCenter']],
            'model Notification' => ['App\\Models\\Notification', ['casts', 'getUserData', 'getEventData', 'getGroupData', 'getPageData', 'getFundraiserData']],
            'model Page' => ['App\\Models\\Page', ['casts', 'getCategory', 'getUser', 'followedByUsers', 'likedByUsers', 'posts']],
            'model PageLike' => ['App\\Models\\PageLike', ['casts', 'pageData', 'user', 'page']],
            'model PaymentGateway' => ['App\\Models\\PaymentGateway', ['casts', 'scopeForIdentifier', 'isEnabled', 'isInTestMode', 'decodedKeys']],
            'model PaymentHistoryEntry' => ['App\\Models\\PaymentHistoryEntry', ['casts']],
            'model Posts' => ['App\\Models\\Posts', ['casts', 'scopeActive', 'scopeNotPrivate', 'scopeNotReported', 'scopeForPublisher', 'scopePubliclyVisible', 'scopeForUser', 'getUser', 'media_files', 'comments', 'reports', 'shares', 'savedByUsers']],
            'model PostShare' => ['App\\Models\\PostShare', ['casts', 'user', 'post']],
            'model Report' => ['App\\Models\\Report', ['casts', 'userData', 'post']],
            'model SavedProduct' => ['App\\Models\\SavedProduct', ['casts', 'user', 'productData', 'product']],
            'model SaveForLater' => ['App\\Models\\SaveForLater', ['casts', 'user', 'getVideo', 'video', 'group', 'post', 'marketplace', 'event', 'blog']],
            'model Share' => ['App\\Models\\Share', ['casts']],
            'model Sponsor' => ['App\\Models\\Sponsor', ['scopeForUser', 'casts', 'add_payment_success']],
            'model Stories' => ['App\\Models\\Stories', ['casts']],
            'model User' => ['App\\Models\\User', ['casts', 'scopeAdmins', 'scopeNonAdmins', 'isOnline', 'blockedUsers', 'blockedByUsers', 'followingUsers', 'followedByUsers', 'followedPages', 'followedGroups', 'joinedGroups', 'likedPages', 'savedProducts', 'savedVideos', 'savedGroups', 'savedPosts', 'savedMarketplaceItems', 'savedEvents', 'savedBlogs', 'get_user_image']],
            'model Users' => ['App\\Models\\Users', ['casts']],
            'model Video' => ['App\\Models\\Video', ['casts', 'getUser', 'savedByUsers']],
            'policy MarketplacePolicy' => ['App\\Policies\\MarketplacePolicy', ['before', 'create', 'update', 'delete', 'messageSeller']],
            'policy PagePolicy' => ['App\\Policies\\PagePolicy', ['before', 'create', 'update']],
            'provider AppServiceProvider' => ['App\\Providers\\AppServiceProvider', ['register', 'boot']],
            'provider AuthServiceProvider' => ['App\\Providers\\AuthServiceProvider', ['boot']],
            'provider CommonServiceProvider' => ['App\\Providers\\CommonServiceProvider', ['register', 'boot']],
            'provider EventServiceProvider' => ['App\\Providers\\EventServiceProvider', ['boot']],
            'provider RouteServiceProvider' => ['App\\Providers\\RouteServiceProvider', ['boot', 'shouldLoadFundraiserRoutes', 'configureRateLimiting', 'apiLimit', 'apiRateLimitResponse', 'routeScopedKey', 'userOrClientKey', 'emailAndClientKey', 'clientKey', 'routeName', 'normalizedInput']],
            'provider ViewServiceProvider' => ['App\\Providers\\ViewServiceProvider', ['boot', 'settings']],
            'query FriendshipsQuery' => ['App\\Queries\\FriendshipsQuery', ['acceptedForUser', 'importantForUser', 'recentForUser', 'acceptedFriendIdsForUser']],
            'query JobApplicationExportQuery' => ['App\\Queries\\Jobs\\JobApplicationExportQuery', ['forOwner']],
            'query MarketplaceProductsQuery' => ['App\\Queries\\Marketplace\\MarketplaceProductsQuery', ['handle', 'paginate', 'query', 'applySearchAndLocation', 'applyPriceRange', 'applyExactFilter', 'applyDateRange', 'applySorting', 'containsLikePattern', 'whereLikeEscaped']],
            'query PageCardsQuery' => ['App\\Queries\\Pages\\PageCardsQuery', ['forViewer', 'profileForViewer', 'suggestedForViewer']],
            'query StoriesQuery' => ['App\\Queries\\StoriesQuery', ['visibleFor', 'findWithOwner', 'withOwnerColumns']],
            'rule PostMediaFile' => ['App\\Rules\\PostMediaFile', ['__construct', 'forCreate', 'forUpdate', 'validate', 'maxKilobytes', 'allowedExtensions', 'mimesRule', 'maxRule', 'extensionList']],
            'service Flutterwave' => ['App\\Services\\Payments\\Gateways\\Flutterwave', ['payment_status']],
            'service Paypal' => ['App\\Services\\Payments\\Gateways\\Paypal', ['payment_status', 'gatewayCredentials']],
            'service Paystack' => ['App\\Services\\Payments\\Gateways\\Paystack', ['payment_status', 'secretKey']],
            'service Paytm' => ['App\\Services\\Payments\\Gateways\\Paytm', ['payment_status']],
            'service Razorpay' => ['App\\Services\\Payments\\Gateways\\Razorpay', ['payment_status', 'payment_create']],
            'service StripePay' => ['App\\Services\\Payments\\Gateways\\StripePay', ['payment_status', 'payment_create']],
            'service PaymentGatewayResolver' => ['App\\Services\\Payments\\PaymentGatewayResolver', ['__construct', 'serviceClass', 'service', 'paymentStatus', 'createPayment', 'legacyServiceClass', 'callGatewayMethod']],
            'service ZoomMeetingClient' => ['App\\Services\\Zoom\\ZoomMeetingClient', ['__construct', 'get', 'post', 'patch', 'delete', 'toZoomTimeFormat', 'toUnixTimeStamp', 'request', 'generateToken', 'credentials', 'url']],
            'support AddonPackageImportResult' => ['App\\Support\\Addons\\AddonPackageImportResult', ['__construct']],
            'support AddonPackageManifest' => ['App\\Support\\Addons\\AddonPackageManifest', ['__construct']],
            'support AddonPackageManifestParser' => ['App\\Support\\Addons\\AddonPackageManifestParser', ['parse', 'arrayValue', 'stringValue', 'addonEntries']],
            'support ApiErrorResponse' => ['App\\Support\\Api\\ApiErrorResponse', ['make', 'authentication', 'authorization', 'notFound', 'domain']],
            'support IdempotentApiRequest' => ['App\\Support\\Api\\IdempotentApiRequest', ['handle', 'idempotencyKey', 'isValidIdempotencyKey', 'scope', 'actorScope', 'fingerprint', 'normalizeFiles', 'normalize', 'responseFromCache', 'storeResponse']],
            'support FileUploader' => ['App\\Support\\Files\\FileUploader', ['upload', 'uploadToPublicDisk', 'publicDiskTarget', 'putUploadedFile', 'putResizedImage', 'optimizedDirectoryExists', 'looksLikeFileTarget', 'generatedFileName', 'assertSafeRelativePath', 'assertSafeFileName', 'assertAllowedUploadedFile', 'safeExtension']],
            'support InstallSqlImportResult' => ['App\\Support\\Install\\InstallSqlImportResult', ['recordSchemaStatement', 'recordInsertStatement', 'recordInsertedRows', 'recordDuplicateRow', 'recordSkippedStatement', 'recordRowError', 'schemaStatements', 'insertStatements', 'insertedRows', 'duplicateRows', 'failedRows', 'skippedStatements', 'hasFailures', 'errors']],
            'support InstallSqlInsertParser' => ['App\\Support\\Install\\InstallSqlInsertParser', ['parse', 'parseColumns', 'parseRows', 'readParenthesizedRow', 'parseRowValues', 'parseScalarValue', 'parseStringValue']],
            'support InstallSqlParsedInsert' => ['App\\Support\\Install\\InstallSqlParsedInsert', ['__construct']],
            'support InstallSqlStatementReader' => ['App\\Support\\Install\\InstallSqlStatementReader', ['statements', 'stripVersionComments']],
            'support SensitiveLogContext' => ['App\\Support\\Logging\\SensitiveLogContext', ['sanitize', 'sanitizeMessage', 'sanitizeArray', 'sanitizeKeyedValue', 'normalizeKey', 'isSensitiveKey', 'isPersonalDataKey', 'isPaymentDataKey', 'isRawPayloadKey', 'isFileContentKey', 'payloadSummary', 'uploadedFileSummary', 'exceptionSummary']],
            'support Money' => ['App\\Support\\Money\\Money', ['toMinorUnits', 'normalizeDecimalAmount']],
            'support ServerSideUrl' => ['App\\Support\\Security\\ServerSideUrl', ['forHttpFetch', 'forConfiguredHttpFetch', 'configuredStreamContextOptions', 'configuredResponseByteLimit', 'streamContextOptions', 'resolvesOnlyToPublicIps', 'normalizeHost', 'isPublicIp', 'hostMatchesAllowlist', 'normalizeAllowedHost', 'normalizeAllowedSchemes', 'configuredStringList', 'configuredString', 'configuredInt']],
            'support DateTimeRules' => ['App\\Support\\Validation\\DateTimeRules', ['requiredBrowserDate', 'nullableBrowserDate', 'requiredBrowserTime', 'requiredDateRangeEnd', 'requiredBirthDate', 'nullableBirthDate', 'nullableBrowserDateTimeLocal', 'nullableTimezone', 'birthDateTimestamp', 'browserDateAtCurrentTime', 'dateTimeLocalToDatabase', 'timezoneOrDefault', 'browserDate', 'dateTimeFromFormat', 'appTimezone']],
            'support NestedFileValidationErrors' => ['App\\Support\\Validation\\NestedFileValidationErrors', ['collapse']],
            'view GuestLayout' => ['App\\View\\Components\\GuestLayout', ['render']],
            'view model BladeViewData' => ['App\\ViewModels\\BladeViewData', ['postCommentCount', 'rootComments', 'rootCommentCount', 'childCommentCount', 'childComments', 'reacts', 'taggedUserIds', 'userName', 'user', 'feelingActivity', 'postMediaFiles', 'postMediaFileCount', 'moreUnloadedImages', 'locationVisitCount', 'profileMediaFiles', 'profileFriends', 'friendshipRows', 'friendRequestRows', 'suggestedFriendRows', 'blockedUserRows', 'acceptedFriends', 'shareFriendRows', 'shareGroupRows', 'friendshipStatus', 'profileIdentifier', 'profilePronouns', 'canViewProfile', 'mutualFriendCount', 'isFollowing', 'rightSidebarHour', 'activeSponsors', 'onlineFriendRows', 'eventGuestStats', 'eventInviteRows', 'blogCategoryPostCount', 'paidContentCreator', 'fundraiserInvitedCount', 'fundraiserShareCount', 'fundraiserInviteRows', 'videoPost', 'isVideoSaved', 'isProductSaved', 'videoCommentCount', 'videoRootComments', 'videoReactCount', 'videoViewCount', 'firstExternalUrl', 'postShareCount', 'isBlockedPost', 'setting', 'systemName', 'systemFavicon', 'sharePostRoute', 'blogTags', 'accountActivationRequest', 'dashboardStats', 'addonAccess', 'backendFolder', 'pendingAccountActivationCount', 'pendingPaidContentPayoutCount', 'pendingFundraiserPayoutCount', 'pendingJobCount', 'groupAcceptedMemberCount', 'userJoinedGroup', 'groupMediaFiles', 'recentGroupMembers', 'page', 'group', 'albumsFor', 'albumImages', 'isGroupInviteSent', 'recentUsers', 'upcomingPublicGroupEvents', 'areFriends', 'chatThreadUser', 'chatLastMessage', 'chatUnreadCount', 'chatFiles', 'storyTextInfo', 'storyMediaFiles', 'sharedTargetId', 'sharedTargetType', 'withCommentUsers', 'remember', 'commentContentId', 'isBlockedUserId', 'isFriendId', 'hasFriendshipRequest']],
            'view model ProfileFollowList' => ['App\\ViewModels\\ProfileFollowList', ['forUser', 'build', 'profiles', 'followedProfileIds', 'friendIds']],
        ];
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function requestedModelClassesWithoutListedMethodsProvider(): array
    {
        return [
            'model Brand' => ['App\\Models\\Brand'],
            'model Category' => ['App\\Models\\Category'],
            'model FeelingAndActivity' => ['App\\Models\\FeelingAndActivity'],
            'model Fundraiser' => ['App\\Models\\Fundraiser'],
            'model FundraiserCategory' => ['App\\Models\\FundraiserCategory'],
            'model FundraiserDonation' => ['App\\Models\\FundraiserDonation'],
            'model FundraiserPayout' => ['App\\Models\\FundraiserPayout'],
            'model JobApply' => ['App\\Models\\JobApply'],
            'model JobCategory' => ['App\\Models\\JobCategory'],
            'model JobWishlist' => ['App\\Models\\JobWishlist'],
            'model Language' => ['App\\Models\\Language'],
            'model PageCategory' => ['App\\Models\\PageCategory'],
            'model PaidContentCreator' => ['App\\Models\\PaidContentCreator'],
            'model PaidContentPackages' => ['App\\Models\\PaidContentPackages'],
            'model PaidContentPayout' => ['App\\Models\\PaidContentPayout'],
            'model Setting' => ['App\\Models\\Setting'],
        ];
    }

    private function invokeRouteProviderMethod(RouteServiceProvider $provider, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod($provider, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($provider, ...$arguments);
    }
}
