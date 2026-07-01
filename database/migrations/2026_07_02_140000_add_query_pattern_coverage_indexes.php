<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive indexes for query patterns found in controllers, query objects,
     * view models, and providers. Each entry documents the read path it serves.
     *
     * @var array<string, array<string, array{columns: list<string>, reason: string}>>
     */
    private array $indexes = [
        'comments' => [
            'comments_type_content_parent_comment_idx' => [
                'columns' => ['is_type', 'id_of_type', 'parent_id', 'comment_id'],
                'reason' => 'Supports root/child comment counts and latest comment previews filtered by content type, content id, and parent id.',
            ],
        ],
        'currencies' => [
            'currencies_code_idx' => [
                'columns' => ['code'],
                'reason' => 'Supports admin/system currency selectors that order currencies by code.',
            ],
        ],
        'friendships' => [
            'friendships_accepter_status_importance_id_idx' => [
                'columns' => ['accepter', 'is_accepted', 'importance', 'id'],
                'reason' => 'Supports accepted-friend lookups for a recipient ordered by importance then id.',
            ],
            'friendships_requester_status_importance_id_idx' => [
                'columns' => ['requester', 'is_accepted', 'importance', 'id'],
                'reason' => 'Supports accepted-friend lookups for a requester ordered by importance then id.',
            ],
        ],
        'group_members' => [
            'group_members_group_status_id_idx' => [
                'columns' => ['group_id', 'is_accepted', 'id'],
                'reason' => 'Supports accepted group-member counts and recent member lists ordered by id.',
            ],
        ],
        'groups' => [
            'groups_privacy_status_id_idx' => [
                'columns' => ['privacy', 'status', 'id'],
                'reason' => 'Supports public active group discovery lists ordered by newest group id.',
            ],
        ],
        'invites' => [
            'invites_sender_receiver_event_idx' => [
                'columns' => ['invite_sender_id', 'invite_reciver_id', 'event_id'],
                'reason' => 'Supports event invitation accept/decline updates by sender, receiver, and event.',
            ],
            'invites_sender_receiver_group_idx' => [
                'columns' => ['invite_sender_id', 'invite_reciver_id', 'group_id'],
                'reason' => 'Supports group invitation accept/decline updates by sender, receiver, and group.',
            ],
        ],
        'notifications' => [
            'notifications_receiver_created_id_idx' => [
                'columns' => ['reciver_user_id', 'created_at', 'id'],
                'reason' => 'Supports older-notification pagination filtered by receiver and created date, ordered by newest id.',
            ],
        ],
        'stories' => [
            'stories_status_created_story_idx' => [
                'columns' => ['status', 'created_at', 'story_id'],
                'reason' => 'Supports active story feed windows filtered by status and age, ordered by newest story id.',
            ],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $definition) {
                $columns = $definition['columns'];

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

            foreach (array_reverse($indexes, true) as $name => $definition) {
                if (! $this->hasColumns($table, $definition['columns']) || ! Schema::hasIndex($table, $name)) {
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
