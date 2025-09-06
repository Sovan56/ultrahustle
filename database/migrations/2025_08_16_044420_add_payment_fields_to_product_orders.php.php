<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('product_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('product_orders', 'buyer_id')) {
                $table->unsignedBigInteger('buyer_id')->nullable()->after('buyer_name');
            }
            if (!Schema::hasColumn('product_orders', 'buyer_email')) {
                $table->string('buyer_email')->nullable()->after('buyer_id');
            }
            if (!Schema::hasColumn('product_orders', 'payment_status')) {
                $table->enum('payment_status', ['pending','paid','failed','refunded'])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('product_orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('product_orders', 'razorpay_order_id')) {
                $table->string('razorpay_order_id')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('product_orders', 'razorpay_payment_id')) {
                $table->string('razorpay_payment_id')->nullable()->after('razorpay_order_id');
            }
            if (!Schema::hasColumn('product_orders', 'razorpay_signature')) {
                $table->string('razorpay_signature')->nullable()->after('razorpay_payment_id');
            }
            if (!Schema::hasColumn('product_orders', 'buyer_approved_at')) {
                $table->timestamp('buyer_approved_at')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('product_orders', 'is_buyer_approved')) {
                $table->boolean('is_buyer_approved')->default(false)->after('buyer_approved_at');
            }
            if (!Schema::hasColumn('product_orders', 'hold_amount')) {
                $table->decimal('hold_amount', 12, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('product_orders', 'is_hold_released')) {
                $table->boolean('is_hold_released')->default(false)->after('hold_amount');
            }
        });
    }

    public function down(): void {
        Schema::table('product_orders', function (Blueprint $table) {
            $cols = ['buyer_id','buyer_email','payment_status','paid_at','razorpay_order_id','razorpay_payment_id','razorpay_signature','buyer_approved_at','is_buyer_approved','hold_amount','is_hold_released'];
            foreach ($cols as $c) if (Schema::hasColumn('product_orders', $c)) $table->dropColumn($c);
        });
    }
};
