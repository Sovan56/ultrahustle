<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure columns exist
        Schema::table('product_order_stages', function (Blueprint $table) {
            if (!Schema::hasColumn('product_order_stages', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('product_order_stages', 'position')) {
                $table->unsignedInteger('position')->default(0)->after('order_id');
            }
        });

        // Migrate legacy product_order_id -> order_id
        if (Schema::hasColumn('product_order_stages', 'product_order_id')) {
            DB::statement('UPDATE product_order_stages SET order_id = product_order_id WHERE order_id IS NULL');

            // Try to drop legacy column (ignore if DBAL not present)
            try {
                Schema::table('product_order_stages', function (Blueprint $table) {
                    $table->dropColumn('product_order_id');
                });
            } catch (\Throwable $e) {
                // ignore (column may be locked by lack of doctrine/dbal)
            }
        }

        // Add index on order_id if missing (no Doctrine)
        $this->addIndexIfMissing('product_order_stages', 'order_id', 'product_order_stages_order_id_index');
    }

    public function down(): void
    {
        // Optional: drop index if exists
        $this->dropIndexIfExists('product_order_stages', 'product_order_stages_order_id_index');

        // Optional: remove position
        if (Schema::hasColumn('product_order_stages', 'position')) {
            Schema::table('product_order_stages', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }

        // (We keep order_id; restoring product_order_id is rarely needed)
    }

    private function addIndexIfMissing(string $table, string $column, string $indexName): void
    {
        $conn = Schema::getConnection()->getName();
        $db   = config("database.connections.$conn.database");
        $exists = DB::select(
            'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$db, $table, $indexName]
        );

        if (empty($exists)) {
            try {
                Schema::table($table, function (Blueprint $tbl) use ($column, $indexName) {
                    $tbl->index($column, $indexName);
                });
            } catch (\Throwable $e) {
                // ignore if race condition creates it meanwhile
            }
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $conn = Schema::getConnection()->getName();
        $db   = config("database.connections.$conn.database");
        $exists = DB::select(
            'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$db, $table, $indexName]
        );

        if (!empty($exists)) {
            try {
                Schema::table($table, function (Blueprint $tbl) use ($indexName) {
                    $tbl->dropIndex($indexName);
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};
