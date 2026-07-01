<?php

namespace Tests\Feature;

use App\Actions\Install\UpdateEnvironmentFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ConfigureDatabaseTest extends TestCase
{
    private const ACTION_CLASS = 'App\\Actions\\Install\\ConfigureDatabase';

    private string $originalDefaultConnection;

    private string $originalSqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');
        $this->originalSqliteDatabase = config('database.connections.sqlite.database');
    }

    protected function tearDown(): void
    {
        Config::set('database.default', $this->originalDefaultConnection);
        Config::set('database.connections.sqlite.database', $this->originalSqliteDatabase);
        DB::setDefaultConnection($this->originalDefaultConnection);
        DB::purge('sqlite');
        DB::purge('mysql');

        parent::tearDown();
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

    private function makeActionExpectingEnvironmentUpdate(array $values): object
    {
        $this->assertTrue(class_exists(self::ACTION_CLASS));

        $environment = Mockery::mock(UpdateEnvironmentFile::class);
        $environment->shouldReceive('handle')->once()->with($values);

        $actionClass = self::ACTION_CLASS;

        return new $actionClass($environment);
    }
}
