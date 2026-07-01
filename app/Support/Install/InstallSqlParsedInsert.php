<?php

namespace App\Support\Install;

final class InstallSqlParsedInsert
{
    /**
     * @param  list<string>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        public readonly string $table,
        public readonly array $columns,
        public readonly array $rows
    ) {}
}
