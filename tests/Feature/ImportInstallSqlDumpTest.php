<?php

namespace Tests\Feature;

use App\Actions\Install\ImportInstallSqlDump;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
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

    public function test_database_seeder_imports_install_sql_dump(): void
    {
        $this->seed();

        $this->assertTrue(Schema::hasTable('settings'));
        $this->assertGreaterThan(0, DB::table('settings')->count());
        $this->assertGreaterThan(0, DB::table('currencies')->count());
    }

    public function test_database_seeder_imports_sanitized_reference_data_without_demo_users_or_secrets(): void
    {
        $this->seed();

        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame('support@example.test', DB::table('settings')->where('type', 'system_email')->value('description'));
        $this->assertSame('', DB::table('settings')->where('type', 'hugging_face_auth_key')->value('description'));

        $this->assertSame([
            'smtp_protocol' => 'smtp',
            'smtp_crypto' => 'tls',
            'smtp_host' => '',
            'smtp_port' => '',
            'smtp_user' => '',
            'smtp_pass' => '',
        ], $this->settingPayload('smtp'));

        $this->assertSame([
            'account_email' => '',
            'jitsi_app_id' => '',
            'jitsi_jwt' => '',
        ], $this->settingPayload('zitsi_configuration'));

        foreach (DB::table('payment_gateways')->pluck('keys', 'identifier') as $identifier => $keys) {
            $decodedKeys = json_decode((string) $keys, true);

            $this->assertIsArray($decodedKeys, "Gateway [{$identifier}] keys must remain JSON.");

            foreach ($this->scalarValues($decodedKeys) as $value) {
                $this->assertSame('', $value, "Gateway [{$identifier}] seeds must not include credential values.");
            }
        }
    }

    public function test_local_demo_seeder_refuses_production_environment(): void
    {
        $this->assertTrue(class_exists('Database\\Seeders\\LocalDemoSeeder'));

        $this->app->detectEnvironment(fn (): string => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Local demo seeder may only run in local or testing environments.');

        app('Database\\Seeders\\LocalDemoSeeder')->run();
    }

    public function test_local_demo_seeder_uses_factories_and_is_repeat_safe(): void
    {
        $this->assertTrue(class_exists('Database\\Seeders\\LocalDemoSeeder'));

        $this->seed();
        app('Database\\Seeders\\LocalDemoSeeder')->run();

        $firstCounts = $this->demoCounts();

        app('Database\\Seeders\\LocalDemoSeeder')->run();

        $this->assertSame($firstCounts, $this->demoCounts());
        $this->assertSame(1, DB::table('users')->where('email', 'local-demo@example.test')->count());
        $this->assertSame(1, DB::table('marketplaces')->where('title', 'Local demo marketplace product')->count());
    }

    /**
     * @return array<string, string>
     */
    private function settingPayload(string $type): array
    {
        $payload = json_decode((string) DB::table('settings')->where('type', $type)->value('description'), true);

        $this->assertIsArray($payload);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    private function scalarValues(array $values): array
    {
        $scalars = [];

        array_walk_recursive($values, function (mixed $value) use (&$scalars): void {
            if (is_scalar($value) || $value === null) {
                $scalars[] = (string) $value;
            }
        });

        return $scalars;
    }

    /**
     * @return array{users: int, categories: int, brands: int, marketplaces: int}
     */
    private function demoCounts(): array
    {
        return [
            'users' => DB::table('users')->where('email', 'local-demo@example.test')->count(),
            'categories' => DB::table('categories')->where('name', 'Local Demo Electronics')->count(),
            'brands' => DB::table('brands')->where('name', 'Local Demo Brand')->count(),
            'marketplaces' => DB::table('marketplaces')->where('title', 'Local demo marketplace product')->count(),
        ];
    }
}
