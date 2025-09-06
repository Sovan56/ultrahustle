<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_order_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_order_id');
            $table->unsignedInteger('number');
            $table->string('title');
            $table->text('notes')->nullable();
            $table->enum('status',['pending','in_progress','done'])->default('pending');
            $table->timestamps();

            $table->foreign('product_order_id')->references('id')->on('product_orders')->onDelete('cascade');
            $table->unique(['product_order_id','number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_order_stages');
    }
};
