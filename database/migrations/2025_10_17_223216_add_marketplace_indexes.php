<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       // database/migrations/xxxx_add_marketplace_indexes.php
Schema::table('products', function (Blueprint $t) {
    $t->index(['status', 'product_type_id']);
    $t->index(['status', 'product_subcategory_id']);
    $t->index(['status', 'uses_ai']);
    $t->index(['status', 'has_team']);
});
Schema::table('product_pricings', function (Blueprint $t) {
    $t->index(['product_id', 'tier']);
});
Schema::table('wishlists', function (Blueprint $t) {
    $t->index('product_id');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
