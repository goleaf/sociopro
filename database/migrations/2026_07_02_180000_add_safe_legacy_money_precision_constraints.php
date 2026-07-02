<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TOTAL_DIGITS = 12;

    private const DECIMAL_PLACES = 2;

    /**
     * @var array<string, array<string, array{legacy_type: 'double'|'string', length?: int}>>
     */
    private array $moneyColumns = [
        'marketplaces' => [
            'price' => ['legacy_type' => 'string', 'length' => 15],
        ],
        'payment_histories' => [
            'amount' => ['legacy_type' => 'double'],
        ],
        'sponsors' => [
            'paid_amount' => ['legacy_type' => 'double'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->moneyColumns as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_keys($columns) as $columnName) {
                if (! Schema::hasColumn($tableName, $columnName)
                    || $this->columnAlreadyDecimal($tableName, $columnName)
                    || $this->hasUnsafeMoneyValues($tableName, $columnName)
                ) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($columnName): void {
                    $table->decimal($columnName, total: self::TOTAL_DIGITS, places: self::DECIMAL_PLACES)
                        ->nullable()
                        ->change();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->moneyColumns, true) as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_reverse($columns, true) as $columnName => $definition) {
                if (! Schema::hasColumn($tableName, $columnName)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($columnName, $definition): void {
                    $column = match ($definition['legacy_type']) {
                        'double' => $table->double($columnName),
                        'string' => $table->string($columnName, $definition['length'] ?? 255),
                    };

                    $column->nullable()->change();
                });
            }
        }
    }

    private function columnAlreadyDecimal(string $tableName, string $columnName): bool
    {
        return in_array($this->columnTypeName($tableName, $columnName), ['decimal', 'numeric'], true);
    }

    private function hasUnsafeMoneyValues(string $tableName, string $columnName): bool
    {
        $rows = DB::table($tableName)
            ->select($columnName)
            ->whereNotNull($columnName)
            ->cursor();

        foreach ($rows as $row) {
            $value = trim((string) $row->{$columnName});

            if (preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $value) !== 1) {
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
