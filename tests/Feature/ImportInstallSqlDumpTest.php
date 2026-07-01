<?php

namespace Tests\Feature;

use App\Actions\Install\ImportInstallSqlDump;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportInstallSqlDumpTest extends TestCase
{
    private string $databasePath;

    private string $dumpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = database_path('test-install-import.sqlite');
        $this->dumpPath = storage_path('framework/testing/install-dump.sql');

        File::delete($this->databasePath);
        File::ensureDirectoryExists(dirname($this->databasePath));
        File::put($this->databasePath, '');

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $this->databasePath);

        DB::purge('sqlite');
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        File::delete($this->databasePath);
        File::delete($this->dumpPath);

        parent::tearDown();
    }

    public function test_it_imports_mysql_dump_into_sqlite_with_autoincrement_keys(): void
    {
        File::ensureDirectoryExists(dirname($this->dumpPath));
        File::put($this->dumpPath, <<<'SQL'
-- phpMyAdmin SQL Dump
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

CREATE TABLE `widgets` (
  `id` int(11) NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `widgets` (`id`, `slug`, `name`, `created_at`) VALUES
(1, 'first', 'First widget', '2026-07-01 00:00:00');

ALTER TABLE `widgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `widgets_slug_unique` (`slug`);

ALTER TABLE `widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;
SQL);

        app(ImportInstallSqlDump::class)->handle($this->dumpPath);

        DB::table('widgets')->insert([
            'slug' => 'second',
            'name' => 'Second widget',
        ]);

        $this->assertSame('First widget', DB::table('widgets')->where('slug', 'first')->value('name'));
        $this->assertSame(2, DB::table('widgets')->where('slug', 'second')->value('id'));
    }
}
