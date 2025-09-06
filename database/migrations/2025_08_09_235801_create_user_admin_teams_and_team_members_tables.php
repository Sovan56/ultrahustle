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
        // Teams table
        Schema::create('user_admin_teams', function (Blueprint $table) {
            $table->bigIncrements('id'); // BIGINT primary key
            $table->string('team_name', 255);
            $table->string('team_owner_id', 255);
            $table->string('profile_image', 255)->nullable();
            $table->text('about')->nullable();
            $table->timestamps();
        });

        // Team Members table
        Schema::create('team_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id');
            $table->string('positions', 255)->nullable();
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->string('member_id', 255)->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('team_id')->references('id')->on('user_admin_teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('user_admin_teams');
    }
};
