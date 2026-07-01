<?php

namespace App\Support\Install;

use RuntimeException;

final class InstallSqlInsertParser
{
    public function parse(string $statement): ?InstallSqlParsedInsert
    {
        $statement = trim($statement);

        if (! preg_match('/^INSERT\s+INTO\s+`([^`]+)`\s*\((.*?)\)\s+VALUES\s*(.+)$/is', $statement, $matches)) {
            return null;
        }

        $columns = $this->parseColumns($matches[2]);
        $rows = [];

        foreach ($this->parseRows($matches[3]) as $rowNumber => $values) {
            if (count($values) !== count($columns)) {
                throw new RuntimeException(sprintf(
                    'Insert row %d for table [%s] has %d values, expected %d.',
                    $rowNumber + 1,
                    $matches[1],
                    count($values),
                    count($columns)
                ));
            }

            $rows[] = array_combine($columns, $values);
        }

        return new InstallSqlParsedInsert($matches[1], $columns, $rows);
    }

    /**
     * @return list<string>
     */
    private function parseColumns(string $columns): array
    {
        preg_match_all('/`([^`]+)`/', $columns, $matches);

        if ($matches[1] === []) {
            throw new RuntimeException('Could not parse insert column list.');
        }

        return array_values($matches[1]);
    }

    /**
     * @return list<list<mixed>>
     */
    private function parseRows(string $values): array
    {
        $values = rtrim(trim($values), ';');
        $rows = [];
        $length = strlen($values);
        $index = 0;

        while ($index < $length) {
            while ($index < $length && (ctype_space($values[$index]) || $values[$index] === ',')) {
                $index++;
            }

            if ($index >= $length) {
                break;
            }

            if ($values[$index] !== '(') {
                throw new RuntimeException('Could not parse insert row values.');
            }

            [$row, $index] = $this->readParenthesizedRow($values, $index);
            $rows[] = $this->parseRowValues($row);
        }

        return $rows;
    }

    /**
     * @return array{string, int}
     */
    private function readParenthesizedRow(string $values, int $start): array
    {
        $row = '';
        $depth = 0;
        $inSingleQuote = false;
        $isEscaped = false;
        $length = strlen($values);

        for ($index = $start; $index < $length; $index++) {
            $character = $values[$index];

            if ($character === "'" && ! $isEscaped) {
                $inSingleQuote = ! $inSingleQuote;
            }

            if (! $inSingleQuote && $character === '(') {
                $depth++;

                if ($depth === 1) {
                    $isEscaped = false;

                    continue;
                }
            }

            if (! $inSingleQuote && $character === ')') {
                $depth--;

                if ($depth === 0) {
                    return [$row, $index + 1];
                }
            }

            $row .= $character;
            $isEscaped = $character === '\\' && ! $isEscaped;
        }

        throw new RuntimeException('Unclosed insert row values.');
    }

    /**
     * @return list<mixed>
     */
    private function parseRowValues(string $row): array
    {
        $values = [];
        $value = '';
        $depth = 0;
        $inSingleQuote = false;
        $isEscaped = false;
        $length = strlen($row);

        for ($index = 0; $index < $length; $index++) {
            $character = $row[$index];

            if ($character === "'" && ! $isEscaped) {
                $inSingleQuote = ! $inSingleQuote;
            }

            if (! $inSingleQuote && $character === '(') {
                $depth++;
            }

            if (! $inSingleQuote && $character === ')') {
                $depth--;
            }

            if (! $inSingleQuote && $depth === 0 && $character === ',') {
                $values[] = $this->parseScalarValue($value);
                $value = '';
                $isEscaped = false;

                continue;
            }

            $value .= $character;
            $isEscaped = $character === '\\' && ! $isEscaped;
        }

        if (trim($value) !== '') {
            $values[] = $this->parseScalarValue($value);
        }

        return $values;
    }

    private function parseScalarValue(string $value): mixed
    {
        $value = trim($value);

        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return $this->parseStringValue(substr($value, 1, -1));
        }

        if (preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        if (preg_match('/^-?\d+\.\d+$/', $value) === 1) {
            return (float) $value;
        }

        return $value;
    }

    private function parseStringValue(string $value): string
    {
        $parsed = '';
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $character = $value[$index];
            $next = $value[$index + 1] ?? null;

            if ($character === "'" && $next === "'") {
                $parsed .= "'";
                $index++;

                continue;
            }

            if ($character === '\\' && ($next === "'" || $next === '"')) {
                $parsed .= $next;
                $index++;

                continue;
            }

            $parsed .= $character;
        }

        return $parsed;
    }
}
