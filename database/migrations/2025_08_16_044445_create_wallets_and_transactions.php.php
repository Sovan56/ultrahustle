<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->decimal('available_balance', 12, 2)->default(0);
                $table->decimal('hold_balance', 12, 2)->default(0);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('balance_transactions')) {
            Schema::create('balance_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->enum('type', ['hold','release','credit','debit']);
                $table->decimal('amount', 12, 2);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('wallets');
    }
};
