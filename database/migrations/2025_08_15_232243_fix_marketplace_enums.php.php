<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // products.status -> only 'published' or 'unlisted' (no draft/unpublished)
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('published','unlisted') NOT NULL DEFAULT 'published'");

        // normalize any legacy values
        DB::statement("UPDATE products SET status='unlisted' WHERE status IN ('completed','canceled','draft','unpublished')");

        // product_orders.status -> add 'new', default 'new'
        DB::statement("ALTER TABLE product_orders MODIFY COLUMN status ENUM('new','in_progress','completed','canceled') NOT NULL DEFAULT 'new'");

        // if your seeder/default accidentally created completed orders, make brand-new ones 'new'
        // (optional) DB::statement(\"UPDATE product_orders SET status='new' WHERE status NOT IN ('in_progress','completed','canceled')\");
    }

    public function down(): void
    {
        // revert to the earlier (not recommended) sets if needed
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('published','canceled','completed') NOT NULL DEFAULT 'published'");
        DB::statement("ALTER TABLE product_orders MODIFY COLUMN status ENUM('completed','canceled','in_progress') NOT NULL DEFAULT 'completed'");
    }
};
