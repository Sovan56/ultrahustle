<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('team_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('user_admin_teams')->onDelete('cascade');
            $table->index(['team_id', 'title']);
        });

        Schema::create('team_project_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('image_path', 255); // stored on public disk under team_projects/
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('team_projects')->onDelete('cascade');
            $table->index(['project_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('team_project_images');
        Schema::dropIfExists('team_projects');
    }
};
