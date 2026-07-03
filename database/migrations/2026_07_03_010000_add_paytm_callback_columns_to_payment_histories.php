<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'payment_histories';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            if (! Schema::hasColumn(self::TABLE, 'order_id')) {
                $table->string('order_id')->nullable()->after('transaction_keys');
            }

            if (! Schema::hasColumn(self::TABLE, 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('order_id');
            }

            if (! Schema::hasColumn(self::TABLE, 'status')) {
                $table->unsignedTinyInteger('status')->nullable()->after('transaction_id');
            }
        });

        if (Schema::hasColumn(self::TABLE, 'order_id')
            && ! Schema::hasIndex(self::TABLE, 'payment_histories_order_id_idx')
        ) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->index('order_id', 'payment_histories_order_id_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if (Schema::hasColumn(self::TABLE, 'order_id')
            && Schema::hasIndex(self::TABLE, 'payment_histories_order_id_idx')
        ) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropIndex('payment_histories_order_id_idx');
            });
        }

        $columns = array_values(array_filter(
            ['status', 'transaction_id', 'order_id'],
            fn (string $column): bool => Schema::hasColumn(self::TABLE, $column),
        ));

        if ($columns === []) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
