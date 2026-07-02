<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PivotTableAuditTest extends TestCase
{
    public function test_pivot_like_tables_have_expected_names_columns_and_timestamps(): void
    {
        foreach ($this->pivotTableColumns() as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "{$table} table is missing.");

            foreach (array_merge($columns, ['created_at', 'updated_at']) as $column) {
                $this->assertTrue(Schema::hasColumn($table, $column), "{$table}.{$column} is missing.");
            }
        }
    }

    public function test_owner_target_pivot_tables_have_composite_unique_constraints(): void
    {
        $this->runSchemaMigration('2026_07_02_160000_add_safe_legacy_unique_constraints.php');

        foreach ($this->expectedPivotUniqueIndexes() as $table => $indexes) {
            $uniqueIndexes = $this->uniqueIndexesFor($table);

            foreach ($indexes as $name => $columns) {
                $this->assertSame($columns, $uniqueIndexes[$name] ?? null, "{$table}.{$name}");
            }
        }
    }

    public function test_pivot_tables_have_reverse_lookup_indexes(): void
    {
        $this->runSchemaMigration('2026_07_01_150000_add_safe_legacy_lookup_indexes.php');
        $this->runSchemaMigration('2026_07_02_130000_add_safe_legacy_relationship_indexes.php');
        $this->runSchemaMigration('2026_07_02_140000_add_query_pattern_coverage_indexes.php');

        foreach ($this->expectedPivotLookupIndexes() as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                $this->assertTrue(Schema::hasIndex($table, $columns), "{$table}.{$name}");
            }
        }
    }

    public function test_target_side_pivot_foreign_keys_have_cascade_behavior(): void
    {
        $this->runSchemaMigration('2026_07_02_150000_add_safe_legacy_foreign_key_constraints.php');

        foreach ($this->expectedPivotForeignKeys() as $table => $foreignKeys) {
            $actualForeignKeys = $this->foreignKeysFor($table);

            foreach ($foreignKeys as $foreignKey) {
                $this->assertContains($foreignKey, $actualForeignKeys, $table.'.'.implode('_', $foreignKey['columns']));
            }
        }
    }

    public function test_pivot_table_audit_documents_deferred_constraints_and_non_pivots(): void
    {
        $path = base_path('docs/pivot-table-audit.md');

        $this->assertFileExists($path);

        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString('User-side foreign keys are deferred', $contents);
        $this->assertStringContainsString('friendships', $contents);
        $this->assertStringContainsString('post_shares', $contents);
    }

    private function runSchemaMigration(string $filename): void
    {
        $migration = require database_path("migrations/{$filename}");

        $migration->up();
    }

    /**
     * @return array<string, list<string>>
     */
    private function pivotTableColumns(): array
    {
        return [
            'block_users' => ['id', 'user_id', 'block_user'],
            'followers' => ['id', 'user_id', 'follow_id', 'page_id', 'group_id'],
            'group_members' => ['id', 'user_id', 'group_id', 'is_accepted', 'role'],
            'page_likes' => ['id', 'user_id', 'page_id', 'role'],
            'saved_products' => ['id', 'user_id', 'product_id'],
            'saveforlaters' => ['id', 'user_id', 'video_id', 'group_id', 'post_id', 'marketplace_id', 'event_id', 'blog_id'],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function expectedPivotUniqueIndexes(): array
    {
        return [
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
     * @return array<string, array<string, list<string>>>
     */
    private function expectedPivotLookupIndexes(): array
    {
        return [
            'block_users' => [
                'block_users_user_block_lookup' => ['user_id', 'block_user'],
            ],
            'followers' => [
                'followers_follow_lookup' => ['follow_id'],
                'followers_page_lookup' => ['page_id'],
                'followers_group_lookup' => ['group_id'],
            ],
            'group_members' => [
                'group_members_group_status_lookup' => ['group_id', 'is_accepted'],
                'group_members_user_status_lookup' => ['user_id', 'is_accepted'],
            ],
            'page_likes' => [
                'page_likes_user_page_lookup' => ['user_id', 'page_id'],
                'page_likes_page_user_lookup' => ['page_id', 'user_id'],
            ],
            'saved_products' => [
                'saved_products_user_product_lookup' => ['user_id', 'product_id'],
                'saved_products_product_lookup' => ['product_id'],
            ],
            'saveforlaters' => [
                'saveforlaters_user_video_lookup' => ['user_id', 'video_id'],
                'saveforlaters_user_group_lookup' => ['user_id', 'group_id'],
                'saveforlaters_user_post_lookup' => ['user_id', 'post_id'],
                'saveforlaters_user_marketplace_lookup' => ['user_id', 'marketplace_id'],
                'saveforlaters_user_event_lookup' => ['user_id', 'event_id'],
                'saveforlaters_user_blog_lookup' => ['user_id', 'blog_id'],
            ],
        ];
    }

    /**
     * @return array<string, list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>, on_delete: string}>>
     */
    private function expectedPivotForeignKeys(): array
    {
        return [
            'followers' => [
                ['columns' => ['page_id'], 'foreign_table' => 'pages', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'group_members' => [
                ['columns' => ['group_id'], 'foreign_table' => 'groups', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
            ],
            'page_likes' => [
                ['columns' => ['page_id'], 'foreign_table' => 'pages', 'foreign_columns' => ['id'], 'on_delete' => 'cascade'],
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
    private function uniqueIndexesFor(string $table): array
    {
        return collect(Schema::getIndexes($table))
            ->filter(fn (array $index): bool => ($index['unique'] ?? false) === true)
            ->mapWithKeys(fn (array $index): array => [$index['name'] => $index['columns']])
            ->all();
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
