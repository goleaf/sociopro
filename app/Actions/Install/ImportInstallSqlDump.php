<?php

namespace App\Actions\Install;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportInstallSqlDump
{
    public function handle(string $dumpPath): void
    {
        if (! is_file($dumpPath)) {
            throw new RuntimeException('Install SQL dump was not found.');
        }

        $sql = file_get_contents($dumpPath);
        $connection = DB::connection();

        $statements = $connection->getDriverName() === 'sqlite'
            ? $this->sqliteStatements($sql)
            : $this->mysqlStatements($sql);

        foreach ($statements as $statement) {
            $connection->getPdo()->exec($statement);
        }
    }

    private function mysqlStatements(string $sql): array
    {
        return array_values(array_filter(
            $this->splitStatements($this->stripDumpComments($sql)),
            fn (string $statement): bool => trim($statement) !== ''
        ));
    }

    private function sqliteStatements(string $sql): array
    {
        $cleanSql = $this->stripDumpComments($sql);
        $primaryKeys = $this->primaryKeys($cleanSql);
        $autoIncrementColumns = $this->autoIncrementColumns($cleanSql);
        $statements = [];

        foreach ($this->splitStatements($cleanSql) as $statement) {
            $statement = trim($statement);

            if ($statement === '' || $this->shouldSkipForSqlite($statement)) {
                continue;
            }

            if (preg_match('/^CREATE TABLE\s+`([^`]+)`\s*\((.*)\)\s*(?:ENGINE=.*)?$/is', $statement, $matches)) {
                $statements[] = $this->createTableForSqlite(
                    $matches[1],
                    $matches[2],
                    $primaryKeys,
                    $autoIncrementColumns
                );

                continue;
            }

            if (stripos($statement, 'INSERT INTO') === 0) {
                $statements[] = $this->mysqlInsertForSqlite($statement);
            }
        }

        return array_merge($statements, $this->sqliteIndexes($cleanSql));
    }

    private function stripDumpComments(string $sql): string
    {
        $sql = preg_replace('/\/\*![\s\S]*?\*\/;?/', '', $sql) ?? $sql;
        $lines = preg_split('/\R/', $sql) ?: [];

        $lines = array_filter($lines, function (string $line): bool {
            $trimmed = trim($line);

            return $trimmed !== '' && ! str_starts_with($trimmed, '--');
        });

        return implode("\n", $lines);
    }

    private function splitStatements(string $sql): array
    {
        $statements = [];
        $statement = '';
        $inSingleQuote = false;
        $isEscaped = false;
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];

            if ($character === "'" && ! $isEscaped) {
                $inSingleQuote = ! $inSingleQuote;
            }

            if ($character === ';' && ! $inSingleQuote) {
                $statements[] = trim($statement);
                $statement = '';
                $isEscaped = false;

                continue;
            }

            $statement .= $character;
            $isEscaped = $character === '\\' && ! $isEscaped;
        }

        if (trim($statement) !== '') {
            $statements[] = trim($statement);
        }

        return $statements;
    }

    private function shouldSkipForSqlite(string $statement): bool
    {
        return preg_match('/^(SET|START TRANSACTION|COMMIT|ALTER TABLE)/i', $statement) === 1;
    }

    private function primaryKeys(string $sql): array
    {
        $primaryKeys = [];

        foreach ($this->splitStatements($sql) as $statement) {
            if (! preg_match('/^ALTER TABLE\s+`([^`]+)`\s+(.*)$/is', trim($statement), $matches)) {
                continue;
            }

            foreach ($this->splitDefinitions($matches[2]) as $definition) {
                if (preg_match('/ADD PRIMARY KEY\s+\(`([^`]+)`\)/i', trim($definition), $primaryKey)) {
                    $primaryKeys[$matches[1]] = $primaryKey[1];
                }
            }
        }

        return $primaryKeys;
    }

    private function autoIncrementColumns(string $sql): array
    {
        $columns = [];

        foreach ($this->splitStatements($sql) as $statement) {
            if (preg_match('/^ALTER TABLE\s+`([^`]+)`\s+MODIFY\s+`([^`]+)`.*AUTO_INCREMENT/is', trim($statement), $matches)) {
                $columns[$matches[1]] = $matches[2];
            }
        }

        return $columns;
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

    private function mysqlInsertForSqlite(string $statement): string
    {
        $statement = preg_replace('/`([^`]+)`/', '"$1"', $statement) ?? $statement;

        return str_replace("\\'", "''", $statement);
    }

    private function sqliteIndexes(string $sql): array
    {
        $indexes = [];

        foreach ($this->splitStatements($sql) as $statement) {
            if (! preg_match('/^ALTER TABLE\s+`([^`]+)`\s+(.*)$/is', trim($statement), $matches)) {
                continue;
            }

            $table = $matches[1];

            foreach ($this->splitDefinitions($matches[2]) as $definition) {
                $definition = trim($definition);

                if (preg_match('/ADD UNIQUE KEY\s+`([^`]+)`\s+\(([^)]+)\)/i', $definition, $index)) {
                    $indexes[] = $this->indexStatement($table, $index[1], $index[2], true);
                }

                if (preg_match('/ADD KEY\s+`([^`]+)`\s+\(([^)]+)\)/i', $definition, $index)) {
                    $indexes[] = $this->indexStatement($table, $index[1], $index[2]);
                }
            }
        }

        return $indexes;
    }

    private function indexStatement(string $table, string $name, string $columns, bool $unique = false): string
    {
        $columns = array_map(
            fn (string $column): string => $this->quoteIdentifier(trim($column, " `\t\n\r\0\x0B")),
            explode(',', $columns)
        );

        return sprintf(
            'CREATE %sINDEX IF NOT EXISTS %s ON %s (%s)',
            $unique ? 'UNIQUE ' : '',
            $this->quoteIdentifier($name),
            $this->quoteIdentifier($table),
            implode(', ', $columns)
        );
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
