<?php

namespace App\Actions\Install;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PrepareDatabaseConnection
{
    public function handle(array $data): array
    {
        $connection = $data['db_connection'] ?? 'mysql';

        if ($connection === 'sqlite') {
            return $this->prepareSqlite($data);
        }

        return $this->prepareMysql($data);
    }

    private function prepareSqlite(array $data): array
    {
        $database = $this->sqlitePath($data['sqlite_path'] ?? null);

        try {
            File::ensureDirectoryExists(dirname($database));

            if (! File::exists($database)) {
                File::put($database, '');
            }

            Config::set('database.default', 'sqlite');
            Config::set('database.connections.sqlite.database', $database);

            DB::purge('sqlite');
            DB::connection('sqlite')->getPdo();

            return [
                'status' => 'success',
                'session' => [
                    'db_connection' => 'sqlite',
                    'hostname' => '',
                    'username' => '',
                    'password' => '',
                    'dbname' => $database,
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'message' => 'Could not prepare the SQLite database file. Please check storage permissions.',
            ];
        }
    }

    private function prepareMysql(array $data): array
    {
        foreach (['hostname', 'username', 'dbname'] as $field) {
            if (empty($data[$field])) {
                return [
                    'status' => 'error',
                    'message' => 'Please fill in all required MySQL database fields.',
                ];
            }
        }

        $connectionName = uniqid('db', true);

        Config::set('database.connections.'.$connectionName, [
            'driver' => 'mysql',
            'host' => $data['hostname'],
            'port' => config('database.connections.mysql.port', '3306'),
            'database' => $data['dbname'],
            'username' => $data['username'],
            'password' => $data['password'] ?? '',
            'charset' => config('database.connections.mysql.charset', 'utf8mb4'),
        ]);

        try {
            DB::connection($connectionName)->getPdo();

            return [
                'status' => 'success',
                'session' => [
                    'db_connection' => 'mysql',
                    'hostname' => $data['hostname'],
                    'username' => $data['username'],
                    'password' => $data['password'] ?? '',
                    'dbname' => $data['dbname'],
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'message' => 'Could not connect to the database. Please check your configuration.',
            ];
        } finally {
            DB::purge($connectionName);
        }
    }

    private function sqlitePath(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return database_path('database.sqlite');
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return database_path($path);
    }
}
