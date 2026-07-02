<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These constraints cover legacy relationships with compatible MySQL column
     * types and clear child lifecycles. Relationships to users and legacy text
     * foreign-key columns stay documented cleanup work until their types match.
     *
     * @var array<string, array<string, array{columns: list<string>, references: list<string>, on: string, onDelete: 'cascade'|'restrict'|'set null'}>>
     */
    private array $foreignKeys = [
        'album_images' => [
            'album_images_album_id_fk' => ['columns' => ['album_id'], 'references' => ['id'], 'on' => 'albums', 'onDelete' => 'cascade'],
            'album_images_page_id_fk' => ['columns' => ['page_id'], 'references' => ['id'], 'on' => 'pages', 'onDelete' => 'cascade'],
            'album_images_group_id_fk' => ['columns' => ['group_id'], 'references' => ['id'], 'on' => 'groups', 'onDelete' => 'cascade'],
        ],
        'albums' => [
            'albums_page_id_fk' => ['columns' => ['page_id'], 'references' => ['id'], 'on' => 'pages', 'onDelete' => 'cascade'],
            'albums_group_id_fk' => ['columns' => ['group_id'], 'references' => ['id'], 'on' => 'groups', 'onDelete' => 'cascade'],
        ],
        'blogs' => [
            'blogs_category_id_fk' => ['columns' => ['category_id'], 'references' => ['id'], 'on' => 'blogcategories', 'onDelete' => 'set null'],
        ],
        'events' => [
            'events_group_id_fk' => ['columns' => ['group_id'], 'references' => ['id'], 'on' => 'groups', 'onDelete' => 'cascade'],
        ],
        'followers' => [
            'followers_page_id_fk' => ['columns' => ['page_id'], 'references' => ['id'], 'on' => 'pages', 'onDelete' => 'cascade'],
            'followers_group_id_fk' => ['columns' => ['group_id'], 'references' => ['id'], 'on' => 'groups', 'onDelete' => 'cascade'],
        ],
        'group_members' => [
            'group_members_group_id_fk' => ['columns' => ['group_id'], 'references' => ['id'], 'on' => 'groups', 'onDelete' => 'cascade'],
        ],
        'invites' => [
            'invites_event_id_fk' => ['columns' => ['event_id'], 'references' => ['id'], 'on' => 'events', 'onDelete' => 'cascade'],
            'invites_page_id_fk' => ['columns' => ['page_id'], 'references' => ['id'], 'on' => 'pages', 'onDelete' => 'cascade'],
            'invites_group_id_fk' => ['columns' => ['group_id'], 'references' => ['id'], 'on' => 'groups', 'onDelete' => 'cascade'],
            'invites_post_id_fk' => ['columns' => ['post_id'], 'references' => ['post_id'], 'on' => 'posts', 'onDelete' => 'cascade'],
        ],
        'marketplaces' => [
            'marketplaces_currency_id_fk' => ['columns' => ['currency_id'], 'references' => ['id'], 'on' => 'currencies', 'onDelete' => 'restrict'],
        ],
        'media_files' => [
            'media_files_post_id_fk' => ['columns' => ['post_id'], 'references' => ['post_id'], 'on' => 'posts', 'onDelete' => 'cascade'],
            'media_files_story_id_fk' => ['columns' => ['story_id'], 'references' => ['story_id'], 'on' => 'stories', 'onDelete' => 'cascade'],
            'media_files_album_id_fk' => ['columns' => ['album_id'], 'references' => ['id'], 'on' => 'albums', 'onDelete' => 'cascade'],
            'media_files_product_id_fk' => ['columns' => ['product_id'], 'references' => ['id'], 'on' => 'marketplaces', 'onDelete' => 'cascade'],
            'media_files_page_id_fk' => ['columns' => ['page_id'], 'references' => ['id'], 'on' => 'pages', 'onDelete' => 'cascade'],
            'media_files_group_id_fk' => ['columns' => ['group_id'], 'references' => ['id'], 'on' => 'groups', 'onDelete' => 'cascade'],
            'media_files_chat_id_fk' => ['columns' => ['chat_id'], 'references' => ['id'], 'on' => 'chats', 'onDelete' => 'cascade'],
            'media_files_album_image_id_fk' => ['columns' => ['album_image_id'], 'references' => ['id'], 'on' => 'album_images', 'onDelete' => 'cascade'],
        ],
        'notifications' => [
            'notifications_event_id_fk' => ['columns' => ['event_id'], 'references' => ['id'], 'on' => 'events', 'onDelete' => 'set null'],
            'notifications_page_id_fk' => ['columns' => ['page_id'], 'references' => ['id'], 'on' => 'pages', 'onDelete' => 'set null'],
            'notifications_group_id_fk' => ['columns' => ['group_id'], 'references' => ['id'], 'on' => 'groups', 'onDelete' => 'set null'],
        ],
        'page_likes' => [
            'page_likes_page_id_fk' => ['columns' => ['page_id'], 'references' => ['id'], 'on' => 'pages', 'onDelete' => 'cascade'],
        ],
        'pages' => [
            'pages_category_id_fk' => ['columns' => ['category_id'], 'references' => ['id'], 'on' => 'pagecategories', 'onDelete' => 'set null'],
        ],
        'post_shares' => [
            'post_shares_post_id_fk' => ['columns' => ['post_id'], 'references' => ['post_id'], 'on' => 'posts', 'onDelete' => 'cascade'],
        ],
        'reports' => [
            'reports_post_id_fk' => ['columns' => ['post_id'], 'references' => ['post_id'], 'on' => 'posts', 'onDelete' => 'cascade'],
        ],
        'saved_products' => [
            'saved_products_product_id_fk' => ['columns' => ['product_id'], 'references' => ['id'], 'on' => 'marketplaces', 'onDelete' => 'cascade'],
        ],
        'saveforlaters' => [
            'saveforlaters_video_id_fk' => ['columns' => ['video_id'], 'references' => ['id'], 'on' => 'videos', 'onDelete' => 'cascade'],
            'saveforlaters_group_id_fk' => ['columns' => ['group_id'], 'references' => ['id'], 'on' => 'groups', 'onDelete' => 'cascade'],
            'saveforlaters_post_id_fk' => ['columns' => ['post_id'], 'references' => ['post_id'], 'on' => 'posts', 'onDelete' => 'cascade'],
            'saveforlaters_marketplace_id_fk' => ['columns' => ['marketplace_id'], 'references' => ['id'], 'on' => 'marketplaces', 'onDelete' => 'cascade'],
            'saveforlaters_event_id_fk' => ['columns' => ['event_id'], 'references' => ['id'], 'on' => 'events', 'onDelete' => 'cascade'],
            'saveforlaters_blog_id_fk' => ['columns' => ['blog_id'], 'references' => ['id'], 'on' => 'blogs', 'onDelete' => 'cascade'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->foreignKeys as $table => $foreignKeys) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($foreignKeys as $name => $definition) {
                if (! $this->canCreateForeignKey($table, $definition) || $this->foreignKeyExists($table, $definition)) {
                    continue;
                }

                $this->ensureLeadingIndex($table, $definition['columns']);

                Schema::table($table, function (Blueprint $table) use ($definition, $name): void {
                    $foreign = $table->foreign($definition['columns'], $name)
                        ->references($definition['references'])
                        ->on($definition['on']);

                    match ($definition['onDelete']) {
                        'cascade' => $foreign->cascadeOnDelete(),
                        'set null' => $foreign->nullOnDelete(),
                        'restrict' => $foreign->restrictOnDelete(),
                    };
                });
            }
        }
    }

    public function down(): void
    {
        // Keep helper indexes: rollback cannot prove whether a helper-named index
        // was created here or already existed for production query performance.
        foreach (array_reverse($this->foreignKeys, true) as $table => $foreignKeys) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_reverse($foreignKeys, true) as $name => $definition) {
                if ($this->foreignKeyExists($table, $definition)) {
                    Schema::table($table, function (Blueprint $table) use ($definition, $name): void {
                        $table->dropForeign($this->dropForeignArgument($name, $definition['columns']));
                    });
                }
            }
        }
    }

    /**
     * @param  array{columns: list<string>, references: list<string>, on: string, onDelete: string}  $definition
     */
    private function canCreateForeignKey(string $table, array $definition): bool
    {
        return Schema::hasTable($definition['on'])
            && $this->hasColumns($table, $definition['columns'])
            && $this->hasColumns($definition['on'], $definition['references'])
            && $this->hasNullableColumnsWhenRequired($table, $definition)
            && ! $this->hasOrphans($table, $definition);
    }

    /**
     * @param  array{columns: list<string>, references: list<string>, on: string, onDelete: string}  $definition
     */
    private function hasNullableColumnsWhenRequired(string $table, array $definition): bool
    {
        if ($definition['onDelete'] !== 'set null') {
            return true;
        }

        foreach (Schema::getColumns($table) as $column) {
            if (! in_array($column['name'] ?? null, $definition['columns'], true)) {
                continue;
            }

            if (($column['nullable'] ?? false) !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{columns: list<string>, references: list<string>, on: string, onDelete: string}  $definition
     */
    private function hasOrphans(string $table, array $definition): bool
    {
        $childColumn = $definition['columns'][0];
        $parentColumn = $definition['references'][0];
        $parentTable = $definition['on'];

        return DB::table($table.' as child')
            ->leftJoin($parentTable.' as parent', 'parent.'.$parentColumn, '=', 'child.'.$childColumn)
            ->whereNotNull('child.'.$childColumn)
            ->whereNull('parent.'.$parentColumn)
            ->exists();
    }

    /**
     * @param  array{columns: list<string>, references: list<string>, on: string, onDelete: string}  $definition
     */
    private function foreignKeyExists(string $table, array $definition): bool
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (($foreignKey['columns'] ?? []) === $definition['columns']
                && ($foreignKey['foreign_table'] ?? null) === $definition['on']
                && ($foreignKey['foreign_columns'] ?? []) === $definition['references']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $columns
     */
    private function ensureLeadingIndex(string $table, array $columns): void
    {
        if ($this->hasLeadingIndex($table, $columns[0])) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($columns): void {
            $table->index($columns, $this->indexName($table->getTable(), $columns[0]));
        });
    }

    private function hasLeadingIndex(string $table, string $column): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['columns'][0] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }

    private function indexName(string $table, string $column): string
    {
        return $table.'_'.$column.'_fk_idx';
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropForeignArgument(string $name, array $columns): string|array
    {
        return DB::getDriverName() === 'sqlite' ? $columns : $name;
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
