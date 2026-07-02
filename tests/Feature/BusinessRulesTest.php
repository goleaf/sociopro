<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\MediaFileType;
use App\Enums\PostType;
use App\Enums\Visibility;
use App\Models\Friendships;
use App\Models\GroupMember;
use App\Models\MediaFile;
use App\Models\PaymentGateway;
use App\Models\Posts;
use App\Models\User;
use App\Queries\FriendshipsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_friendship_query_returns_the_other_user_ids_for_accepted_friendships(): void
    {
        $viewer = User::factory()->create();
        $requestedFriend = User::factory()->create();
        $acceptedFriend = User::factory()->create();
        $pendingFriend = User::factory()->create();

        Friendships::create([
            'requester' => $viewer->id,
            'accepter' => $requestedFriend->id,
            'importance' => 10,
            'is_accepted' => 1,
        ]);
        Friendships::create([
            'requester' => $acceptedFriend->id,
            'accepter' => $viewer->id,
            'importance' => 20,
            'is_accepted' => 1,
        ]);
        Friendships::create([
            'requester' => $pendingFriend->id,
            'accepter' => $viewer->id,
            'importance' => 30,
            'is_accepted' => 0,
        ]);

        $this->assertSame(
            [$requestedFriend->id, $acceptedFriend->id],
            FriendshipsQuery::acceptedFriendIdsForUser($viewer)
        );
    }

    public function test_group_member_accepted_scope_preserves_legacy_accepted_flag(): void
    {
        $accepted = new GroupMember;
        $accepted->group_id = 10;
        $accepted->user_id = 1;
        $accepted->is_accepted = '1';
        $accepted->save();

        $pending = new GroupMember;
        $pending->group_id = 10;
        $pending->user_id = 2;
        $pending->is_accepted = '0';
        $pending->save();

        $this->assertSame([$accepted->id], GroupMember::accepted()->pluck('id')->all());
    }

    public function test_media_file_type_scope_filters_legacy_file_type_values(): void
    {
        $image = MediaFile::create([
            'file_name' => 'photo.jpg',
            'file_type' => MediaFileType::Image->value,
        ]);
        $video = MediaFile::create([
            'file_name' => 'clip.mp4',
            'file_type' => MediaFileType::Video->value,
        ]);

        $this->assertSame([$image->id], MediaFile::ofType(MediaFileType::Image)->pluck('id')->all());
        $this->assertSame([$video->id], MediaFile::ofType(MediaFileType::Video)->pluck('id')->all());
    }

    public function test_post_scopes_preserve_active_visible_not_reported_rules(): void
    {
        $visiblePostId = $this->insertPost([
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
        ]);
        $this->insertPost([
            'privacy' => Visibility::Private->value,
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
        ]);
        $this->insertPost([
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Inactive->value,
            'report_status' => 0,
        ]);
        $this->insertPost([
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Active->value,
            'report_status' => 1,
        ]);

        $postIds = Posts::active()
            ->notPrivate()
            ->notReported()
            ->pluck('post_id')
            ->all();

        $this->assertSame([$visiblePostId], $postIds);
    }

    public function test_post_for_publisher_scope_filters_publisher_type_and_identifier(): void
    {
        $matchingPostId = $this->insertPost([
            'publisher' => 'video_and_shorts',
            'publisher_id' => 42,
        ]);
        $this->insertPost([
            'publisher' => 'video_and_shorts',
            'publisher_id' => 43,
        ]);
        $this->insertPost([
            'publisher' => 'event',
            'publisher_id' => 42,
        ]);

        $postIds = Posts::query()
            ->forPublisher('video_and_shorts', 42)
            ->pluck('post_id')
            ->all();

        $this->assertSame([$matchingPostId], $postIds);
    }

    public function test_post_publicly_visible_scope_filters_public_privacy(): void
    {
        $publicPostId = $this->insertPost([
            'privacy' => Visibility::Public->value,
        ]);
        $this->insertPost([
            'privacy' => Visibility::Private->value,
        ]);

        $postIds = Posts::query()
            ->publiclyVisible()
            ->pluck('post_id')
            ->all();

        $this->assertSame([$publicPostId], $postIds);
    }

    public function test_post_for_user_scope_filters_owner(): void
    {
        $matchingPostId = $this->insertPost([
            'user_id' => 20,
        ]);
        $this->insertPost([
            'user_id' => 21,
        ]);

        $postIds = Posts::query()
            ->forUser(20)
            ->pluck('post_id')
            ->all();

        $this->assertSame([$matchingPostId], $postIds);
    }

    public function test_payment_gateway_model_centralizes_enabled_mode_and_key_decoding(): void
    {
        $gateway = new PaymentGateway;
        $gateway->forceFill([
            'keys' => json_encode(['public_key' => 'public-test-key']),
            'status' => '1',
            'test_mode' => '1',
        ]);

        $this->assertTrue($gateway->isEnabled());
        $this->assertTrue($gateway->isInTestMode());
        $this->assertSame(['public_key' => 'public-test-key'], $gateway->decodedKeys());

        $gateway->status = '0';
        $gateway->test_mode = '0';
        $gateway->keys = null;

        $this->assertFalse($gateway->isEnabled());
        $this->assertFalse($gateway->isInTestMode());
        $this->assertSame([], $gateway->decodedKeys());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertPost(array $overrides): int
    {
        return (int) Posts::query()->insertGetId($overrides + [
            'user_id' => 1,
            'publisher' => 'post',
            'publisher_id' => 1,
            'post_type' => PostType::General->value,
            'tagged_user_ids' => json_encode([]),
            'user_reacts' => json_encode([]),
            'shared_user' => json_encode([]),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }
}
