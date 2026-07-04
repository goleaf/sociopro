<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed production-safe schema and reference data only.
     *
     * Local/demo rows live in LocalDemoSeeder and are intentionally opt-in.
     */
    public function run(): void
    {
        if (Schema::hasTable('settings') && DB::table('settings')->exists()) {
            return;
        }

        $this->call(LegacyInstallSchemaSeeder::class);
    }
}
