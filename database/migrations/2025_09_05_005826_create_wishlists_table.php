<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wishlists', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('product_id');
            $t->timestamps();

            $t->unique(['user_id', 'product_id']);
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $t->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('wishlists');
    }
};
