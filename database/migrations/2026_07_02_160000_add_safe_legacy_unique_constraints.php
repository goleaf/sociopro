<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<string, list<string>>>
     */
    private array $uniqueIndexes = [
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

    public function up(): void
    {
        foreach ($this->uniqueIndexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (! $this->hasColumns($table, $columns)
                    || $this->uniqueIndexExists($table, $columns)
                    || $this->indexExists($table, $name)
                    || $this->hasDuplicateValues($table, $columns)
                ) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($columns, $name): void {
                    $table->unique($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->uniqueIndexes, true) as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_reverse($indexes, true) as $name => $columns) {
                if (! $this->uniqueIndexExists($table, $columns, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($name): void {
                    $table->dropUnique($name);
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

    /**
     * @param  list<string>  $columns
     */
    private function uniqueIndexExists(string $table, array $columns, ?string $name = null): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['unique'] ?? false) !== true) {
                continue;
            }

            if ($name !== null && ($index['name'] ?? null) !== $name) {
                continue;
            }

            if (($index['columns'] ?? []) === $columns) {
                return true;
            }
        }

        return false;
    }

    private function indexExists(string $table, string $name): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasDuplicateValues(string $table, array $columns): bool
    {
        $query = DB::table($table)->select($columns);

        foreach ($columns as $column) {
            $query->whereNotNull($column)->orderBy($column);
        }

        $previousKey = null;

        foreach ($query->cursor() as $row) {
            $key = array_map(
                fn (string $column): string => (string) $row->{$column},
                $columns
            );

            if ($key === $previousKey) {
                return true;
            }

            $previousKey = $key;
        }

        return false;
    }
};
