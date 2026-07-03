<?php

namespace Tests\Feature;

use App\Actions\Install\ConfigureDatabase;
use App\Actions\Install\UpdateEnvironmentFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class ConfigureDatabaseTest extends TestCase
{
    private string $originalDefaultConnection;

    private string $originalSqliteDatabase;

    /**
     * @var array<string, mixed>
     */
    private array $originalMysqlConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');
        $this->originalSqliteDatabase = config('database.connections.sqlite.database');
        $this->originalMysqlConfig = config('database.connections.mysql');
    }

    protected function tearDown(): void
    {
        Config::set('database.default', $this->originalDefaultConnection);
        Config::set('database.connections.sqlite.database', $this->originalSqliteDatabase);
        Config::set('database.connections.mysql', $this->originalMysqlConfig);
        DB::setDefaultConnection($this->originalDefaultConnection);
        DB::purge('sqlite');
        DB::purge('mysql');

        parent::tearDown();
    }

    public function test_constructor_stores_environment_updater_dependency(): void
    {
        $environment = Mockery::mock(UpdateEnvironmentFile::class);
        $action = new ConfigureDatabase($environment);

        $property = new ReflectionProperty(ConfigureDatabase::class, 'environment');

        $this->assertSame($environment, $property->getValue($action));
    }

    public function test_it_configures_sqlite_connection_from_install_session(): void
    {
        $database = database_path('configured-install.sqlite');

        session([
            'db_connection' => 'sqlite',
            'dbname' => $database,
        ]);

        $action = $this->makeActionExpectingEnvironmentUpdate([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $database,
            'DB_HOST' => '',
            'DB_PORT' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
        ]);

        $action->handle();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame($database, config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_configure_sqlite_writes_environment_and_runtime_connection(): void
    {
        $database = database_path('configured-private-sqlite.sqlite');

        session([
            'dbname' => $database,
        ]);

        $action = $this->makeActionExpectingEnvironmentUpdate([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $database,
            'DB_HOST' => '',
            'DB_PORT' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
        ]);

        $this->invokePrivateMethod($action, 'configureSqlite');

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame($database, config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_it_configures_mysql_connection_from_install_session(): void
    {
        session([
            'db_connection' => 'mysql',
            'hostname' => 'database-host',
            'dbname' => 'sociopro',
            'username' => 'installer',
            'password' => 'db-pass',
        ]);

        $action = $this->makeActionExpectingEnvironmentUpdate([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'database-host',
            'DB_PORT' => config('database.connections.mysql.port', '3306'),
            'DB_DATABASE' => 'sociopro',
            'DB_USERNAME' => 'installer',
            'DB_PASSWORD' => 'db-pass',
        ]);

        $action->handle();

        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('database-host', config('database.connections.mysql.host'));
        $this->assertSame('sociopro', config('database.connections.mysql.database'));
        $this->assertSame('installer', config('database.connections.mysql.username'));
        $this->assertSame('db-pass', config('database.connections.mysql.password'));
        $this->assertSame('mysql', DB::getDefaultConnection());
    }

    public function test_configure_mysql_writes_environment_and_runtime_connection(): void
    {
        session([
            'hostname' => 'private-database-host',
            'dbname' => 'private_sociopro',
            'username' => 'private-installer',
            'password' => 'private-db-pass',
        ]);

        $action = $this->makeActionExpectingEnvironmentUpdate([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'private-database-host',
            'DB_PORT' => config('database.connections.mysql.port', '3306'),
            'DB_DATABASE' => 'private_sociopro',
            'DB_USERNAME' => 'private-installer',
            'DB_PASSWORD' => 'private-db-pass',
        ]);

        $this->invokePrivateMethod($action, 'configureMysql');

        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('private-database-host', config('database.connections.mysql.host'));
        $this->assertSame('private_sociopro', config('database.connections.mysql.database'));
        $this->assertSame('private-installer', config('database.connections.mysql.username'));
        $this->assertSame('private-db-pass', config('database.connections.mysql.password'));
        $this->assertSame('mysql', DB::getDefaultConnection());
    }

    private function makeActionExpectingEnvironmentUpdate(array $values): ConfigureDatabase
    {
        $environment = Mockery::mock(UpdateEnvironmentFile::class);
        $environment->shouldReceive('handle')->once()->with($values);

        return new ConfigureDatabase($environment);
    }

    private function invokePrivateMethod(ConfigureDatabase $action, string $method): void
    {
        (new ReflectionMethod(ConfigureDatabase::class, $method))->invoke($action);
    }
}
