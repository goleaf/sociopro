<?php

namespace App\Actions\Install;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ConfigureDatabase
{
    public function __construct(private readonly UpdateEnvironmentFile $environment) {}

    public function handle(): void
    {
        if (session('db_connection', 'mysql') === 'sqlite') {
            $this->configureSqlite();

            return;
        }

        $this->configureMysql();
    }

    private function configureSqlite(): void
    {
        $database = session('dbname', database_path('database.sqlite'));

        $this->environment->handle([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $database,
            'DB_HOST' => '',
            'DB_PORT' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
        ]);

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $database);

        DB::setDefaultConnection('sqlite');
        DB::purge('sqlite');
    }

    private function configureMysql(): void
    {
        $this->environment->handle([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => session('hostname'),
            'DB_PORT' => config('database.connections.mysql.port', '3306'),
            'DB_DATABASE' => session('dbname'),
            'DB_USERNAME' => session('username'),
            'DB_PASSWORD' => session('password'),
        ]);

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.host', session('hostname'));
        Config::set('database.connections.mysql.database', session('dbname'));
        Config::set('database.connections.mysql.username', session('username'));
        Config::set('database.connections.mysql.password', session('password'));

        DB::setDefaultConnection('mysql');
        DB::purge('mysql');
    }
}
