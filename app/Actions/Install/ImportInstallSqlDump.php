<?php

namespace App\Actions\Install;

use App\Support\Install\InstallSqlImportResult;
use App\Support\Install\InstallSqlInsertParser;
use App\Support\Install\InstallSqlParsedInsert;
use App\Support\Install\InstallSqlStatementReader;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportInstallSqlDump
{
    public const DEFAULT_BATCH_SIZE = 500;

    public function __construct(
        private readonly InstallSqlStatementReader $statementReader,
        private readonly InstallSqlInsertParser $insertParser
    ) {}

    public function handle(string $dumpPath, int $batchSize = self::DEFAULT_BATCH_SIZE): InstallSqlImportResult
    {
        if ($batchSize < 1) {
            throw new RuntimeException('Import batch size must be greater than zero.');
        }

        $connection = DB::connection();
        $result = new InstallSqlImportResult;

        if ($connection->getDriverName() === 'sqlite') {
            return $this->importSqliteDump($connection, $dumpPath, $batchSize, $result);
        }

        return $this->importNativeDump($connection, $dumpPath, $result);
    }

    private function importNativeDump(
        Connection $connection,
        string $dumpPath,
        InstallSqlImportResult $result
    ): InstallSqlImportResult {
        foreach ($this->statementReader->statements($dumpPath) as $statement) {
            if (stripos($statement, 'INSERT INTO') === 0) {
                $result->recordInsertStatement();
            } else {
                $result->recordSchemaStatement();
            }

            $connection->getPdo()->exec($statement);
        }

        return $result;
    }

    private function importSqliteDump(
        Connection $connection,
        string $dumpPath,
        int $batchSize,
        InstallSqlImportResult $result
    ): InstallSqlImportResult {
        $metadata = $this->sqliteMetadata($dumpPath);

        foreach ($this->statementReader->statements($dumpPath) as $statement) {
            $statement = trim($statement);

            if ($statement === '' || $this->shouldSkipForSqlite($statement)) {
                $result->recordSkippedStatement();

                continue;
            }

            if (preg_match('/^CREATE TABLE\s+`([^`]+)`\s*\((.*)\)\s*(?:ENGINE=.*)?$/is', $statement, $matches)) {
                $connection->getPdo()->exec($this->createTableForSqlite(
                    $matches[1],
                    $matches[2],
                    $metadata['primaryKeys'],
                    $metadata['autoIncrementColumns']
                ));
                $result->recordSchemaStatement();

                continue;
            }

            if (stripos($statement, 'INSERT INTO') === 0) {
                $this->importSqliteInsert($connection, $statement, $metadata, $batchSize, $result);

                continue;
            }

            $result->recordSkippedStatement();
        }

        foreach ($metadata['indexes'] as $indexStatement) {
            $connection->getPdo()->exec($indexStatement);
            $result->recordSchemaStatement();
        }

        return $result;
    }

    /**
     * @param  array{
     *     primaryKeys: array<string, string>,
     *     autoIncrementColumns: array<string, string>,
     *     uniqueKeys: array<string, list<list<string>>>,
     *     indexes: list<string>
     * }  $metadata
     */
    private function importSqliteInsert(
        Connection $connection,
        string $statement,
        array $metadata,
        int $batchSize,
        InstallSqlImportResult $result
    ): void {
        try {
            $insert = $this->insertParser->parse($statement);
        } catch (Throwable $exception) {
            $result->recordRowError(null, null, $exception->getMessage());

            return;
        }

        if (! $insert) {
            $result->recordSkippedStatement();

            return;
        }

        $result->recordInsertStatement();

        foreach (array_chunk($insert->rows, $batchSize, true) as $batch) {
            $this->importSqliteBatch($connection, $insert, $batch, $metadata, $result);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $batch
     * @param  array{
     *     primaryKeys: array<string, string>,
     *     autoIncrementColumns: array<string, string>,
     *     uniqueKeys: array<string, list<list<string>>>,
     *     indexes: list<string>
     * }  $metadata
     */
    private function importSqliteBatch(
        Connection $connection,
        InstallSqlParsedInsert $insert,
        array $batch,
        array $metadata,
        InstallSqlImportResult $result
    ): void {
        $rows = [];
        $columnListing = $this->sqliteColumnListing($connection, $insert->table);

        foreach ($batch as $rowIndex => $row) {
            $rowNumber = ((int) $rowIndex) + 1;

            if (! $this->validateSqliteRow($insert->table, $row, $columnListing, $rowNumber, $result)) {
                continue;
            }

            if ($this->duplicateExists($connection, $insert->table, $row, $metadata)) {
                $result->recordDuplicateRow();

                continue;
            }

            $rows[$rowNumber] = $row;
        }

        if ($rows === []) {
            return;
        }

        try {
            $connection->transaction(function () use ($connection, $insert, $rows): void {
                $connection->table($insert->table)->insert(array_values($rows));
            });

            $result->recordInsertedRows(count($rows));
        } catch (Throwable) {
            foreach ($rows as $rowNumber => $row) {
                $this->importSqliteRow($connection, $insert->table, $row, $rowNumber, $metadata, $result);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{
     *     primaryKeys: array<string, string>,
     *     autoIncrementColumns: array<string, string>,
     *     uniqueKeys: array<string, list<list<string>>>,
     *     indexes: list<string>
     * }  $metadata
     */
    private function importSqliteRow(
        Connection $connection,
        string $table,
        array $row,
        int $rowNumber,
        array $metadata,
        InstallSqlImportResult $result
    ): void {
        if ($this->duplicateExists($connection, $table, $row, $metadata)) {
            $result->recordDuplicateRow();

            return;
        }

        try {
            $connection->transaction(function () use ($connection, $table, $row): void {
                $connection->table($table)->insert($row);
            });

            $result->recordInsertedRows(1);
        } catch (Throwable $exception) {
            $result->recordRowError($table, $rowNumber, $exception->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $columnListing
     */
    private function validateSqliteRow(
        string $table,
        array $row,
        array $columnListing,
        int $rowNumber,
        InstallSqlImportResult $result
    ): bool {
        if ($columnListing === []) {
            $result->recordRowError($table, $rowNumber, 'Import table does not exist.');

            return false;
        }

        $unknownColumns = array_values(array_diff(array_keys($row), $columnListing));

        if ($unknownColumns !== []) {
            $result->recordRowError(
                $table,
                $rowNumber,
                'Unknown import columns: '.implode(', ', $unknownColumns)
            );

            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function sqliteColumnListing(Connection $connection, string $table): array
    {
        try {
            return $connection->getSchemaBuilder()->getColumnListing($table);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{
     *     primaryKeys: array<string, string>,
     *     autoIncrementColumns: array<string, string>,
     *     uniqueKeys: array<string, list<list<string>>>,
     *     indexes: list<string>
     * }  $metadata
     */
    private function duplicateExists(Connection $connection, string $table, array $row, array $metadata): bool
    {
        foreach ($this->uniqueChecksForRow($table, $row, $metadata) as $columns) {
            $query = $connection->table($table);

            foreach ($columns as $column) {
                $query->where($column, $row[$column]);
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{
     *     primaryKeys: array<string, string>,
     *     autoIncrementColumns: array<string, string>,
     *     uniqueKeys: array<string, list<list<string>>>,
     *     indexes: list<string>
     * }  $metadata
     * @return list<list<string>>
     */
    private function uniqueChecksForRow(string $table, array $row, array $metadata): array
    {
        $checks = [];
        $primaryKey = $metadata['primaryKeys'][$table] ?? null;

        if ($primaryKey && array_key_exists($primaryKey, $row) && $row[$primaryKey] !== null) {
            $checks[] = [$primaryKey];
        }

        foreach ($metadata['uniqueKeys'][$table] ?? [] as $columns) {
            foreach ($columns as $column) {
                if (! array_key_exists($column, $row) || $row[$column] === null) {
                    continue 2;
                }
            }

            $checks[] = $columns;
        }

        return $checks;
    }

    private function shouldSkipForSqlite(string $statement): bool
    {
        return preg_match('/^(SET|START TRANSACTION|COMMIT|ALTER TABLE)/i', $statement) === 1;
    }

    /**
     * @return array{
     *     primaryKeys: array<string, string>,
     *     autoIncrementColumns: array<string, string>,
     *     uniqueKeys: array<string, list<list<string>>>,
     *     indexes: list<string>
     * }
     */
    private function sqliteMetadata(string $dumpPath): array
    {
        $metadata = [
            'primaryKeys' => [],
            'autoIncrementColumns' => [],
            'uniqueKeys' => [],
            'indexes' => [],
        ];

        foreach ($this->statementReader->statements($dumpPath) as $statement) {
            $statement = trim($statement);

            if (preg_match('/^ALTER TABLE\s+`([^`]+)`\s+MODIFY\s+`([^`]+)`.*AUTO_INCREMENT/is', $statement, $matches)) {
                $metadata['autoIncrementColumns'][$matches[1]] = $matches[2];

                continue;
            }

            if (! preg_match('/^ALTER TABLE\s+`([^`]+)`\s+(.*)$/is', $statement, $matches)) {
                continue;
            }

            $table = $matches[1];

            foreach ($this->splitDefinitions($matches[2]) as $definition) {
                $definition = trim($definition);

                if (preg_match('/ADD PRIMARY KEY\s+\(`([^`]+)`\)/i', $definition, $primaryKey)) {
                    $metadata['primaryKeys'][$table] = $primaryKey[1];

                    continue;
                }

                if (preg_match('/ADD UNIQUE KEY\s+`([^`]+)`\s+\(([^)]+)\)/i', $definition, $index)) {
                    $metadata['uniqueKeys'][$table][] = $this->columnsFromIndex($index[2]);
                    $metadata['indexes'][] = $this->indexStatement($table, $index[1], $index[2], true);

                    continue;
                }

                if (preg_match('/ADD KEY\s+`([^`]+)`\s+\(([^)]+)\)/i', $definition, $index)) {
                    $metadata['indexes'][] = $this->indexStatement($table, $index[1], $index[2]);
                }
            }
        }

        return $metadata;
    }

    private function createTableForSqlite(
        string $table,
        string $body,
        array $primaryKeys,
        array $autoIncrementColumns
    ): string {
        $columns = [];

        foreach ($this->splitDefinitions($body) as $definition) {
            $definition = trim($definition);

            if (! str_starts_with($definition, '`')) {
                continue;
            }

            $columns[] = $this->columnForSqlite(
                $table,
                $definition,
                $primaryKeys[$table] ?? null,
                $autoIncrementColumns[$table] ?? null
            );
        }

        if ($columns === []) {
            throw new RuntimeException("Could not parse columns for {$table}.");
        }

        return 'CREATE TABLE IF NOT EXISTS '.$this->quoteIdentifier($table)." (\n  ".implode(",\n  ", $columns)."\n)";
    }

    private function columnForSqlite(
        string $table,
        string $definition,
        ?string $primaryKey,
        ?string $autoIncrementColumn
    ): string {
        if (! preg_match('/^`([^`]+)`\s+(.+)$/s', $definition, $matches)) {
            throw new RuntimeException("Could not parse column definition for {$table}.");
        }

        $name = $matches[1];
        $typeDefinition = $matches[2];

        if ($primaryKey === $name && $autoIncrementColumn === $name) {
            return $this->quoteIdentifier($name).' integer primary key autoincrement';
        }

        $column = $this->quoteIdentifier($name).' '.$this->sqliteType($typeDefinition);

        if (preg_match('/\bNOT NULL\b/i', $typeDefinition)) {
            $column .= ' NOT NULL';
        }

        if ($primaryKey === $name) {
            $column .= ' PRIMARY KEY';
        }

        $default = $this->sqliteDefault($typeDefinition);

        if ($default !== null) {
            $column .= ' DEFAULT '.$default;
        }

        return $column;
    }

    private function sqliteType(string $definition): string
    {
        $definition = strtolower($definition);

        return match (true) {
            preg_match('/^(bigint|int|integer|mediumint|smallint|tinyint)/', $definition) === 1 => 'integer',
            preg_match('/^(decimal|double|float|real)/', $definition) === 1 => 'real',
            preg_match('/^(blob|binary|varbinary)/', $definition) === 1 => 'blob',
            default => 'text',
        };
    }

    private function sqliteDefault(string $definition): ?string
    {
        $definition = preg_replace('/\s+ON UPDATE\s+CURRENT_TIMESTAMP/i', '', $definition) ?? $definition;

        if (! preg_match('/\sDEFAULT\s+((?:\'[^\']*\')|CURRENT_TIMESTAMP|NULL|[^\s,]+)/i', $definition, $matches)) {
            return null;
        }

        return strtoupper($matches[1]) === 'NULL' ? 'NULL' : $matches[1];
    }

    private function indexStatement(string $table, string $name, string $columns, bool $unique = false): string
    {
        $columns = array_map(
            fn (string $column): string => $this->quoteIdentifier($column),
            $this->columnsFromIndex($columns)
        );

        return sprintf(
            'CREATE %sINDEX IF NOT EXISTS %s ON %s (%s)',
            $unique ? 'UNIQUE ' : '',
            $this->quoteIdentifier($name),
            $this->quoteIdentifier($table),
            implode(', ', $columns)
        );
    }

    /**
     * @return list<string>
     */
    private function columnsFromIndex(string $columns): array
    {
        preg_match_all('/`([^`]+)`/', $columns, $matches);

        if ($matches[1] !== []) {
            return array_values($matches[1]);
        }

        return array_values(array_filter(array_map(
            fn (string $column): string => trim($column, " `\t\n\r\0\x0B"),
            explode(',', $columns)
        )));
    }

    private function splitDefinitions(string $body): array
    {
        $definitions = [];
        $definition = '';
        $depth = 0;
        $inSingleQuote = false;
        $isEscaped = false;
        $length = strlen($body);

        for ($index = 0; $index < $length; $index++) {
            $character = $body[$index];

            if ($character === "'" && ! $isEscaped) {
                $inSingleQuote = ! $inSingleQuote;
            }

            if (! $inSingleQuote && $character === '(') {
                $depth++;
            }

            if (! $inSingleQuote && $character === ')') {
                $depth--;
            }

            if ($character === ',' && $depth === 0 && ! $inSingleQuote) {
                $definitions[] = trim($definition);
                $definition = '';
                $isEscaped = false;

                continue;
            }

            $definition .= $character;
            $isEscaped = $character === '\\' && ! $isEscaped;
        }

        if (trim($definition) !== '') {
            $definitions[] = trim($definition);
        }

        return $definitions;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
