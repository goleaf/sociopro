<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive clean-column indexes for the chat expand/contract migration.
     * No foreign keys are added yet because legacy production data has not
     * been audited for referential integrity.
     *
     * @var array<string, array<string, list<string>>>
     */
    private array $indexes = [
        'message_thrades' => [
            'message_thrades_receiver_id_idx' => ['receiver_id'],
            'message_thrades_sender_receiver_clean_idx' => ['sender_id', 'receiver_id'],
        ],
        'chats' => [
            'chats_message_thread_id_idx' => ['message_thread_id'],
            'chats_receiver_id_idx' => ['receiver_id'],
            'chats_sender_receiver_clean_idx' => ['sender_id', 'receiver_id'],
        ],
    ];

    public function up(): void
    {
        $this->addMessageThreadColumns();
        $this->addChatColumns();

        $this->backfillColumn('message_thrades', 'receiver_id', 'reciver_id');
        $this->backfillColumn('message_thrades', 'chat_center', 'chatcenter');
        $this->backfillColumn('chats', 'message_thread_id', 'message_thrade');
        $this->backfillColumn('chats', 'receiver_id', 'reciver_id');
        $this->backfillColumn('chats', 'chat_center', 'chatcenter');

        $this->addIndexes();
    }

    public function down(): void
    {
        $this->dropIndexes();
        $this->dropColumnsIfPresent('chats', ['message_thread_id', 'receiver_id', 'chat_center']);
        $this->dropColumnsIfPresent('message_thrades', ['receiver_id', 'chat_center']);
    }

    private function addMessageThreadColumns(): void
    {
        if (! Schema::hasTable('message_thrades')) {
            return;
        }

        Schema::table('message_thrades', function (Blueprint $table): void {
            if (! Schema::hasColumn('message_thrades', 'receiver_id')) {
                $table->integer('receiver_id')->nullable()->after('reciver_id');
            }

            if (! Schema::hasColumn('message_thrades', 'chat_center')) {
                $table->text('chat_center')->nullable()->after('chatcenter');
            }
        });
    }

    private function addChatColumns(): void
    {
        if (! Schema::hasTable('chats')) {
            return;
        }

        Schema::table('chats', function (Blueprint $table): void {
            if (! Schema::hasColumn('chats', 'message_thread_id')) {
                $table->integer('message_thread_id')->nullable()->after('message_thrade');
            }

            if (! Schema::hasColumn('chats', 'receiver_id')) {
                $table->integer('receiver_id')->nullable()->after('reciver_id');
            }

            if (! Schema::hasColumn('chats', 'chat_center')) {
                $table->text('chat_center')->nullable()->after('chatcenter');
            }
        });
    }

    private function backfillColumn(string $table, string $cleanColumn, string $legacyColumn): void
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $cleanColumn)
            || ! Schema::hasColumn($table, $legacyColumn)
        ) {
            return;
        }

        DB::table($table)
            ->select(['id', $legacyColumn])
            ->whereNull($cleanColumn)
            ->whereNotNull($legacyColumn)
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows) use ($table, $cleanColumn, $legacyColumn): void {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->whereNull($cleanColumn)
                        ->update([$cleanColumn => $row->{$legacyColumn}]);
                }
            });
    }

    private function addIndexes(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (! $this->hasColumns($table, $columns)
                    || Schema::hasIndex($table, $name)
                    || Schema::hasIndex($table, $columns)
                ) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($columns, $name): void {
                    $table->index($columns, $name);
                });
            }
        }
    }

    private function dropIndexes(): void
    {
        foreach (array_reverse($this->indexes, true) as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_reverse($indexes, true) as $name => $columns) {
                if (! $this->hasColumns($table, $columns) || ! Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($name): void {
                    $table->dropIndex($name);
                });
            }
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropColumnsIfPresent(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existingColumns = array_values(array_filter(
            $columns,
            static fn (string $column): bool => Schema::hasColumn($table, $column)
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($existingColumns): void {
            $table->dropColumn($existingColumns);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
};
