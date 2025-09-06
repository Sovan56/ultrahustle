<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('product_orders', function (Blueprint $t) {
            if (!Schema::hasColumn('product_orders','razorpay_order_id')) {
                $t->string('razorpay_order_id')->nullable()->index();
            }
            if (!Schema::hasColumn('product_orders','razorpay_payment_id')) {
                $t->string('razorpay_payment_id')->nullable()->index();
            }
            if (!Schema::hasColumn('product_orders','razorpay_signature')) {
                $t->string('razorpay_signature')->nullable();
            }
        });
    }
    public function down(): void {
        Schema::table('product_orders', function (Blueprint $t) {
            if (Schema::hasColumn('product_orders','razorpay_order_id')) $t->dropColumn('razorpay_order_id');
            if (Schema::hasColumn('product_orders','razorpay_payment_id')) $t->dropColumn('razorpay_payment_id');
            if (Schema::hasColumn('product_orders','razorpay_signature')) $t->dropColumn('razorpay_signature');
        });
    }
};
