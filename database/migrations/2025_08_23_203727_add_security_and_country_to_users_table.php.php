<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('users', 'twofa_enabled')) {
                $table->boolean('twofa_enabled')->default(false)->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'twofa_secret')) {
                $table->text('twofa_secret')->nullable()->after('twofa_enabled'); // store base32 secret
            }
            if (!Schema::hasColumn('users', 'twofa_recovery_codes')) {
                $table->json('twofa_recovery_codes')->nullable()->after('twofa_secret');
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'country_id')) $table->dropColumn('country_id');
            if (Schema::hasColumn('users', 'twofa_enabled')) $table->dropColumn('twofa_enabled');
            if (Schema::hasColumn('users', 'twofa_secret')) $table->dropColumn('twofa_secret');
            if (Schema::hasColumn('users', 'twofa_recovery_codes')) $table->dropColumn('twofa_recovery_codes');
        });
    }
};
