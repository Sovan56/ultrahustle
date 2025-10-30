<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) service_orders
        Schema::create('service_orders', function (Blueprint $t) {
            $t->bigIncrements('id');

            $t->unsignedBigInteger('buyer_id');
            $t->unsignedBigInteger('seller_id');

            $t->unsignedBigInteger('product_id')->nullable();
            $t->unsignedBigInteger('subcategory_id')->nullable();

            $t->longText('terms')->nullable();
            $t->json('meta')->nullable(); // e.g., origin_product_id, conversation_id, etc.

            // Buyer display currency
            $t->string('currency_code', 3)->default('USD');
            $t->string('currency_symbol', 8)->default('$');

            // Totals (buyer currency)
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('platform_fee_percent', 6, 3)->default(0);
            $t->decimal('platform_fee_amount', 12, 2)->default(0);
            $t->decimal('gst_percent', 6, 3)->default(0);
            $t->decimal('gst_amount', 12, 2)->default(0);
            $t->decimal('total_payable', 12, 2)->default(0);

            // Holds (buyer currency)
            $t->decimal('hold_amount', 12, 2)->default(0);
            $t->decimal('released_amount', 12, 2)->default(0);

            // Admin audit (base currency, e.g., USD)
            $t->string('base_currency_code', 3)->default('USD');
            $t->decimal('base_subtotal', 12, 2)->default(0);
            $t->decimal('base_total_payable', 12, 2)->default(0);
            $t->decimal('base_hold_amount', 12, 2)->default(0);
            $t->decimal('base_released_amount', 12, 2)->default(0);

            $t->enum('status', [
                'draft','sent','reupdated','canceled_by_buyer','canceled_by_seller',
                'approved_paid','in_progress','completed',
                'dispute_open','dispute_resolved_refund','dispute_resolved_release'
            ])->default('draft');

            $t->timestamps();

            // Indexes
            $t->index(['buyer_id']);
            $t->index(['seller_id']);
            $t->index(['status']);
        });

        // 2) service_milestones
        Schema::create('service_milestones', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('service_order_id');

            $t->string('title', 160);
            $t->text('description')->nullable();
            $t->decimal('price', 12, 2); // buyer currency

            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();

            $t->enum('status', ['draft','submitted','approved','released','canceled','resubmitted'])->default('draft');

            $t->timestamps();

            $t->index(['service_order_id']);
        });

        // 3) service_milestone_submissions
        Schema::create('service_milestone_submissions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('service_milestone_id');
            $t->unsignedBigInteger('seller_id');

            $t->text('note')->nullable();

            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
            $t->unsignedBigInteger('file_size')->nullable();
            $t->string('file_mime', 191)->nullable();
            $t->string('url', 512)->nullable();

            $t->timestamps();

            $t->index(['service_milestone_id']);
            $t->index(['seller_id']);
        });

        // 4) service_reports (disputes)
        Schema::create('service_reports', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('service_order_id');
            $t->unsignedBigInteger('reporter_id');
            $t->enum('role', ['buyer','seller']);

            $t->text('reason')->nullable();
            $t->text('resolution_note')->nullable();
            $t->json('evidence')->nullable();
            $t->enum('status', ['open','approved','rejected'])->default('open');

            $t->timestamps();

            $t->index(['service_order_id']);
            $t->index(['status']);
        });

        // 🔒 Set up foreign keys last to avoid creation order issues
        Schema::table('service_orders', function (Blueprint $t) {
            $t->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
            $t->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            // product_id/subcategory_id left without FK for flexibility; add if you want
        });

        Schema::table('service_milestones', function (Blueprint $t) {
            $t->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('cascade');
        });

        Schema::table('service_milestone_submissions', function (Blueprint $t) {
            $t->foreign('service_milestone_id')->references('id')->on('service_milestones')->onDelete('cascade');
            $t->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('service_reports', function (Blueprint $t) {
            $t->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('cascade');
            $t->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('service_reports', function (Blueprint $t) {
            $t->dropForeign(['service_order_id']);
            $t->dropForeign(['reporter_id']);
        });
        Schema::table('service_milestone_submissions', function (Blueprint $t) {
            $t->dropForeign(['service_milestone_id']);
            $t->dropForeign(['seller_id']);
        });
        Schema::table('service_milestones', function (Blueprint $t) {
            $t->dropForeign(['service_order_id']);
        });
        Schema::table('service_orders', function (Blueprint $t) {
            $t->dropForeign(['buyer_id']);
            $t->dropForeign(['seller_id']);
        });

        Schema::dropIfExists('service_reports');
        Schema::dropIfExists('service_milestone_submissions');
        Schema::dropIfExists('service_milestones');
        Schema::dropIfExists('service_orders');
    }
};
