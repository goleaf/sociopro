<?php

namespace Tests;

use App\Actions\Install\ImportInstallSqlDump;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importInstallSchemaForInMemoryDatabase();
    }

    private function importInstallSchemaForInMemoryDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        if (config('database.connections.sqlite.database') !== ':memory:') {
            return;
        }

        if (Schema::hasTable('settings')) {
            return;
        }

        app(ImportInstallSqlDump::class)->handle((string) config('install.schema_dump_path'), batchSize: 100);

        gc_collect_cycles();
        if (function_exists('gc_mem_caches')) {
            gc_mem_caches();
        }
    }
}
