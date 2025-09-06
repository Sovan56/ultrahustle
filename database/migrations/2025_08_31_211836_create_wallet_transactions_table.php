<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wallet_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('type', 20);      // debit|credit
            $t->decimal('amount', 12, 2);
            $t->string('currency', 8)->default('INR');
            $t->string('reason')->nullable(); // "Order #1234"
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['user_id','type']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('wallet_transactions');
    }
};
