<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
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

    public function test_safe_legacy_foreign_key_constraints_are_present_and_reversible(): void
    {
        $migration = require database_path('migrations/2026_07_02_150000_add_safe_legacy_foreign_key_constraints.php');

        $migration->up();
        $this->assertForeignKeysExist($this->expectedSafeForeignKeys());
        $this->assertIndexesExist($this->expectedForeignKeyHelperIndexes());

        $migration->down();
        $this->assertForeignKeysDoNotExist($this->expectedSafeForeignKeys());
        $this->assertIndexesDoNotExist($this->expectedForeignKeyHelperIndexes());

        $migration->up();
        $this->assertForeignKeysExist($this->expectedSafeForeignKeys());
        $this->assertIndexesExist($this->expectedForeignKeyHelperIndexes());
    }

    public function test_safe_legacy_unique_constraints_are_present_and_reversible(): void
    {
        $migration = require database_path('migrations/2026_07_02_160000_add_safe_legacy_unique_constraints.php');

        $migration->up();
        $this->assertUniqueIndexesExist($this->expectedSafeUniqueIndexes());

        $migration->down();
        $this->assertIndexesDoNotExist($this->expectedSafeUniqueIndexes());

        $migration->up();
        $this->assertUniqueIndexesExist($this->expectedSafeUniqueIndexes());
    }

    public function test_safe_legacy_unique_constraints_skip_dirty_duplicate_data(): void
    {
        $migration = require database_path('migrations/2026_07_02_160000_add_safe_legacy_unique_constraints.php');

        $migration->down();

        DB::table('brands')->insert([
            ['name' => 'Dirty Duplicate', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dirty Duplicate', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration->up();
        $this->assertIndexesDoNotExist([
            'brands' => [
                'brands_name_unique' => ['name'],
            ],
        ]);

        DB::table('brands')->where('name', 'Dirty Duplicate')->delete();

        $migration->up();
        $this->assertUniqueIndexesExist([
            'brands' => [
                'brands_name_unique' => ['name'],
            ],
        ]);
    }

    public function test_safe_legacy_nullable_column_constraints_are_present_and_reversible(): void
    {
        $migration = require database_path('migrations/2026_07_02_170000_add_safe_legacy_nullable_column_constraints.php');

        $migration->down();
        $this->assertColumnsAreNullable($this->expectedSafeRequiredColumns());

        $migration->up();
        $this->assertColumnsAreRequired($this->expectedSafeRequiredColumns());

        $migration->down();
        $this->assertColumnsAreNullable($this->expectedSafeRequiredColumns());

        $migration->up();
        $this->assertColumnsAreRequired($this->expectedSafeRequiredColumns());
    }

    public function test_safe_legacy_nullable_column_constraints_skip_dirty_null_data(): void
    {
        $migration = require database_path('migrations/2026_07_02_170000_add_safe_legacy_nullable_column_constraints.php');

        $migration->down();

        DB::table('currencies')->where('id', 1)->update(['name' => null]);

        $migration->up();
        $this->assertColumnNullable('currencies', 'name');

        DB::table('currencies')->where('id', 1)->update(['name' => 'Leke']);

        $migration->up();
        $this->assertColumnRequired('currencies', 'name');
    }

    public function test_safe_legacy_money_precision_constraints_are_present_and_reversible(): void
    {
        $migration = require database_path('migrations/2026_07_02_180000_add_safe_legacy_money_precision_constraints.php');

        $migration->down();
        $this->assertColumnsHaveTypeNames($this->expectedLegacyMoneyColumnTypes());

        $migration->up();
        $this->assertColumnsHaveTypeNames($this->expectedSafeMoneyColumnTypes());

        $migration->down();
        $this->assertColumnsHaveTypeNames($this->expectedLegacyMoneyColumnTypes());

        $migration->up();
        $this->assertColumnsHaveTypeNames($this->expectedSafeMoneyColumnTypes());
    }

    public function test_safe_legacy_money_precision_constraints_skip_dirty_money_values(): void
    {
        $migration = require database_path('migrations/2026_07_02_180000_add_safe_legacy_money_precision_constraints.php');

        $migration->down();

        DB::table('marketplaces')->insert([
            'title' => 'Dirty price fixture',
            'price' => 'free',
        ]);

        $migration->up();
        $this->assertColumnsHaveTypeNames([
            'marketplaces' => [
                'price' => ['text', 'varchar'],
            ],
        ]);

        DB::table('marketplaces')->where('title', 'Dirty price fixture')->delete();

        $migration->up();
        $this->assertColumnsHaveTypeNames([
            'marketplaces' => [
                'price' => ['numeric'],
            ],
        ]);
    }

    public function test_safe_legacy_datetime_column_constraints_are_present_and_reversible(): void
    {
        $migration = require database_path('migrations/2026_07_02_190000_add_safe_legacy_datetime_column_constraints.php');

        $migration->down();
        $this->assertColumnsHaveTypeNames($this->expectedLegacyDatetimeColumnTypes());
        $this->assertIndexesDoNotExist($this->expectedSafeDatetimeIndexes());

        $migration->up();
        $this->assertColumnsHaveTypeNames($this->expectedSafeDatetimeColumnTypes());
        $this->assertIndexesExist($this->expectedSafeDatetimeIndexes());

        $migration->down();
        $this->assertColumnsHaveTypeNames($this->expectedLegacyDatetimeColumnTypes());
        $this->assertIndexesDoNotExist($this->expectedSafeDatetimeIndexes());

        $migration->up();
        $this->assertColumnsHaveTypeNames($this->expectedSafeDatetimeColumnTypes());
        $this->assertIndexesExist($this->expectedSafeDatetimeIndexes());
    }

    public function test_safe_legacy_datetime_column_constraints_skip_dirty_datetime_values(): void
    {
        $migration = require database_path('migrations/2026_07_02_190000_add_safe_legacy_datetime_column_constraints.php');

        $migration->down();

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => 999,
            'name' => 'dirty-expiry',
            'token' => hash('sha256', 'dirty-expiry'),
            'abilities' => '["*"]',
            'expires_at' => 'not-a-date',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();
        $this->assertColumnsHaveTypeNames([
            'personal_access_tokens' => [
                'expires_at' => ['text', 'varchar'],
            ],
        ]);

        DB::table('personal_access_tokens')->where('name', 'dirty-expiry')->delete();

        $migration->up();
        $this->assertColumnsHaveTypeNames([
            'personal_access_tokens' => [
                'expires_at' => ['datetime', 'timestamp'],
            ],
        ]);
    }

    public function test_safe_legacy_json_column_constraints_are_present_and_reversible(): void
    {
        $migration = require database_path('migrations/2026_07_02_200000_add_safe_legacy_json_column_constraints.php');

        $migration->down();
        $this->assertColumnsHaveTypeNames($this->expectedLegacyJsonColumnTypes());

        $migration->up();
        $this->assertColumnsHaveTypeNames($this->expectedSafeJsonColumnTypes());

        $migration->down();
        $this->assertColumnsHaveTypeNames($this->expectedLegacyJsonColumnTypes());

        $migration->up();
        $this->assertColumnsHaveTypeNames($this->expectedSafeJsonColumnTypes());
    }

    public function test_safe_legacy_json_column_constraints_skip_dirty_json_values(): void
    {
        $migration = require database_path('migrations/2026_07_02_200000_add_safe_legacy_json_column_constraints.php');

        $migration->down();

        DB::table('payment_gateways')
            ->where('identifier', 'paypal')
            ->update(['keys' => '{not-json']);

        $this->assertTrue($this->migrationHasUnsafeJsonValues($migration, 'payment_gateways', 'keys'));

        $migration->up();
        $this->assertColumnsHaveTypeNames([
            'payment_gateways' => [
                'keys' => ['text', 'varchar'],
            ],
        ]);

        DB::table('payment_gateways')
            ->where('identifier', 'paypal')
            ->update(['keys' => json_encode(['client_id' => '', 'secret_key' => ''])]);

        $this->assertFalse($this->migrationHasUnsafeJsonValues($migration, 'payment_gateways', 'keys'));

        $migration->up();
        $this->assertColumnsHaveTypeNames([
            'payment_gateways' => [
                'keys' => $this->expectedJsonTypeNames(),
            ],
        ]);
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
     * @param  array<string, list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>, on_delete: string}>>  $expectedForeignKeys
     */
    private function assertForeignKeysExist(array $expectedForeignKeys): void
    {
        foreach ($expectedForeignKeys as $table => $foreignKeys) {
            $actualForeignKeys = $this->foreignKeysFor($table);

            foreach ($foreignKeys as $foreignKey) {
                $this->assertContains($foreignKey, $actualForeignKeys, $table.'.'.implode('_', $foreignKey['columns']));
            }
        }
    }

    /**
     * @param  array<string, list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>, on_delete: string}>>  $expectedForeignKeys
     */
    private function assertForeignKeysDoNotExist(array $expectedForeignKeys): void
    {
        foreach ($expectedForeignKeys as $table => $foreignKeys) {
            $actualForeignKeys = $this->foreignKeysFor($table);

            foreach ($foreignKeys as $foreignKey) {
                $this->assertNotContains($foreignKey, $actualForeignKeys, $table.'.'.implode('_', $foreignKey['columns']));
            }
        }
    }

    /**
     * @param  array<string, array<string, list<string>>>  $expectedIndexes
     */
    private function assertUniqueIndexesExist(array $expectedIndexes): void
    {
        foreach ($expectedIndexes as $table => $indexes) {
            $actualIndexes = $this->indexesFor($table);
            $actualUniqueIndexes = $this->uniqueIndexesFor($table);

            foreach ($indexes as $name => $columns) {
                $this->assertSame($columns, $actualIndexes[$name] ?? null, "{$table}.{$name}");
                $this->assertContains($name, $actualUniqueIndexes, "{$table}.{$name} is not unique");
            }
        }
    }

    /**
     * @param  array<string, array<string, array{default?: string|null}>>  $expectedColumns
     */
    private function assertColumnsAreRequired(array $expectedColumns): void
    {
        foreach ($expectedColumns as $table => $columns) {
            foreach ($columns as $column => $expectations) {
                $this->assertColumnRequired($table, $column);

                if (array_key_exists('default', $expectations)) {
                    $this->assertSame(
                        $expectations['default'],
                        $this->normalizedColumnDefault($table, $column),
                        "{$table}.{$column} default"
                    );
                }
            }
        }
    }

    /**
     * @param  array<string, array<string, array{default?: string|null}>>  $expectedColumns
     */
    private function assertColumnsAreNullable(array $expectedColumns): void
    {
        foreach ($expectedColumns as $table => $columns) {
            foreach (array_keys($columns) as $column) {
                $this->assertColumnNullable($table, $column);
            }
        }
    }

    /**
     * @param  array<string, array<string, list<string>>>  $expectedColumns
     */
    private function assertColumnsHaveTypeNames(array $expectedColumns): void
    {
        foreach ($expectedColumns as $table => $columns) {
            foreach ($columns as $column => $allowedTypeNames) {
                $actualTypeName = $this->columnTypeName($table, $column);

                $this->assertContains(
                    $actualTypeName,
                    $allowedTypeNames,
                    "{$table}.{$column} type [{$actualTypeName}] was not expected."
                );
            }
        }
    }

    private function assertColumnRequired(string $table, string $column): void
    {
        $this->assertFalse(
            (bool) ($this->columnFor($table, $column)['nullable'] ?? true),
            "{$table}.{$column} should be required"
        );
    }

    private function assertColumnNullable(string $table, string $column): void
    {
        $this->assertTrue(
            (bool) ($this->columnFor($table, $column)['nullable'] ?? false),
            "{$table}.{$column} should remain nullable"
        );
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
     * @return array<string, list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>, on_delete: string}>>
     */
    private function expectedSafeForeignKeys(): array
    {
        return [
            'album_images' => [
                ['columns' => ['album_id'], 'foreign_table' => 'albums', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['page_id'], 'foreign_table' => 'pages', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'albums' => [
                ['columns' => ['page_id'], 'foreign_table' => 'pages', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'blogs' => [
                ['columns' => ['category_id'], 'foreign_table' => 'blogcategories', 'foreign_columns' => ['id'], 'on_delete' => 'set null'],
            ],
            'events' => [
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'followers' => [
                ['columns' => ['page_id'], 'foreign_table' => 'pages', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'group_members' => [
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'invites' => [
                ['columns' => ['event_id'], 'foreign_table' => 'events', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['page_id'], 'foreign_table' => 'pages', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['post_id'], 'foreign_table' => 'posts', 'foreign_columns' => ['post_id'], 'on_delete' => 'cascade'],
            ],
            'marketplaces' => [
                ['columns' => ['currency_id'], 'foreign_table' => 'currencies', 'foreign_columns' => ['id'], 'on_delete' => 'restrict'],
            ],
            'media_files' => [
                ['columns' => ['post_id'], 'foreign_table' => 'posts', 'foreign_columns' => ['post_id'], 'on_delete' => 'cascade'],
                ['columns' => ['story_id'], 'foreign_table' => 'stories', 'foreign_columns' => ['story_id'], 'on_delete' => 'cascade'],
                ['columns' => ['album_id'], 'foreign_table' => 'albums', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['product_id'], 'foreign_table' => 'marketplaces', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['page_id'], 'foreign_table' => 'pages', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['chat_id'], 'foreign_table' => 'chats', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['album_image_id'], 'foreign_table' => 'album_images', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'notifications' => [
                ['columns' => ['event_id'], 'foreign_table' => 'events', 'foreign_columns' => ['id'], 'on_delete' => 'set null'],
                ['columns' => ['page_id'], 'foreign_table' => 'pages', 'foreign_columns' => ['id'], 'on_delete' => 'set null'],
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'set null'],
            ],
            'page_likes' => [
                ['columns' => ['page_id'], 'foreign_table' => 'pages', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'pages' => [
                ['columns' => ['category_id'], 'foreign_table' => 'pagecategories', 'foreign_columns' => ['id'], 'on_delete' => 'set null'],
            ],
            'post_shares' => [
                ['columns' => ['post_id'], 'foreign_table' => 'posts', 'foreign_columns' => ['post_id'], 'on_delete' => 'cascade'],
            ],
            'reports' => [
                ['columns' => ['post_id'], 'foreign_table' => 'posts', 'foreign_columns' => ['post_id'], 'on_delete' => 'cascade'],
            ],
            'saved_products' => [
                ['columns' => ['product_id'], 'foreign_table' => 'marketplaces', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'saveforlaters' => [
                ['columns' => ['video_id'], 'foreign_table' => 'videos', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['post_id'], 'foreign_table' => 'posts', 'foreign_columns' => ['post_id'], 'on_delete' => 'cascade'],
                ['columns' => ['marketplace_id'], 'foreign_table' => 'marketplaces', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['event_id'], 'foreign_table' => 'events', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['blog_id'], 'foreign_table' => 'blogs', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedForeignKeyHelperIndexes(): array
    {
        return [
            'album_images' => [
                'album_images_page_id_fk_idx' => ['page_id'],
                'album_images_group_id_fk_idx' => ['group_id'],
            ],
            'invites' => [
                'invites_event_id_fk_idx' => ['event_id'],
                'invites_page_id_fk_idx' => ['page_id'],
                'invites_group_id_fk_idx' => ['group_id'],
                'invites_post_id_fk_idx' => ['post_id'],
            ],
            'saveforlaters' => [
                'saveforlaters_video_id_fk_idx' => ['video_id'],
                'saveforlaters_group_id_fk_idx' => ['group_id'],
                'saveforlaters_post_id_fk_idx' => ['post_id'],
                'saveforlaters_marketplace_id_fk_idx' => ['marketplace_id'],
                'saveforlaters_event_id_fk_idx' => ['event_id'],
                'saveforlaters_blog_id_fk_idx' => ['blog_id'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedSafeUniqueIndexes(): array
    {
        return [
            'addons' => [
                'addons_unique_identifier_unique' => ['unique_identifier'],
            ],
            'blogcategories' => [
                'blogcategories_name_unique' => ['name'],
            ],
            'brands' => [
                'brands_name_unique' => ['name'],
            ],
            'categories' => [
                'categories_name_unique' => ['name'],
            ],
            'currencies' => [
                'currencies_code_unique' => ['code'],
            ],
            'payment_gateways' => [
                'payment_gateways_identifier_unique' => ['identifier'],
            ],
            'pagecategories' => [
                'pagecategories_name_unique' => ['name'],
            ],
            'block_users' => [
                'block_users_user_block_unique' => ['user_id', 'block_user'],
            ],
            'followers' => [
                'followers_user_follow_unique' => ['user_id', 'follow_id'],
                'followers_user_page_unique' => ['user_id', 'page_id'],
                'followers_user_group_unique' => ['user_id', 'group_id'],
            ],
            'group_members' => [
                'group_members_user_group_unique' => ['user_id', 'group_id'],
            ],
            'page_likes' => [
                'page_likes_user_page_unique' => ['user_id', 'page_id'],
            ],
            'saved_products' => [
                'saved_products_user_product_unique' => ['user_id', 'product_id'],
            ],
            'saveforlaters' => [
                'saveforlaters_user_video_unique' => ['user_id', 'video_id'],
                'saveforlaters_user_group_unique' => ['user_id', 'group_id'],
                'saveforlaters_user_post_unique' => ['user_id', 'post_id'],
                'saveforlaters_user_marketplace_unique' => ['user_id', 'marketplace_id'],
                'saveforlaters_user_event_unique' => ['user_id', 'event_id'],
                'saveforlaters_user_blog_unique' => ['user_id', 'blog_id'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array{default?: string|null}>>
     */
    private function expectedSafeRequiredColumns(): array
    {
        return [
            'account_active_requests' => [
                'status' => ['default' => 'pending'],
            ],
            'currencies' => [
                'name' => [],
                'code' => [],
                'symbol' => [],
                'paypal_supported' => ['default' => '0'],
                'stripe_supported' => ['default' => '0'],
            ],
            'payment_gateways' => [
                'identifier' => [],
                'currency' => [],
                'title' => [],
                'test_mode' => ['default' => '1'],
                'status' => ['default' => '0'],
                'is_addon' => ['default' => '0'],
            ],
            'settings' => [
                'type' => [],
            ],
            'users' => [
                'name' => [],
                'email' => [],
                'password' => [],
                'user_role' => ['default' => 'general'],
                'status' => ['default' => '0'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedLegacyMoneyColumnTypes(): array
    {
        return [
            'marketplaces' => [
                'price' => ['text', 'varchar'],
            ],
            'payment_histories' => [
                'amount' => ['double', 'real'],
            ],
            'sponsors' => [
                'paid_amount' => ['double', 'real'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedSafeMoneyColumnTypes(): array
    {
        return [
            'marketplaces' => [
                'price' => ['numeric'],
            ],
            'payment_histories' => [
                'amount' => ['numeric'],
            ],
            'sponsors' => [
                'paid_amount' => ['numeric'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedLegacyDatetimeColumnTypes(): array
    {
        return [
            'personal_access_tokens' => [
                'expires_at' => ['text', 'varchar'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedSafeDatetimeColumnTypes(): array
    {
        return [
            'personal_access_tokens' => [
                'expires_at' => ['datetime', 'timestamp'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedSafeDatetimeIndexes(): array
    {
        return [
            'personal_access_tokens' => [
                'personal_access_tokens_expires_id_idx' => ['expires_at', 'id'],
            ],
            'sponsors' => [
                'sponsors_status_start_end_id_idx' => ['status', 'start_date', 'end_date', 'id'],
            ],
            'users' => [
                'users_email_verified_id_idx' => ['email_verified_at', 'id'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedLegacyJsonColumnTypes(): array
    {
        return [
            'payment_gateways' => [
                'keys' => ['text', 'varchar'],
            ],
            'payment_histories' => [
                'transaction_keys' => ['text', 'varchar'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedSafeJsonColumnTypes(): array
    {
        $jsonTypeNames = $this->expectedJsonTypeNames();

        return [
            'payment_gateways' => [
                'keys' => $jsonTypeNames,
            ],
            'payment_histories' => [
                'transaction_keys' => $jsonTypeNames,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function expectedJsonTypeNames(): array
    {
        return DB::connection()->getDriverName() === 'sqlite' ? ['json', 'text'] : ['json'];
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

    /**
     * @return list<string>
     */
    private function uniqueIndexesFor(string $table): array
    {
        return collect(Schema::getIndexes($table))
            ->filter(fn (array $index): bool => ($index['unique'] ?? false) === true)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function columnFor(string $table, string $column): array
    {
        $columns = collect(Schema::getColumns($table))->keyBy('name');

        $this->assertTrue($columns->has($column), "{$table}.{$column} is missing");

        return $columns->get($column);
    }

    private function normalizedColumnDefault(string $table, string $column): ?string
    {
        $default = $this->columnFor($table, $column)['default'] ?? null;

        if ($default === null) {
            return null;
        }

        $default = trim((string) $default);
        $default = trim($default, "'\"");

        return strtoupper($default) === 'NULL' ? null : $default;
    }

    private function columnTypeName(string $table, string $column): string
    {
        return strtolower((string) ($this->columnFor($table, $column)['type_name'] ?? ''));
    }

    private function migrationHasUnsafeJsonValues(object $migration, string $table, string $column): bool
    {
        $method = new ReflectionMethod($migration, 'hasUnsafeJsonValues');
        $method->setAccessible(true);

        return (bool) $method->invoke($migration, $table, $column);
    }

    /**
     * @return list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>, on_delete: string}>
     */
    private function foreignKeysFor(string $table): array
    {
        return collect(Schema::getForeignKeys($table))
            ->map(fn (array $foreignKey): array => [
                'columns' => $foreignKey['columns'],
                'foreign_table' => $foreignKey['foreign_table'],
                'foreign_columns' => $foreignKey['foreign_columns'],
                'on_delete' => strtolower(str_replace('no action', 'restrict', $foreignKey['on_delete'])),
            ])
            ->values()
            ->all();
    }
}
