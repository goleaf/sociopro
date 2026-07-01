<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private array $indexes = [
        'marketplaces_status_id_idx' => ['status', 'id'],
        'marketplaces_status_created_id_idx' => ['status', 'created_at', 'id'],
        'marketplaces_status_price_id_idx' => ['status', 'price', 'id'],
        'marketplaces_status_title_id_idx' => ['status', 'title', 'id'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('marketplaces')) {
            return;
        }

        foreach ($this->indexes as $name => $columns) {
            if (! $this->hasColumns($columns)
                || Schema::hasIndex('marketplaces', $name)
                || Schema::hasIndex('marketplaces', $columns)
            ) {
                continue;
            }

            Schema::table('marketplaces', function (Blueprint $table) use ($columns, $name): void {
                $table->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplaces')) {
            return;
        }

        foreach (array_reverse($this->indexes, true) as $name => $columns) {
            if (! $this->hasColumns($columns) || ! Schema::hasIndex('marketplaces', $name)) {
                continue;
            }

            Schema::table('marketplaces', function (Blueprint $table) use ($name): void {
                $table->dropIndex($name);
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasColumns(array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn('marketplaces', $column)) {
                return false;
            }
        }

        return true;
    }
};
