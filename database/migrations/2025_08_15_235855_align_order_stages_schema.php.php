<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_order_stages', function (Blueprint $table) {
            // If the FK column was named product_order_id, rename to order_id
            if (Schema::hasColumn('product_order_stages', 'product_order_id') && !Schema::hasColumn('product_order_stages', 'order_id')) {
                $table->renameColumn('product_order_id', 'order_id');
            }
            // If the position column was named number, rename to position
            if (Schema::hasColumn('product_order_stages', 'number') && !Schema::hasColumn('product_order_stages', 'position')) {
                $table->renameColumn('number', 'position');
            }
        });

        Schema::table('product_order_stages', function (Blueprint $table) {
            if (!Schema::hasColumn('product_order_stages','position')) {
                $table->unsignedInteger('position')->default(0)->after('order_id');
            }
        });
    }

    public function down(): void
    {
        // no-op (safe)
    }
};
