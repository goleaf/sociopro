<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use SplFileInfo;
use Tests\TestCase;

class MigrationSafetyAuditTest extends TestCase
{
    public function test_all_project_migrations_define_reversible_down_methods(): void
    {
        $missingDownMethods = [];

        foreach ($this->migrationFiles() as $file) {
            $contents = file_get_contents($file->getPathname());

            if (! is_string($contents)) {
                continue;
            }

            if (preg_match('/public\s+function\s+down\s*\(\s*\)\s*:\s*void/', $contents) !== 1) {
                $missingDownMethods[] = $file->getFilename();
            }
        }

        $this->assertSame([], $missingDownMethods);
    }

    public function test_high_value_legacy_lookup_indexes_are_present_and_reversible(): void
    {
        $migration = require database_path('migrations/2026_07_02_130000_add_safe_legacy_relationship_indexes.php');

        $migration->up();
        $this->assertIndexesExist($this->expectedLegacyRelationshipIndexes());

        $migration->down();
        $this->assertIndexesDoNotExist($this->expectedLegacyRelationshipIndexes());

        $migration->up();
        $this->assertIndexesExist($this->expectedLegacyRelationshipIndexes());
    }

    public function test_query_pattern_coverage_indexes_are_present_and_reversible(): void
    {
        $migration = require database_path('migrations/2026_07_02_140000_add_query_pattern_coverage_indexes.php');

        $migration->up();
        $this->assertIndexesExist($this->expectedQueryPatternIndexes());

        $migration->down();
        $this->assertIndexesDoNotExist($this->expectedQueryPatternIndexes());

        $migration->up();
        $this->assertIndexesExist($this->expectedQueryPatternIndexes());
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function migrationFiles(): iterable
    {
        foreach (glob(database_path('migrations/*.php')) ?: [] as $path) {
            $file = new SplFileInfo($path);

            if ($file->isFile()) {
                yield $file;
            }
        }
    }

    /**
     * @param  array<string, array<string, list<string>>>  $expectedIndexes
     */
    private function assertIndexesExist(array $expectedIndexes): void
    {
        foreach ($expectedIndexes as $table => $indexes) {
            $actualIndexes = $this->indexesFor($table);

            foreach ($indexes as $name => $columns) {
                $this->assertSame($columns, $actualIndexes[$name] ?? null, "{$table}.{$name}");
            }
        }
    }

    /**
     * @param  array<string, array<string, list<string>>>  $expectedIndexes
     */
    private function assertIndexesDoNotExist(array $expectedIndexes): void
    {
        foreach ($expectedIndexes as $table => $indexes) {
            $actualIndexes = $this->indexesFor($table);

            foreach (array_keys($indexes) as $name) {
                $this->assertArrayNotHasKey($name, $actualIndexes, "{$table}.{$name}");
            }
        }
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedLegacyRelationshipIndexes(): array
    {
        return [
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
            'feeling_and_activities' => [
                'feeling_and_activities_type_idx' => ['type'],
            ],
            'users' => [
                'users_status_id_idx' => ['status', 'id'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedQueryPatternIndexes(): array
    {
        return [
            'comments' => [
                'comments_type_content_parent_comment_idx' => ['is_type', 'id_of_type', 'parent_id', 'comment_id'],
            ],
            'currencies' => [
                'currencies_code_idx' => ['code'],
            ],
            'friendships' => [
                'friendships_accepter_status_importance_id_idx' => ['accepter', 'is_accepted', 'importance', 'id'],
                'friendships_requester_status_importance_id_idx' => ['requester', 'is_accepted', 'importance', 'id'],
            ],
            'group_members' => [
                'group_members_group_status_id_idx' => ['group_id', 'is_accepted', 'id'],
            ],
            'groups' => [
                'groups_privacy_status_id_idx' => ['privacy', 'status', 'id'],
            ],
            'invites' => [
                'invites_sender_receiver_event_idx' => ['invite_sender_id', 'invite_reciver_id', 'event_id'],
                'invites_sender_receiver_group_idx' => ['invite_sender_id', 'invite_reciver_id', 'group_id'],
            ],
            'notifications' => [
                'notifications_receiver_created_id_idx' => ['reciver_user_id', 'created_at', 'id'],
            ],
            'stories' => [
                'stories_status_created_story_idx' => ['status', 'created_at', 'story_id'],
            ],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function indexesFor(string $table): array
    {
        return collect(Schema::getIndexes($table))
            ->mapWithKeys(fn (array $index): array => [$index['name'] => $index['columns']])
            ->all();
    }
}
