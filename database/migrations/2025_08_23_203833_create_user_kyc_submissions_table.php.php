<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_kyc_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            $table->string('legal_name');
            $table->date('dob');
            $table->text('address');

            $table->enum('id_type', ['Aadhaar', 'PAN', 'Passport', 'Other']);
            $table->string('id_number');

            // file paths on public disk
            $table->string('id_front_path');
            $table->string('id_back_path');
            $table->string('selfie_path');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('user_kyc_submissions');
    }
};
