<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These are additive lookup indexes for the legacy SQL-dump schema.
     * They intentionally avoid uniqueness, foreign keys, cascade rules, and
     * type/nullability changes because the existing dump has no constraints.
     *
     * @var array<string, array<string, list<string>>>
     */
    private array $indexes = [
        'account_active_requests' => [
            'account_active_requests_user_id_id_idx' => ['user_id', 'id'],
            'account_active_requests_status_id_idx' => ['status', 'id'],
        ],
        'albums' => [
            'albums_user_id_id_idx' => ['user_id', 'id'],
            'albums_page_id_id_idx' => ['page_id', 'id'],
            'albums_group_id_id_idx' => ['group_id', 'id'],
        ],
        'album_images' => [
            'album_images_album_id_id_idx' => ['album_id', 'id'],
            'album_images_user_id_id_idx' => ['user_id', 'id'],
        ],
        'blogs' => [
            'blogs_user_status_id_idx' => ['user_id', 'status', 'id'],
            'blogs_category_status_id_idx' => ['category_id', 'status', 'id'],
        ],
        'block_users' => [
            'block_users_user_block_idx' => ['user_id', 'block_user'],
        ],
        'chats' => [
            'chats_thread_id_idx' => ['message_thrade', 'id'],
            'chats_thread_receiver_read_idx' => ['message_thrade', 'reciver_id', 'read_status'],
            'chats_sender_receiver_id_idx' => ['sender_id', 'reciver_id', 'id'],
        ],
        'comments' => [
            'comments_type_content_parent_idx' => ['is_type', 'id_of_type', 'parent_id'],
            'comments_parent_id_idx' => ['parent_id'],
            'comments_user_id_idx' => ['user_id'],
        ],
        'events' => [
            'events_group_privacy_date_idx' => ['group_id', 'privacy', 'event_date'],
            'events_user_id_id_idx' => ['user_id', 'id'],
            'events_publisher_entity_idx' => ['publisher', 'publisher_id'],
        ],
        'followers' => [
            'followers_user_follow_idx' => ['user_id', 'follow_id'],
            'followers_follow_id_idx' => ['follow_id'],
        ],
        'friendships' => [
            'friendships_accepter_status_idx' => ['accepter', 'is_accepted', 'id'],
            'friendships_requester_status_idx' => ['requester', 'is_accepted', 'id'],
        ],
        'group_members' => [
            'group_members_group_status_idx' => ['group_id', 'is_accepted'],
            'group_members_user_status_idx' => ['user_id', 'is_accepted'],
        ],
        'groups' => [
            'groups_user_id_id_idx' => ['user_id', 'id'],
            'groups_privacy_id_idx' => ['privacy', 'id'],
        ],
        'invites' => [
            'invites_receiver_group_idx' => ['invite_reciver_id', 'group_id'],
            'invites_receiver_event_idx' => ['invite_reciver_id', 'event_id'],
            'invites_sender_receiver_idx' => ['invite_sender_id', 'invite_reciver_id'],
        ],
        'marketplaces' => [
            'marketplaces_user_status_id_idx' => ['user_id', 'status', 'id'],
            'marketplaces_category_status_idx' => ['category', 'status'],
            'marketplaces_currency_id_idx' => ['currency_id'],
        ],
        'media_files' => [
            'media_files_post_id_idx' => ['post_id'],
            'media_files_user_type_id_idx' => ['user_id', 'file_type', 'id'],
            'media_files_page_type_id_idx' => ['page_id', 'file_type', 'id'],
            'media_files_group_type_id_idx' => ['group_id', 'file_type', 'id'],
            'media_files_album_id_idx' => ['album_id'],
            'media_files_story_id_idx' => ['story_id'],
            'media_files_chat_id_idx' => ['chat_id'],
        ],
        'message_thrades' => [
            'message_thrades_sender_receiver_idx' => ['sender_id', 'reciver_id'],
            'message_thrades_receiver_sender_idx' => ['reciver_id', 'sender_id'],
        ],
        'notifications' => [
            'notifications_receiver_status_created_idx' => ['reciver_user_id', 'status', 'created_at'],
            'notifications_sender_receiver_idx' => ['sender_user_id', 'reciver_user_id'],
        ],
        'page_likes' => [
            'page_likes_user_page_idx' => ['user_id', 'page_id'],
            'page_likes_page_user_idx' => ['page_id', 'user_id'],
        ],
        'pages' => [
            'pages_user_id_id_idx' => ['user_id', 'id'],
            'pages_category_id_idx' => ['category_id'],
        ],
        'payment_gateways' => [
            'payment_gateways_identifier_idx' => ['identifier'],
        ],
        'payment_histories' => [
            'payment_histories_user_item_idx' => ['user_id', 'item_type', 'item_id'],
            'payment_histories_identifier_idx' => ['identifier'],
        ],
        'posts' => [
            'posts_user_status_privacy_report_idx' => ['user_id', 'status', 'privacy', 'report_status'],
            'posts_publisher_entity_status_idx' => ['publisher', 'publisher_id', 'status'],
            'posts_album_image_id_idx' => ['album_image_id'],
            'posts_activity_id_idx' => ['activity_id'],
            'posts_posted_on_idx' => ['posted_on'],
        ],
        'post_shares' => [
            'post_shares_post_user_idx' => ['post_id', 'user_id'],
        ],
        'reports' => [
            'reports_post_status_idx' => ['post_id', 'status'],
            'reports_user_status_idx' => ['user_id', 'status'],
        ],
        'saved_products' => [
            'saved_products_user_product_idx' => ['user_id', 'product_id'],
            'saved_products_product_id_idx' => ['product_id'],
        ],
        'saveforlaters' => [
            'saveforlaters_user_video_idx' => ['user_id', 'video_id'],
            'saveforlaters_user_post_idx' => ['user_id', 'post_id'],
            'saveforlaters_user_marketplace_idx' => ['user_id', 'marketplace_id'],
            'saveforlaters_user_event_idx' => ['user_id', 'event_id'],
            'saveforlaters_user_blog_idx' => ['user_id', 'blog_id'],
        ],
        'settings' => [
            'settings_type_idx' => ['type'],
        ],
        'shares' => [
            'shares_event_id_idx' => ['event_id'],
            'shares_page_id_idx' => ['page_id'],
            'shares_group_id_idx' => ['group_id'],
        ],
        'sponsors' => [
            'sponsors_user_status_dates_idx' => ['user_id', 'status', 'start_date', 'end_date'],
        ],
        'stories' => [
            'stories_user_status_created_idx' => ['user_id', 'status', 'created_at'],
            'stories_publisher_entity_idx' => ['publisher', 'publisher_id'],
        ],
        'videos' => [
            'videos_category_privacy_id_idx' => ['category', 'privacy', 'id'],
            'videos_user_id_id_idx' => ['user_id', 'id'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (! $this->hasColumns($table, $columns)
                    || Schema::hasIndex($table, $name)
                    || Schema::hasIndex($table, $columns)
                ) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($columns, $name): void {
                    $table->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes, true) as $tableName => $indexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_reverse($indexes, true) as $name => $columns) {
                if (! $this->hasColumns($tableName, $columns) || ! Schema::hasIndex($tableName, $name)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($name): void {
                    $table->dropIndex($name);
                });
            }
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
};
