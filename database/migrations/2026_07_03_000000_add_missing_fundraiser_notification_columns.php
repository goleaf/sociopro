<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invites') && ! Schema::hasColumn('invites', 'fundraiser_id')) {
            Schema::table('invites', function (Blueprint $table): void {
                $table->unsignedBigInteger('fundraiser_id')->nullable()->after('group_id');
            });
        }

        if (Schema::hasTable('notifications') && ! Schema::hasColumn('notifications', 'fundraiser_id')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->unsignedBigInteger('fundraiser_id')->nullable()->after('group_id');
            });
        }

        $this->addIndex('invites', ['invite_sender_id', 'invite_reciver_id', 'fundraiser_id'], 'invites_sender_receiver_fundraiser_idx');
        $this->addIndex('notifications', ['fundraiser_id', 'status'], 'notifications_fundraiser_status_idx');
    }

    public function down(): void
    {
        $this->dropIndex('notifications', 'notifications_fundraiser_status_idx');
        $this->dropIndex('invites', 'invites_sender_receiver_fundraiser_idx');

        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'fundraiser_id')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->dropColumn('fundraiser_id');
            });
        }

        if (Schema::hasTable('invites') && Schema::hasColumn('invites', 'fundraiser_id')) {
            Schema::table('invites', function (Blueprint $table): void {
                $table->dropColumn('fundraiser_id');
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function addIndex(string $tableName, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasIndex($tableName, $indexName)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndex(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }
};
