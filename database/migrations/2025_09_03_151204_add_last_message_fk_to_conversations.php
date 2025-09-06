<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Check if the FK already exists using information_schema (no Doctrine needed)
        $dbName = DB::getDatabaseName();
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'conversations')
            ->where('CONSTRAINT_NAME', 'conversations_last_message_id_foreign')
            ->exists();

        if (! $exists) {
            Schema::table('conversations', function (Blueprint $table) {
                // Ensure the column exists & is nullable
                if (! Schema::hasColumn('conversations', 'last_message_id')) {
                    $table->unsignedBigInteger('last_message_id')->nullable()->index();
                }

                // Now add the FK (messages must already exist)
                $table->foreign('last_message_id')
                    ->references('id')->on('messages')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Only drop if the FK exists
        $dbName = DB::getDatabaseName();
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'conversations')
            ->where('CONSTRAINT_NAME', 'conversations_last_message_id_foreign')
            ->exists();

        if ($exists) {
            Schema::table('conversations', function (Blueprint $table) {
                try {
                    $table->dropForeign('conversations_last_message_id_foreign');
                } catch (\Throwable $e) {
                    // ignore if already removed
                }
            });
        }
    }
};
