<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<string, array{type: 'string'|'integer', length?: int, default?: string|int, reject_blank?: bool}>>
     */
    private array $requiredColumns = [
        'account_active_requests' => [
            'status' => ['type' => 'string', 'length' => 100, 'default' => 'pending', 'reject_blank' => true],
        ],
        'currencies' => [
            'name' => ['type' => 'string', 'length' => 255, 'reject_blank' => true],
            'code' => ['type' => 'string', 'length' => 255, 'reject_blank' => true],
            'symbol' => ['type' => 'string', 'length' => 255, 'reject_blank' => true],
            'paypal_supported' => ['type' => 'integer', 'default' => 0],
            'stripe_supported' => ['type' => 'integer', 'default' => 0],
        ],
        'payment_gateways' => [
            'identifier' => ['type' => 'string', 'length' => 255, 'reject_blank' => true],
            'currency' => ['type' => 'string', 'length' => 100, 'reject_blank' => true],
            'title' => ['type' => 'string', 'length' => 255, 'reject_blank' => true],
            'test_mode' => ['type' => 'integer', 'default' => 1],
            'status' => ['type' => 'integer', 'default' => 0],
            'is_addon' => ['type' => 'integer', 'default' => 0],
        ],
        'settings' => [
            'type' => ['type' => 'string', 'length' => 255, 'reject_blank' => true],
        ],
        'users' => [
            'name' => ['type' => 'string', 'length' => 255, 'reject_blank' => true],
            'email' => ['type' => 'string', 'length' => 255, 'reject_blank' => true],
            'password' => ['type' => 'string', 'length' => 255, 'reject_blank' => true],
            'user_role' => ['type' => 'string', 'length' => 255, 'default' => 'general', 'reject_blank' => true],
            'status' => ['type' => 'string', 'length' => 100, 'default' => '0', 'reject_blank' => true],
        ],
    ];

    public function up(): void
    {
        foreach ($this->requiredColumns as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($columns as $columnName => $definition) {
                if (! Schema::hasColumn($tableName, $columnName)
                    || $this->hasUnsafeValues($tableName, $columnName, (bool) ($definition['reject_blank'] ?? false))
                    || $this->columnAlreadyRequired($tableName, $columnName, $definition)
                ) {
                    continue;
                }

                $this->changeColumn($tableName, $columnName, $definition, required: true);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->requiredColumns, true) as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_reverse($columns, true) as $columnName => $definition) {
                if (! Schema::hasColumn($tableName, $columnName)
                    || $this->columnAlreadyNullable($tableName, $columnName)
                ) {
                    continue;
                }

                $this->changeColumn($tableName, $columnName, $definition, required: false);
            }
        }
    }

    /**
     * @param  array{type: 'string'|'integer', length?: int, default?: string|int, reject_blank?: bool}  $definition
     */
    private function changeColumn(string $tableName, string $columnName, array $definition, bool $required): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($columnName, $definition, $required): void {
            $column = match ($definition['type']) {
                'integer' => $table->integer($columnName),
                'string' => $table->string($columnName, $definition['length'] ?? 255),
            };

            if ($required) {
                $column->nullable(false);

                if (array_key_exists('default', $definition)) {
                    $column->default($definition['default']);
                }
            } else {
                $column->nullable()->default(null);
            }

            $column->change();
        });
    }

    private function hasUnsafeValues(string $tableName, string $columnName, bool $rejectBlank): bool
    {
        if (DB::table($tableName)->whereNull($columnName)->exists()) {
            return true;
        }

        return $rejectBlank && DB::table($tableName)->where($columnName, '')->exists();
    }

    /**
     * @param  array{type: 'string'|'integer', length?: int, default?: string|int, reject_blank?: bool}  $definition
     */
    private function columnAlreadyRequired(string $tableName, string $columnName, array $definition): bool
    {
        $column = $this->columnFor($tableName, $columnName);

        if ((bool) ($column['nullable'] ?? true)) {
            return false;
        }

        if (! array_key_exists('default', $definition)) {
            return true;
        }

        return $this->normalizeDefault($column['default'] ?? null) === (string) $definition['default'];
    }

    private function columnAlreadyNullable(string $tableName, string $columnName): bool
    {
        return (bool) ($this->columnFor($tableName, $columnName)['nullable'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    private function columnFor(string $tableName, string $columnName): array
    {
        foreach (Schema::getColumns($tableName) as $column) {
            if (($column['name'] ?? null) === $columnName) {
                return $column;
            }
        }

        return [];
    }

    private function normalizeDefault(mixed $default): ?string
    {
        if ($default === null) {
            return null;
        }

        $default = trim((string) $default);
        $default = trim($default, "'\"");

        return strtoupper($default) === 'NULL' ? null : $default;
    }
};
