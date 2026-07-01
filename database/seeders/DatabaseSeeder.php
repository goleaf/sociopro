<?php

namespace Database\Seeders;

use App\Actions\Install\ImportInstallSqlDump;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed production-safe schema and reference data only.
     *
     * Local/demo rows live in LocalDemoSeeder and are intentionally opt-in.
     */
    public function run(ImportInstallSqlDump $importInstallSqlDump): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        $importInstallSqlDump->handle(base_path('public/assets/install.sql'));
    }
}
