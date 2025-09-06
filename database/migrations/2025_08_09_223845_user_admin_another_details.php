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
        Schema::create('user_admin_another_details', function (Blueprint $table) {
            $table->id();
            $table->string('user_admin_id', 255);
            $table->string('profile_picture', 255)->nullable();
            $table->string('location', 100)->nullable();
            $table->json('social_media_link')->nullable();
            $table->longText('profile_description')->nullable();
            $table->timestamps(); // created_at & updated_at
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id(); // AUTO_INCREMENT primary key
            $table->char('code', 2)->unique(); // ISO Alpha-2 code
            $table->string('name', 100);
            $table->unsignedInteger('phone'); // phone country code
            $table->string('currency_symbol', 10)->nullable();
            $table->string('currency', 3)->nullable(); // ISO Alpha-3 currency
            $table->timestamps(); // created_at, updated_at
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_admin_another_details');
        Schema::dropIfExists('countries');
    }
};
