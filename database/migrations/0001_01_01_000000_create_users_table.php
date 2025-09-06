<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---------------- USERS ----------------
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Your merged fields
            $table->string('unique_id')->unique();                // external-friendly id
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number', 25)->nullable();
            $table->unsignedBigInteger('country_id')->nullable(); // no FK, flexible
            $table->string('currency', 3)->nullable();

            // Original fields
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->decimal('wallet', 12, 2)->default(0.00);

            // Audit / consent
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('signup_ip', 45)->nullable();
            $table->string('signup_user_agent')->nullable();

            // 2FA (TOTP)
            $table->boolean('twofa_enabled')->default(false);
            $table->text('twofa_secret')->nullable();
            $table->json('twofa_recovery_codes')->nullable();

            $table->rememberToken();
            $table->timestamp('last_seen_at')->nullable();
            $table->integer('avg_response_seconds')->nullable();
            $table->timestamps();

            // Helpful indexes
            $table->index('country_id');
        });

         Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });

        // ---------------- PASSWORD RESET TOKENS (Laravel default) ----------------
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // ---------------- CUSTOM OTP TABLE (used by your UserAdminController) ----------------
        Schema::create('password_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->index();
            $table->string('code', 6);
            $table->dateTime('expires_at');
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('used_at')->nullable();
            $table->dateTime('last_sent_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['email', 'code']);
        });

        // ---------------- SESSIONS ----------------
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

         Schema::create('balance_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->enum('type', ['credit','debit'])->index(); // credit=add funds, debit=withdraw
                $table->decimal('amount', 12, 2);
                $table->string('currency_symbol', 8)->default('₹'); // from countries.currency_symbol
                $table->string('gateway')->nullable();              // 'razorpay' etc.
                $table->string('gateway_ref')->nullable();          // payment_id / order_id
                $table->enum('status', ['pending','success','failed'])->default('pending')->index();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_otps');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('balance_transactions');
    }
};
