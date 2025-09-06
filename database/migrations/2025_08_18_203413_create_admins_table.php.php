<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamps();
        });

        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();     // 'terms' or 'privacy'
            $table->string('title');
            $table->longText('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
        Schema::dropIfExists('site_pages');
    }
};
