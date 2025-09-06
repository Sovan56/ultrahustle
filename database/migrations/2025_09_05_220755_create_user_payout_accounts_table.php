<?php
// database/migrations/2025_09_06_000002_create_user_payout_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_payout_accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->enum('type', ['bank','upi','paypal'])->default('bank');
            $t->string('holder_name', 120);
            $t->string('account_number', 32)->nullable(); // bank
            $t->string('ifsc', 11)->nullable();           // bank
            $t->string('bank_name', 120)->nullable();
            $t->string('branch', 120)->nullable();
            $t->string('upi_vpa', 191)->nullable();       // upi
            $t->string('paypal_email', 191)->nullable();  // paypal
            $t->boolean('is_default')->default(false);
            $t->json('meta')->nullable(); // external ids: {razorpayx_contact_id, razorpayx_fund_account_id}
            $t->timestamps();

            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_payout_accounts');
    }
};
