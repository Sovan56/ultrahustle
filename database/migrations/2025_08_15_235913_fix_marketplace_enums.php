<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // products.status -> keep 'published'/'unlisted'
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('published','unlisted') NOT NULL DEFAULT 'published'");
        DB::statement("UPDATE products SET status='unlisted' WHERE status NOT IN ('published','unlisted') OR status IS NULL");

        // product_orders.status -> 'new','in_progress','completed','canceled'
        DB::statement("ALTER TABLE product_orders MODIFY COLUMN status ENUM('new','in_progress','completed','canceled') NOT NULL DEFAULT 'new'");
        DB::statement("UPDATE product_orders SET status='new' WHERE status NOT IN ('in_progress','completed','canceled','new') OR status IS NULL");
    }

    public function down(): void
    {
        // safe no-op / or revert if you need
    }
};
