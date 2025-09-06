<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->string('member_email', 255)->nullable()->after('member_id');
            $table->string('status', 32)->default('pending')->after('member_email'); // pending|accepted|declined|removed
            $table->string('invite_token', 64)->nullable()->unique()->after('status');
            $table->timestamp('invited_at')->nullable()->after('invite_token');
            $table->timestamp('responded_at')->nullable()->after('invited_at');
            $table->string('invited_by', 255)->nullable()->after('responded_at');

            // optional helpful index
            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'member_email']);
            $table->index(['team_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'status']);
            $table->dropIndex(['team_id', 'member_email']);
            $table->dropIndex(['team_id', 'member_id']);
            $table->dropColumn(['member_email', 'status', 'invite_token', 'invited_at', 'responded_at', 'invited_by']);
        });
    }
};
