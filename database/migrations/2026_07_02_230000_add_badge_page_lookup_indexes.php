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
        'batchs_user_id_id_idx' => ['user_id', 'id'],
        'batchs_user_status_dates_id_idx' => ['user_id', 'status', 'start_date', 'end_date', 'id'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('batchs')) {
            return;
        }

        foreach ($this->indexes as $name => $columns) {
            if (! $this->hasColumns($columns)
                || Schema::hasIndex('batchs', $name)
                || Schema::hasIndex('batchs', $columns)
            ) {
                continue;
            }

            Schema::table('batchs', function (Blueprint $table) use ($columns, $name): void {
                $table->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('batchs')) {
            return;
        }

        foreach (array_reverse($this->indexes, true) as $name => $columns) {
            if (! $this->hasColumns($columns) || ! Schema::hasIndex('batchs', $name)) {
                continue;
            }

            Schema::table('batchs', function (Blueprint $table) use ($name): void {
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
            if (! Schema::hasColumn('batchs', $column)) {
                return false;
            }
        }

        return true;
    }
};
