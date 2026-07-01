<?php

namespace Tests\Unit;

use App\Support\Install\InstallSqlInsertParser;
use PHPUnit\Framework\TestCase;

class InstallSqlInsertParserTest extends TestCase
{
    public function test_it_parses_mysql_insert_rows_with_escaped_values(): void
    {
        $insert = (new InstallSqlInsertParser)->parse(<<<'SQL'
INSERT INTO `widgets` (`id`, `name`, `payload`, `optional_value`) VALUES
(1, 'Alice\'s widget, large', '{\"enabled\":true}', NULL),
(2, 'Semi; colon', 'plain text', '2026-07-01 00:00:00')
SQL);

        $this->assertNotNull($insert);
        $this->assertSame('widgets', $insert->table);
        $this->assertSame(['id', 'name', 'payload', 'optional_value'], $insert->columns);
        $this->assertSame([
            [
                'id' => 1,
                'name' => "Alice's widget, large",
                'payload' => '{"enabled":true}',
                'optional_value' => null,
            ],
            [
                'id' => 2,
                'name' => 'Semi; colon',
                'payload' => 'plain text',
                'optional_value' => '2026-07-01 00:00:00',
            ],
        ], $insert->rows);
    }
}
