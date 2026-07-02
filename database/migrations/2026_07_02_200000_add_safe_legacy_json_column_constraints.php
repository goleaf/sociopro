<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<string, array{legacy_type: 'longText'|'text'}>>
     */
    private array $jsonColumns = [
        'payment_gateways' => [
            'keys' => ['legacy_type' => 'text'],
        ],
        'payment_histories' => [
            'transaction_keys' => ['legacy_type' => 'longText'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->jsonColumns as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_keys($columns) as $columnName) {
                if (! Schema::hasColumn($tableName, $columnName)
                    || $this->columnAlreadyJson($tableName, $columnName)
                    || $this->hasUnsafeJsonValues($tableName, $columnName)
                ) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($columnName): void {
                    $table->json($columnName)->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->jsonColumns, true) as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_reverse($columns, true) as $columnName => $definition) {
                if (! Schema::hasColumn($tableName, $columnName)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($columnName, $definition): void {
                    $column = match ($definition['legacy_type']) {
                        'longText' => $table->longText($columnName),
                        'text' => $table->text($columnName),
                    };

                    $column->nullable()->change();
                });
            }
        }
    }

    private function columnAlreadyJson(string $tableName, string $columnName): bool
    {
        return $this->columnTypeName($tableName, $columnName) === 'json';
    }

    private function hasUnsafeJsonValues(string $tableName, string $columnName): bool
    {
        $rows = DB::table($tableName)
            ->select($columnName)
            ->whereNotNull($columnName)
            ->cursor();

        foreach ($rows as $row) {
            $value = trim((string) $row->{$columnName});

            if ($value === '') {
                return true;
            }

            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                return true;
            }
        }

        return false;
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
