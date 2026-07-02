<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<string, array{legacy_type: 'string', length: int}>>
     */
    private array $datetimeColumns = [
        'personal_access_tokens' => [
            'expires_at' => ['legacy_type' => 'string', 'length' => 255],
        ],
    ];

    /**
     * @var array<string, array<string, list<string>>>
     */
    private array $indexes = [
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

    public function up(): void
    {
        foreach ($this->datetimeColumns as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_keys($columns) as $columnName) {
                if (! Schema::hasColumn($tableName, $columnName)
                    || $this->columnAlreadyDateTime($tableName, $columnName)
                    || $this->hasUnsafeDateTimeValues($tableName, $columnName)
                ) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($columnName): void {
                    $table->dateTime($columnName)->nullable()->change();
                });
            }
        }

        foreach ($this->indexes as $tableName => $indexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (! $this->hasColumns($tableName, $columns)
                    || Schema::hasIndex($tableName, $name)
                    || Schema::hasIndex($tableName, $columns)
                ) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($columns, $name): void {
                    $table->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes, true) as $tableName => $indexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_reverse($indexes, true) as $name => $columns) {
                if (! $this->hasColumns($tableName, $columns) || ! Schema::hasIndex($tableName, $name)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($name): void {
                    $table->dropIndex($name);
                });
            }
        }

        foreach (array_reverse($this->datetimeColumns, true) as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_reverse($columns, true) as $columnName => $definition) {
                if (! Schema::hasColumn($tableName, $columnName)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($columnName, $definition): void {
                    $table->string($columnName, $definition['length'])->nullable()->change();
                });
            }
        }
    }

    private function columnAlreadyDateTime(string $tableName, string $columnName): bool
    {
        return in_array($this->columnTypeName($tableName, $columnName), ['datetime', 'timestamp'], true);
    }

    private function hasUnsafeDateTimeValues(string $tableName, string $columnName): bool
    {
        $rows = DB::table($tableName)
            ->select($columnName)
            ->whereNotNull($columnName)
            ->cursor();

        foreach ($rows as $row) {
            $value = trim((string) $row->{$columnName});

            if (! $this->isSafeDateTimeValue($value)) {
                return true;
            }
        }

        return false;
    }

    private function isSafeDateTimeValue(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i:s.u', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.uP'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date instanceof DateTimeImmutable
                && ($errors === false || (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0))
                && $date->format($format) === $value
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasColumns(string $tableName, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return false;
            }
        }

        return true;
    }

    private function columnTypeName(string $tableName, string $columnName): string
    {
        foreach (Schema::getColumns($tableName) as $column) {
            if (($column['name'] ?? null) === $columnName) {
                return strtolower((string) ($column['type_name'] ?? ''));
            }
        }

        return '';
    }
};
