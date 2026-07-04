<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
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

        Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);

        gc_collect_cycles();
        if (function_exists('gc_mem_caches')) {
            gc_mem_caches();
        }
    }
}
