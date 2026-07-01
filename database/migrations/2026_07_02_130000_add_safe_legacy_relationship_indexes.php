<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive, non-unique indexes for hot legacy lookups that were missed by
     * earlier index migrations. Text and very-wide varchar composites are
     * intentionally excluded because they are risky on MySQL/InnoDB.
     *
     * @var array<string, array<string, list<string>>>
     */
    private array $indexes = [
        'addons' => [
            'addons_unique_identifier_idx' => ['unique_identifier'],
        ],
        'chats' => [
            'chats_receiver_read_id_idx' => ['reciver_id', 'read_status', 'id'],
        ],
        'comments' => [
            'comments_content_id_idx' => ['id_of_type'],
        ],
        'events' => [
            'events_privacy_group_id_idx' => ['privacy', 'group_id', 'id'],
        ],
        'feeling_and_activities' => [
            'feeling_and_activities_type_idx' => ['type'],
        ],
        'followers' => [
            'followers_page_id_idx' => ['page_id'],
            'followers_group_id_idx' => ['group_id'],
        ],
        'invites' => [
            'invites_receiver_page_idx' => ['invite_reciver_id', 'page_id'],
            'invites_receiver_post_idx' => ['invite_reciver_id', 'post_id'],
        ],
        'languages' => [
            'languages_name_idx' => ['name'],
        ],
        'live_streamings' => [
            'live_streamings_publisher_user_idx' => ['publisher_id', 'user_id'],
        ],
        'media_files' => [
            'media_files_product_id_idx' => ['product_id', 'id'],
            'media_files_album_image_id_idx' => ['album_image_id'],
        ],
        'notifications' => [
            'notifications_event_status_idx' => ['event_id', 'status'],
            'notifications_page_status_idx' => ['page_id', 'status'],
            'notifications_group_status_idx' => ['group_id', 'status'],
        ],
        'payment_histories' => [
            'payment_histories_item_id_idx' => ['item_id'],
        ],
        'saveforlaters' => [
            'saveforlaters_user_group_idx' => ['user_id', 'group_id'],
        ],
        'users' => [
            'users_status_id_idx' => ['status', 'id'],
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
        foreach (array_reverse($this->indexes, true) as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_reverse($indexes, true) as $name => $columns) {
                if (! $this->hasColumns($table, $columns) || ! Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($name): void {
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
