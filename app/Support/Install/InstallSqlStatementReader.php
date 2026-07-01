<?php

namespace App\Support\Install;

use Generator;
use RuntimeException;
use SplFileObject;

final class InstallSqlStatementReader
{
    /**
     * @return Generator<int, string>
     */
    public function statements(string $dumpPath): Generator
    {
        if (! is_file($dumpPath)) {
            throw new RuntimeException('Install SQL dump was not found.');
        }

        if (! is_readable($dumpPath)) {
            throw new RuntimeException('Install SQL dump is not readable.');
        }

        $file = new SplFileObject($dumpPath, 'r');
        $statement = '';
        $inSingleQuote = false;
        $isEscaped = false;

        while (! $file->eof()) {
            $line = $file->fgets();

            if ($line === false) {
                break;
            }

            $line = $this->stripVersionComments($line);
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $length = strlen($line);

            for ($index = 0; $index < $length; $index++) {
                $character = $line[$index];

                if ($character === "'" && ! $isEscaped) {
                    $inSingleQuote = ! $inSingleQuote;
                }

                if ($character === ';' && ! $inSingleQuote) {
                    $trimmedStatement = trim($statement);

                    if ($trimmedStatement !== '') {
                        yield $trimmedStatement;
                    }

                    $statement = '';
                    $isEscaped = false;

                    continue;
                }

                $statement .= $character;
                $isEscaped = $character === '\\' && ! $isEscaped;
            }
        }

        $statement = trim($statement);

        if ($statement !== '') {
            yield $statement;
        }
    }

    private function stripVersionComments(string $line): string
    {
        return preg_replace('/\/\*![\s\S]*?\*\/;?/', '', $line) ?? $line;
    }
}
