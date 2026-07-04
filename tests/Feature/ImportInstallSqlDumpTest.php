<?php

namespace Tests\Feature;

use App\Actions\Install\ImportInstallSqlDump;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Jobs\ImportInstallSqlDumpJob;
use App\Models\User;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PDO;
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

    public function test_it_batches_rows_and_reports_duplicates_without_reimporting_existing_data(): void
    {
        File::ensureDirectoryExists(dirname($this->dumpPath));
        File::put($this->dumpPath, <<<'SQL'
CREATE TABLE `widgets` (
  `id` int(11) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `name` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `widgets` (`id`, `slug`, `name`) VALUES
(1, 'first', 'First widget'),
(1, 'first-duplicate', 'Duplicate first widget'),
(2, 'second', 'Second widget');

ALTER TABLE `widgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `widgets_slug_unique` (`slug`);

ALTER TABLE `widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
SQL);

        $firstImport = app(ImportInstallSqlDump::class)->handle($this->dumpPath, batchSize: 2);

        $this->assertSame(2, DB::table('widgets')->count());
        $this->assertSame(2, $firstImport->insertedRows());
        $this->assertSame(1, $firstImport->duplicateRows());
        $this->assertSame(0, $firstImport->failedRows());
        $this->assertSame('First widget', DB::table('widgets')->where('id', 1)->value('name'));

        $secondImport = app(ImportInstallSqlDump::class)->handle($this->dumpPath, batchSize: 2);

        $this->assertSame(2, DB::table('widgets')->count());
        $this->assertSame(0, $secondImport->insertedRows());
        $this->assertSame(3, $secondImport->duplicateRows());
        $this->assertSame(0, $secondImport->failedRows());
    }

    public function test_import_job_can_process_install_dump_as_queued_work(): void
    {
        File::ensureDirectoryExists(dirname($this->dumpPath));
        File::put($this->dumpPath, <<<'SQL'
CREATE TABLE `queued_widgets` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `queued_widgets` (`id`, `name`) VALUES
(1, 'Queued widget');

ALTER TABLE `queued_widgets`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `queued_widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
SQL);

        (new ImportInstallSqlDumpJob($this->dumpPath, batchSize: 1))
            ->handle(app(ImportInstallSqlDump::class));

        $this->assertSame('Queued widget', DB::table('queued_widgets')->where('id', 1)->value('name'));
    }

    public function test_it_reports_row_level_errors_without_discarding_valid_rows(): void
    {
        File::ensureDirectoryExists(dirname($this->dumpPath));
        File::put($this->dumpPath, <<<'SQL'
CREATE TABLE `validated_widgets` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `validated_widgets` (`id`, `name`) VALUES
(1, 'Valid widget'),
(2, NULL),
(3, 'Another valid widget');

ALTER TABLE `validated_widgets`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `validated_widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
SQL);

        $result = app(ImportInstallSqlDump::class)->handle($this->dumpPath, batchSize: 3);

        $this->assertSame(2, DB::table('validated_widgets')->count());
        $this->assertSame(2, $result->insertedRows());
        $this->assertSame(1, $result->failedRows());
        $this->assertTrue($result->hasFailures());
        $this->assertSame('validated_widgets', $result->errors()[0]['table']);
        $this->assertSame(2, $result->errors()[0]['row']);
    }

    public function test_native_import_skips_destructive_or_unexpected_dump_statements(): void
    {
        File::ensureDirectoryExists(dirname($this->dumpPath));
        File::put($this->dumpPath, <<<'SQL'
CREATE TABLE `native_widgets` (
  `id` integer NOT NULL,
  `name` text
);

INSERT INTO `native_widgets` (`id`, `name`) VALUES
(1, 'Safe widget');

DROP TABLE `native_widgets`;
DELETE FROM `native_widgets`;
UPDATE `native_widgets` SET `name` = 'Injected widget';

ALTER TABLE `native_widgets` ADD COLUMN `extra` text;
SQL);

        DB::extend('native_sqlite_test', function (array $config): SQLiteConnection {
            $pdo = new PDO('sqlite:'.$config['database']);

            return new class($pdo, $config['database'], '', $config) extends SQLiteConnection
            {
                public function getDriverName()
                {
                    return 'mysql';
                }
            };
        });

        Config::set('database.default', 'native_sqlite_test');
        Config::set('database.connections.native_sqlite_test', [
            'driver' => 'native_sqlite_test',
            'database' => $this->databasePath,
            'prefix' => '',
        ]);

        DB::purge('native_sqlite_test');

        try {
            $result = app(ImportInstallSqlDump::class)->handle($this->dumpPath);

            $this->assertSame(3, $result->skippedStatements());
            $this->assertSame(1, $result->insertStatements());
            $this->assertSame(1, $result->insertedRows());
            $this->assertSame('Safe widget', DB::table('native_widgets')->where('id', 1)->value('name'));
            $this->assertTrue(Schema::hasColumn('native_widgets', 'extra'));
        } finally {
            DB::disconnect('native_sqlite_test');
            DB::purge('native_sqlite_test');
            DB::forgetExtension('native_sqlite_test');

            Config::set('database.default', 'sqlite');
        }
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
        $this->assertFalse(DB::table('settings')->where('type', 'hug'.'ging_face_auth_key')->exists());

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

    public function test_local_demo_seeder_creates_configured_local_admin_user(): void
    {
        $this->assertTrue(class_exists('Database\\Seeders\\LocalDemoSeeder'));

        $this->seed();

        Config::set('local.admin.email', 'configured-admin@example.test');
        Config::set('local.admin.username', 'configured-admin');
        Config::set('local.admin.password', 'local-admin-password');

        app('Database\\Seeders\\LocalDemoSeeder')->run();
        app('Database\\Seeders\\LocalDemoSeeder')->run();

        $admin = User::query()->where('email', 'configured-admin@example.test')->first();

        $this->assertInstanceOf(User::class, $admin);
        $this->assertSame('Local Admin', $admin->name);
        $this->assertSame('configured-admin', $admin->username);
        $this->assertSame(UserRole::Admin->value, $admin->user_role);
        $this->assertSame(UserAccountStatus::Active->value, (int) $admin->status);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('local-admin-password', $admin->password));
        $this->assertSame(1, User::query()->where('email', 'configured-admin@example.test')->count());
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
