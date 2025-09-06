<?php
// database/migrations/2025_08_31_000002_create_product_clicks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_clicks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('product_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('source', 50)->default('welcome'); // welcome|listing|other
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->timestamps();

            $t->index(['product_id','created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('product_clicks');
    }
};
