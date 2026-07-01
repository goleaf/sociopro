<?php

namespace Database\Seeders;

use App\Actions\Install\ImportInstallSqlDump;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(ImportInstallSqlDump $importInstallSqlDump): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        $importInstallSqlDump->handle(base_path('public/assets/install.sql'));
    }
}
