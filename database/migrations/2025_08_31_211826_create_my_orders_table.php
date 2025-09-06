<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('my_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('buyer_id');
            $t->unsignedBigInteger('product_id');
            $t->unsignedBigInteger('product_type_id')->nullable();
            $t->string('tier', 20)->nullable(); // basic|standard|premium

            $t->decimal('base_amount', 12, 2)->default(0);
            $t->decimal('platform_fee_percent', 5, 2)->default(0);
            $t->decimal('platform_fee_amount', 12, 2)->default(0);
            $t->decimal('gst_percent', 5, 2)->default(0);
            $t->decimal('gst_amount', 12, 2)->default(0);
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->string('currency', 8)->default('INR');

            $t->string('wallet_txn_id')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->string('status', 20)->default('paid'); // paid|delivered|completed|refunded

            $t->json('delivery_files')->nullable(); // for digital product
            $t->json('course_urls')->nullable();    // for courses
            $t->json('meta')->nullable();

            $t->timestamps();

            $t->index(['buyer_id','product_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('my_orders');
    }
};
