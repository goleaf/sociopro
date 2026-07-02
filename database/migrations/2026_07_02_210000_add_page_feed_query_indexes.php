<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive indexes for page/media feed reads. These avoid constraints,
     * type changes, and deletes so legacy production data remains untouched.
     *
     * @var array<string, array<string, array{columns: list<string>, reason: string}>>
     */
    private array $indexes = [
        'media_files' => [
            'media_files_page_id_id_idx' => [
                'columns' => ['page_id', 'id'],
                'reason' => 'Supports mixed page media sidebars filtered by page_id and ordered by newest media id.',
            ],
        ],
        'posts' => [
            'posts_publisher_privacy_created_post_idx' => [
                'columns' => ['publisher', 'publisher_id', 'privacy', 'created_at', 'post_id'],
                'reason' => 'Supports public page/group/event timeline API reads filtered by publisher and privacy, ordered by created_at with a deterministic post_id tie-breaker.',
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
