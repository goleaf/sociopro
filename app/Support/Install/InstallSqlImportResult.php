<?php

namespace App\Support\Install;

final class InstallSqlImportResult
{
    private int $schemaStatements = 0;

    private int $insertStatements = 0;

    private int $insertedRows = 0;

    private int $duplicateRows = 0;

    private int $failedRows = 0;

    private int $skippedStatements = 0;

    /**
     * @var list<array{table: string|null, row: int|null, message: string}>
     */
    private array $errors = [];

    public function recordSchemaStatement(): void
    {
        $this->schemaStatements++;
    }

    public function recordInsertStatement(): void
    {
        $this->insertStatements++;
    }

    public function recordInsertedRows(int $count): void
    {
        $this->insertedRows += $count;
    }

    public function recordDuplicateRow(): void
    {
        $this->duplicateRows++;
    }

    public function recordSkippedStatement(): void
    {
        $this->skippedStatements++;
    }

    public function recordRowError(?string $table, ?int $row, string $message): void
    {
        $this->failedRows++;
        $this->errors[] = [
            'table' => $table,
            'row' => $row,
            'message' => $message,
        ];
    }

    public function schemaStatements(): int
    {
        return $this->schemaStatements;
    }

    public function insertStatements(): int
    {
        return $this->insertStatements;
    }

    public function insertedRows(): int
    {
        return $this->insertedRows;
    }

    public function duplicateRows(): int
    {
        return $this->duplicateRows;
    }

    public function failedRows(): int
    {
        return $this->failedRows;
    }

    public function skippedStatements(): int
    {
        return $this->skippedStatements;
    }

    public function hasFailures(): bool
    {
        return $this->failedRows > 0;
    }

    /**
     * @return list<array{table: string|null, row: int|null, message: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
