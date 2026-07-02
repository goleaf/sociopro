<?php

namespace Tests\Feature;

use App\Enums\PaytmTransactionStatus;
use App\Models\Account_active_request;
use App\Models\Addon;
use App\Models\Album_image;
use App\Models\Albums;
use App\Models\Badge;
use App\Models\BlockUser;
use App\Models\Blog;
use App\Models\Chat;
use App\Models\Comments;
use App\Models\Currency;
use App\Models\Event;
use App\Models\Follower;
use App\Models\Friendships;
use App\Models\Invite;
use App\Models\Live_streamings;
use App\Models\Marketplace;
use App\Models\Media_files;
use App\Models\Message_thrade;
use App\Models\Notification;
use App\Models\Page;
use App\Models\Page_like;
use App\Models\Payment_gateway;
use App\Models\PaymentHistoryEntry;
use App\Models\Post_share;
use App\Models\Posts;
use App\Models\Report;
use App\Models\SavedProduct;
use App\Models\Saveforlater;
use App\Models\Share;
use App\Models\Sponsor;
use App\Models\Stories;
use App\Models\User;
use App\Models\Users;
use App\Models\Video;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentCastAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_gateway_casts_flags_and_key_payloads_without_serializing_secrets(): void
    {
        $gateway = Payment_gateway::create([
            'identifier' => 'custom_gateway',
            'currency' => 'USD',
            'title' => 'Custom Gateway',
            'description' => 'Custom gateway',
        ]);
        $gateway->forceFill([
            'keys' => [
                'public_key' => 'pk_test_public',
                'secret_key' => 'sk_test_secret',
            ],
            'model_name' => 'CustomGateway',
            'test_mode' => '0',
            'status' => '1',
            'is_addon' => '1',
        ])->save();

        $gateway->refresh();

        $this->assertSame([
            'public_key' => 'pk_test_public',
            'secret_key' => 'sk_test_secret',
        ], $gateway->keys);
        $this->assertSame($gateway->keys, $gateway->decodedKeys());
        $this->assertTrue($gateway->status);
        $this->assertFalse($gateway->test_mode);
        $this->assertTrue($gateway->is_addon);
        $this->assertArrayNotHasKey('keys', $gateway->toArray());
    }

    public function test_payment_history_casts_identifiers_money_and_transaction_payloads(): void
    {
        $entry = new PaymentHistoryEntry;
        $entry->forceFill([
            'item_type' => 'badge',
            'item_id' => '42',
            'user_id' => '7',
            'amount' => '19.5',
            'currency' => 'USD',
            'identifier' => 'paytm',
            'transaction_keys' => [
                'gateway_reference' => 'TXN-123',
                'token' => 'provider-token',
            ],
        ])->save();

        $entry->refresh();

        $this->assertSame(42, $entry->item_id);
        $this->assertSame(7, $entry->user_id);
        $this->assertSame('19.50', $entry->amount);
        $this->assertSame([
            'gateway_reference' => 'TXN-123',
            'token' => 'provider-token',
        ], $entry->transaction_keys);
        $this->assertArrayNotHasKey('transaction_keys', $entry->toArray());

        $entry->status = PaytmTransactionStatus::Successful->value;

        $this->assertSame(PaytmTransactionStatus::Successful, $entry->status);
    }

    public function test_sponsor_casts_payment_amount_status_and_schedule_dates(): void
    {
        $sponsor = new Sponsor;
        $sponsor->forceFill([
            'user_id' => '9',
            'name' => 'Summer Campaign',
            'paid_amount' => '49.9',
            'status' => '1',
            'start_date' => '2026-07-01 09:00:00',
            'end_date' => '2026-07-31 17:30:00',
        ])->save();

        $sponsor->refresh();

        $this->assertSame(9, $sponsor->user_id);
        $this->assertSame('49.90', $sponsor->paid_amount);
        $this->assertSame(1, $sponsor->status);
        $this->assertSame('2026-07-01 09:00:00', $sponsor->start_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-31 17:30:00', $sponsor->end_date->format('Y-m-d H:i:s'));
    }

    public function test_marketplace_casts_price_as_two_decimal_money_string(): void
    {
        $marketplace = new Marketplace;
        $marketplace->forceFill([
            'user_id' => '7',
            'currency_id' => '1',
            'title' => 'Money cast fixture',
            'price' => '19.5',
        ])->save();

        $marketplace->refresh();

        $this->assertSame(7, $marketplace->user_id);
        $this->assertSame(1, $marketplace->currency_id);
        $this->assertSame('19.50', $marketplace->price);
    }

    public function test_legacy_models_cast_numeric_identifiers_and_flags_before_persistence(): void
    {
        foreach ($this->legacyIntegerCastContracts() as $class => $attributes) {
            $model = new $class;
            $model->forceFill(array_fill_keys($attributes, '7'));

            foreach ($attributes as $attribute) {
                $this->assertSame(7, $model->getAttribute($attribute), "{$class}::{$attribute} is not cast to integer.");
                $this->assertSame(7, $model->toArray()[$attribute], "{$class}::{$attribute} is not serialized as integer.");
            }
        }

        $currency = new Currency;
        $currency->forceFill([
            'paypal_supported' => '1',
            'stripe_supported' => '0',
        ]);

        $this->assertTrue($currency->paypal_supported);
        $this->assertFalse($currency->stripe_supported);
        $this->assertTrue($currency->toArray()['paypal_supported']);
        $this->assertFalse($currency->toArray()['stripe_supported']);
    }

    public function test_legacy_model_casts_persist_through_database_reads(): void
    {
        $post = new Posts;
        $post->forceFill([
            'user_id' => '7',
            'publisher_id' => '8',
            'activity_id' => '9',
            'report_status' => '1',
        ])->save();

        $comment = new Comments;
        $comment->forceFill([
            'parent_id' => '2',
            'user_id' => '7',
            'id_of_type' => '9',
        ])->save();

        $notification = new Notification;
        $notification->forceFill([
            'sender_user_id' => '3',
            'reciver_user_id' => '4',
            'status' => '1',
            'view' => '0',
        ])->save();

        $legacyUser = new Users;
        $legacyUser->forceFill([
            'email' => 'legacy-cast@example.com',
            'date_of_birth' => '709948800',
        ])->save();

        $this->assertSame(7, $post->refresh()->user_id);
        $this->assertSame(8, $post->publisher_id);
        $this->assertSame(9, $post->activity_id);
        $this->assertSame(1, $post->report_status);

        $this->assertSame(2, $comment->refresh()->parent_id);
        $this->assertSame(7, $comment->user_id);
        $this->assertSame(9, $comment->id_of_type);

        $currency = Currency::query()->where('paypal_supported', 0)->where('stripe_supported', 1)->firstOrFail();

        $this->assertFalse($currency->paypal_supported);
        $this->assertTrue($currency->stripe_supported);

        $this->assertSame(3, $notification->refresh()->sender_user_id);
        $this->assertSame(4, $notification->reciver_user_id);
        $this->assertSame(1, $notification->status);
        $this->assertSame(0, $notification->view);

        $this->assertSame(709948800, $legacyUser->refresh()->date_of_birth);
    }

    public function test_core_lifecycle_date_attributes_cast_without_changing_legacy_serialization(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => '2026-07-01 09:15:00',
            'lastActive' => '2026-07-01 10:30:00',
        ]);

        $legacyUser = new Users;
        $legacyUser->forceFill([
            'email' => 'legacy-date-cast@example.com',
            'email_verified_at' => '2026-07-01 09:15:00',
            'lastActive' => '2026-07-01 10:30:00',
        ])->save();

        $post = new Posts;
        $post->forceFill([
            'user_id' => '7',
            'posted_on' => '2026-07-01 11:45:00',
        ])->save();

        $this->assertInstanceOf(CarbonInterface::class, $user->refresh()->email_verified_at);
        $this->assertInstanceOf(CarbonInterface::class, $user->lastActive);
        $this->assertInstanceOf(CarbonInterface::class, $legacyUser->refresh()->email_verified_at);
        $this->assertInstanceOf(CarbonInterface::class, $legacyUser->lastActive);
        $this->assertInstanceOf(CarbonInterface::class, $post->refresh()->posted_on);

        $this->assertSame('2026-07-01 09:15:00', $legacyUser->email_verified_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-01 10:30:00', $legacyUser->lastActive->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-01 11:45:00', $post->posted_on->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-01 11:45:00', $post->toArray()['posted_on']);
    }

    /**
     * @return array<class-string, list<string>>
     */
    private function legacyIntegerCastContracts(): array
    {
        return [
            Account_active_request::class => ['user_id'],
            Addon::class => ['parent_id', 'status'],
            Album_image::class => ['album_id', 'user_id', 'page_id', 'group_id'],
            Albums::class => ['user_id', 'page_id', 'group_id'],
            Badge::class => ['user_id', 'status'],
            BlockUser::class => ['user_id', 'block_user'],
            Blog::class => ['user_id', 'category_id'],
            Chat::class => ['message_thrade', 'reciver_id', 'sender_id', 'thumbsup', 'reply_id', 'read_status'],
            Comments::class => ['parent_id', 'user_id', 'id_of_type'],
            Event::class => ['user_id', 'group_id', 'publisher_id'],
            Follower::class => ['user_id', 'follow_id', 'page_id', 'group_id'],
            Friendships::class => ['requester', 'accepter', 'importance', 'is_accepted'],
            Invite::class => ['invite_sender_id', 'invite_reciver_id', 'is_accepted', 'event_id', 'page_id', 'group_id', 'post_id'],
            Live_streamings::class => ['publisher_id', 'user_id'],
            Marketplace::class => ['user_id', 'currency_id'],
            Media_files::class => ['user_id', 'post_id', 'story_id', 'album_id', 'product_id', 'page_id', 'group_id', 'chat_id', 'album_image_id'],
            Message_thrade::class => ['reciver_id', 'sender_id'],
            Notification::class => ['sender_user_id', 'reciver_user_id', 'event_id', 'page_id', 'group_id', 'status', 'view'],
            Page::class => ['user_id', 'category_id'],
            Page_like::class => ['user_id', 'page_id'],
            Post_share::class => ['user_id', 'post_id'],
            Posts::class => ['user_id', 'publisher_id', 'activity_id', 'report_status'],
            Report::class => ['user_id', 'post_id', 'status'],
            SavedProduct::class => ['user_id', 'product_id'],
            Saveforlater::class => ['user_id', 'video_id', 'group_id', 'post_id', 'marketplace_id', 'event_id', 'blog_id'],
            Share::class => ['event_id', 'page_id', 'group_id'],
            Stories::class => ['user_id', 'publisher_id'],
            Users::class => ['date_of_birth'],
            Video::class => ['user_id'],
        ];
    }
}
