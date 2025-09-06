<?php
// database/migrations/2025_09_06_000001_alter_balance_transactions_for_wallet.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('balance_transactions', function (Blueprint $t) {
            if (!Schema::hasColumn('balance_transactions', 'currency_code')) {
                $t->string('currency_code', 3)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('balance_transactions', 'gateway_ref')) {
                $t->string('gateway_ref', 128)->nullable()->unique()->after('gateway');
            }
            if (!Schema::hasColumn('balance_transactions', 'reference')) {
                $t->string('reference', 128)->nullable()->unique()->after('gateway_ref'); // our idempotency key
            }
            if (!Schema::hasColumn('balance_transactions', 'category')) {
                $t->string('category', 24)->default('adjustment')->after('type'); // add_funds|withdraw|purchase|sale|adjustment
            }
            if (!Schema::hasColumn('balance_transactions', 'counterparty')) {
                $t->string('counterparty', 191)->nullable()->after('gateway');
            }
            if (!Schema::hasColumn('balance_transactions', 'payout_account_id')) {
                $t->unsignedBigInteger('payout_account_id')->nullable()->after('counterparty');
                $t->foreign('payout_account_id')->references('id')->on('user_payout_accounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('balance_transactions', 'meta')) {
                $t->json('meta')->nullable();
            }
        });

        // Expand status enum to include reversed & canceled if using MySQL enum; else leave as-is or string
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE balance_transactions 
                    MODIFY status ENUM('pending','success','failed','reversed','canceled') 
                    NOT NULL DEFAULT 'pending'");
            }
        } catch (\Throwable $e) {
            // ignore; status can remain as string/previous enum; we only rely on 'pending|success|failed' for our flows
        }

        Schema::table('balance_transactions', function (Blueprint $t) {
            $t->index(['user_id', 'created_at']);
            $t->index(['user_id', 'status', 'created_at']);
            $t->index(['user_id', 'category', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('balance_transactions', function (Blueprint $t) {
            if (Schema::hasColumn('balance_transactions', 'payout_account_id')) {
                $t->dropForeign(['payout_account_id']);
                $t->dropColumn('payout_account_id');
            }
            foreach (['currency_code','gateway_ref','reference','category','counterparty','meta'] as $c) {
                if (Schema::hasColumn('balance_transactions', $c)) $t->dropColumn($c);
            }
            $t->dropIndex(['balance_transactions_user_id_created_at_index']);
            $t->dropIndex(['balance_transactions_user_id_status_created_at_index']);
            $t->dropIndex(['balance_transactions_user_id_category_created_at_index']);
        });
    }
};
